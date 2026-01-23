<?php
/**
 * AJAX Handler: Batch Delete Records
 * 批次刪除處理（參考 ajax_permanent_delete.php 改寫為陣列版本）
 */

require_once('../Connections/connect2data.php');
require_once(__DIR__ . '/includes/SortHelper.php');

header('Content-Type: application/json');

try {
    $module = $_POST['module'] ?? '';
    $itemIds = $_POST['item_ids'] ?? [];
    $isTrashMode = (int)($_POST['trash'] ?? 0);
    $force = (int)($_POST['force'] ?? 0); // 【新增】是否強制刪除

    if (empty($module) || empty($itemIds) || !is_array($itemIds)) {
        throw new Exception('缺少必要參數');
    }

    // 1. 載入模組配置
    $configFile = __DIR__ . "/set/{$module}Set.php";
    if (!file_exists($configFile)) {
        throw new Exception("找不到模組配置檔案");
    }

    $moduleConfig = require $configFile;
    $tableName = $moduleConfig['tableName'];
    $primaryKey = $moduleConfig['primaryKey'];
    $col_delete_time = $moduleConfig['cols']['delete_time'] ?? 'd_delete_time';
    $col_file_fk = $moduleConfig['cols']['file_fk'] ?? 'file_d_id';

    // 2. 檢查是否有刪除時間欄位 (判斷是否支援軟刪除)
    $stmtCheck = $conn->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
    $stmtCheck->execute([$col_delete_time]);
    $hasDeleteTime = (bool)$stmtCheck->fetch();

    // 3. 【防呆檢查】如果是分類模組且未強制刪除，檢查是否有關聯文章
    if ((strpos($module, 'Cate') !== false || strpos($module, 'Key') !== false) && $force == 0) {
        $mainModule = str_replace(['Cate', 'Key'], '', $module);
        $mainConfigFile = __DIR__ . "/set/{$mainModule}Set.php";
        
        if (file_exists($mainConfigFile)) {
            unset($settingPage);
            $mainConfig = require $mainConfigFile;
            if (!is_array($mainConfig) && isset($settingPage)) $mainConfig = $settingPage;
            
            $articleTable = $mainConfig['tableName'];
            $articleCategoryField = $mainConfig['listPage']['categoryField'] ?? null;

            if ($articleTable && $articleCategoryField) {
                // 檢查每個分類是否有關聯文章
                $categoriesWithArticles = [];
                
                foreach ($itemIds as $itemId) {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$articleTable} WHERE {$articleCategoryField} = :cat_id");
                    $stmt->execute([':cat_id' => $itemId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $articleCount = $result['count'];
                    
                    if ($articleCount > 0) {
                        // 獲取分類名稱
                        $catStmt = $conn->prepare("SELECT * FROM {$tableName} WHERE {$primaryKey} = :id");
                        $catStmt->execute([':id' => $itemId]);
                        $category = $catStmt->fetch(PDO::FETCH_ASSOC);
                        $categoryName = $category['t_name'] ?? $category['d_title'] ?? "ID:{$itemId}";
                        
                        $categoriesWithArticles[] = [
                            'id' => $itemId,
                            'name' => $categoryName,
                            'count' => $articleCount
                        ];
                    }
                }
                
                // 如果有分類包含文章，返回錯誤並提示可強制刪除
                if (!empty($categoriesWithArticles)) {
                    $errorMessages = [];
                    foreach ($categoriesWithArticles as $cat) {
                        $errorMessages[] = "「{$cat['name']}」下尚有 {$cat['count']} 筆文章";
                    }
                    
                    // 【修改】返回 has_data 標記，讓前端可以詢問是否強制刪除
                    echo json_encode([
                        'success' => false,
                        'has_data' => true,
                        'message' => implode("\n", $errorMessages)
                    ]);
                    exit;
                }
            }
        }
    }

    $conn->beginTransaction();

    // 4. 【新增】如果強制刪除且是分類模組，先刪除所有關聯文章
    if ((strpos($module, 'Cate') !== false || strpos($module, 'Key') !== false) && $force == 1) {
        $mainModule = str_replace(['Cate', 'Key'], '', $module);
        $mainConfigFile = __DIR__ . "/set/{$mainModule}Set.php";
        
        if (file_exists($mainConfigFile)) {
            unset($settingPage);
            $mainConfig = require $mainConfigFile;
            if (!is_array($mainConfig) && isset($settingPage)) $mainConfig = $settingPage;
            
            $articleTable = $mainConfig['tableName'];
            $articleCategoryField = $mainConfig['listPage']['categoryField'] ?? null;
            $articleFileFk = $mainConfig['cols']['file_fk'] ?? 'file_d_id';

            if ($articleTable && $articleCategoryField) {
                foreach ($itemIds as $itemId) {
                    // 找出該分類下所有的文章 ID
                    $stmt = $conn->prepare("SELECT {$mainConfig['primaryKey']} as id FROM {$articleTable} WHERE {$articleCategoryField} = :cat_id");
                    $stmt->execute([':cat_id' => $itemId]);
                    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // 刪除文章的檔案
                    foreach ($articles as $art) {
                        deleteRelatedFiles($conn, $articleFileFk, $art['id']);
                        $conn->prepare("DELETE FROM file_set WHERE {$articleFileFk} = :id")->execute([':id' => $art['id']]);
                    }
                    
                    // 刪除文章主資料
                    $conn->prepare("DELETE FROM {$articleTable} WHERE {$articleCategoryField} = :cat_id")->execute([':cat_id' => $itemId]);
                }
            }
        }
    }

    // 4.1 【新增】在刪除前取得項目資料，以便後續重新排序所需的範圍資訊
    $items = [];
    if (!empty($itemIds)) {
        $primaryKey = $moduleConfig['primaryKey'];
        $tableName = $moduleConfig['tableName'];
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $itemQuery = "SELECT * FROM `{$tableName}` WHERE `{$primaryKey}` IN ($placeholders)";
        $stmt = $conn->prepare($itemQuery);
        $stmt->execute($itemIds);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    
    if ($isTrashMode) {
        // 垃圾桶模式：真實永久刪除
        // 先刪除所有關聯檔案
        foreach ($itemIds as $itemId) {
            deleteRelatedFiles($conn, $col_file_fk, $itemId);
            $conn->prepare("DELETE FROM file_set WHERE {$col_file_fk} = :id")->execute([':id' => $itemId]);
        }
        
        $sql = "DELETE FROM `{$tableName}` WHERE `{$primaryKey}` IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($itemIds);
        $message = "已永久刪除 " . count($itemIds) . " 筆資料";
    } else {
        if ($hasDeleteTime) {
            // 支持軟刪除：更新刪除時間
            $now = date('Y-m-d H:i:s');
            $sql = "UPDATE `{$tableName}` SET `{$col_delete_time}` = ? WHERE `{$primaryKey}` IN ($placeholders)";
            $params = array_merge([$now], $itemIds);
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $message = "已將 " . count($itemIds) . " 筆資料移至回收桶";
        } else {
            // 不支持軟刪除：真實刪除
            // 先刪除所有關聯檔案
            foreach ($itemIds as $itemId) {
                deleteRelatedFiles($conn, $col_file_fk, $itemId);
                $conn->prepare("DELETE FROM file_set WHERE {$col_file_fk} = :id")->execute([':id' => $itemId]);
            }
            
            $sql = "DELETE FROM `{$tableName}` WHERE `{$primaryKey}` IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($itemIds);
            $message = "已刪除 " . count($itemIds) . " 筆資料";
        }
    }

    // 5. 【新增】重新排序 (消除空缺)
    if (!empty($items)) {
        $primaryKey = $moduleConfig['primaryKey'];
        $tableName = $moduleConfig['tableName'];
        
        $cols = $moduleConfig['cols'] ?? [];
        $col_sort = $cols['sort'] ?? 'd_sort';
        
        if (!empty($col_sort)) {
            $menuKey = $moduleConfig['menuKey'] ?? null;
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
            $parentIdField = $cols['parent_id'] ?? null;
            
            $affectedScopes = [];
            foreach ($items as $item) {
                // 構建唯一的範圍 Key
                $scopeParams = [];
                $scopeWhere = ["1=1"];
                
                if ($menuKey) {
                    $actualMenuKey = ($menuKey === 'd_class1' && $tableName === 'taxonomies') ? 'taxonomy_type_id' : $menuKey;
                    if (isset($item[$actualMenuKey])) {
                        $scopeWhere[] = "`{$actualMenuKey}` = :menuValue";
                        $scopeParams[':menuValue'] = $item[$actualMenuKey];
                    }
                }

                if ($categoryField && isset($item[$categoryField])) {
                    $scopeWhere[] = "`{$categoryField}` = :cat";
                    $scopeParams[':cat'] = $item[$categoryField];
                }

                if ($parentIdField && isset($item[$parentIdField])) {
                    $scopeWhere[] = "`{$parentIdField}` = :parent_id";
                    $scopeParams[':parent_id'] = $item[$parentIdField];
                }

                if (isset($item['lang'])) {
                    $scopeWhere[] = "`lang` = :lang";
                    $scopeParams[':lang'] = $item['lang'];
                }

                // 排除置頂項目
                $col_top = $cols['top'] ?? null;
                if ($col_top) {
                    $scopeWhere[] = "(`{$col_top}` = 0 OR `{$col_top}` IS NULL)";
                }

                if ($hasDeleteTime) {
                    $scopeWhere[] = "`{$col_delete_time}` IS NULL";
                }
                
                $scopeKey = serialize(['where' => $scopeWhere, 'params' => $scopeParams]);
                if (!isset($affectedScopes[$scopeKey])) {
                    $affectedScopes[$scopeKey] = [
                        'where' => implode(' AND ', $scopeWhere),
                        'params' => $scopeParams
                    ];
                }
            }
            
            // 對每個受影響的範圍執行重新排序
            foreach ($affectedScopes as $scope) {
                SortHelper::reindex($conn, $tableName, $primaryKey, $col_sort, $scope['where'], $scope['params']);
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * 輔助函式：刪除實體檔案
 */
function deleteRelatedFiles($conn, $fkColumn, $id) {
    $stmt = $conn->prepare("SELECT * FROM file_set WHERE {$fkColumn} = :id");
    $stmt->execute([':id' => $id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($files as $f) {
        for ($i=1; $i<=5; $i++) {
            $link = "file_link{$i}";
            if (!empty($f[$link]) && file_exists("../" . $f[$link])) {
                @unlink("../" . $f[$link]);
            }
        }
    }
}

<?php
/**
 * 通用刪除處理 - 動態版 (修正 SQL 語法錯誤版)
 */
session_start();
require_once '../Connections/connect2data.php';

// 載入 Element 模組
require_once(__DIR__ . '/includes/elements/ModuleConfigElement.php');
require_once(__DIR__ . '/includes/elements/PermissionElement.php');
require_once(__DIR__ . '/includes/SortHelper.php');

if (!isset($_GET['module']) || !isset($_GET['id'])) {
    die('Missing required parameters');
}

// 1. 安全過濾輸入
$module = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['module']); // 只允許字母數字底線
$id = intval($_GET['id']);

// 2. 權限檢查
// 獲取權限狀況
list($canView, $canAdd, $canEdit, $canDelete) = PermissionElement::checkModulePermission($conn, $module);

if (!$canDelete) {
    die('錯誤：您沒有權限執行刪除動作');
}

// 載入模組配置
$configFile = __DIR__ . "/set/{$module}Set.php";
if (!file_exists($configFile)) {
    die("Module config not found: {$module}Set.php");
}

$moduleConfig = require $configFile;
if (!is_array($moduleConfig) && isset($settingPage)) {
    $moduleConfig = $settingPage;
}

// ---------------------------------------------------------------------
// 動態欄位定義
// ---------------------------------------------------------------------
$tableName = $moduleConfig['tableName'];
$menuKey   = $moduleConfig['menuKey'] ?? null;
$menuValue = $moduleConfig['menuValue'] ?? null;

$col_id = $moduleConfig['primaryKey'];
$cols = $moduleConfig['cols'] ?? [];

$col_delete_time = array_key_exists('delete_time', $cols) ? $cols['delete_time'] : 'd_delete_time'; 

// 【修正 1】更嚴謹的排序欄位定義
// 如果設定檔明確寫 null，就是 null；否則嘗試讀取設定，預設為 'd_sort'
// 建議：如果該表真的沒排序欄位，Config 裡 cols['sort'] 必須設為 null
$col_sort = array_key_exists('sort', $cols) ? $cols['sort'] : 'd_sort';

$col_file_fk = $cols['file_fk'] ?? 'file_d_id';
$categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
$parentIdField = $cols['parent_id'] ?? null;

// 【新增】檢查是否為 cascade 模式
$cascadeMode = isset($_GET['cascade']) && $_GET['cascade'] == '1';
// ---------------------------------------------------------------------

/**
 * 遞迴刪除所有子分類
 * @param bool $isSoftDelete 是否為軟刪除（移至回收桶）
 */
function deleteChildrenRecursively($conn, $tableName, $parentIdField, $col_id, $parentId, $col_file_fk, $isSoftDelete = false, $col_delete_time = null) {
    // 查詢所有子項目
    $childQuery = "SELECT {$col_id} FROM {$tableName} WHERE {$parentIdField} = :parent_id";
    $stmt = $conn->prepare($childQuery);
    $stmt->execute([':parent_id' => $parentId]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($children as $child) {
        $childId = $child[$col_id];
        
        // 遞迴刪除子項目的子項目
        deleteChildrenRecursively($conn, $tableName, $parentIdField, $col_id, $childId, $col_file_fk, $isSoftDelete, $col_delete_time);
        
        if ($isSoftDelete && $col_delete_time) {
            // 【軟刪除】設定 delete_time 為當前時間
            $softDeleteQuery = "UPDATE {$tableName} SET {$col_delete_time} = NOW() WHERE {$col_id} = :id";
            $conn->prepare($softDeleteQuery)->execute([':id' => $childId]);
        } else {
            // 【硬刪除】刪除子項目的關聯圖片
            $imageQuery = "SELECT * FROM file_set WHERE {$col_file_fk} = :id";
            $imgStmt = $conn->prepare($imageQuery);
            $imgStmt->execute([':id' => $childId]);
            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($images as $image) {
                $links = ['file_link1', 'file_link2', 'file_link3', 'file_link4', 'file_link5'];
                foreach ($links as $link) {
                    if (!empty($image[$link]) && file_exists("../" . $image[$link])) {
                        unlink("../" . $image[$link]);
                    }
                }
            }
            $conn->prepare("DELETE FROM file_set WHERE {$col_file_fk} = :id")->execute([':id' => $childId]);
            
            // 刪除子項目本身
            $conn->prepare("DELETE FROM {$tableName} WHERE {$col_id} = :id")->execute([':id' => $childId]);
        }
    }
}

try {
    $conn->beginTransaction();
    
    // 1. 獲取要刪除項目的當前資訊
    $getItemQuery = "SELECT * FROM {$tableName} WHERE {$col_id} = :id";
    $stmt = $conn->prepare($getItemQuery);
    $stmt->execute([':id' => $id]);
    $itemToDelete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemToDelete) {
        throw new Exception('Item not found');
    }
    
    // 【修改】檢查是否有子分類（防呆機制）
    if ($parentIdField && !$cascadeMode) {
        // 非 cascade 模式：檢查並阻止刪除
        $checkChildrenQuery = "SELECT COUNT(*) as child_count FROM {$tableName} WHERE {$parentIdField} = :id";
        $stmt = $conn->prepare($checkChildrenQuery);
        $stmt->execute([':id' => $id]);
        $childCount = $stmt->fetch(PDO::FETCH_ASSOC)['child_count'];
        
        if ($childCount > 0) {
            throw new Exception("無法刪除：此分類下還有 {$childCount} 個子分類，請先刪除或移動子分類");
        }
    } elseif ($parentIdField && $cascadeMode) {
        // Cascade 模式：只有硬刪除才支援級聯刪除
        // 軟刪除（回收桶）不支援級聯，避免還原時的外鍵問題
        // 【修正】檢查 delete_time 是否為 null（硬刪除）或有值（軟刪除）
        $isSoftDelete = ($col_delete_time !== null && $col_delete_time !== '');
        
        if ($isSoftDelete) {
            // 【重要】軟刪除不支援級聯刪除
            throw new Exception("此模組使用回收桶機制，不支援級聯刪除。請先手動刪除所有子分類，再刪除父分類。");
        } else {
            // 硬刪除才執行級聯刪除
            deleteChildrenRecursively($conn, $tableName, $parentIdField, $col_id, $id, $col_file_fk, false, null);
        }
    }
    
    // 2. 判斷刪除模式 (設定優先於資料表檢測)
    $hasTrashConfig = $moduleConfig['listPage']['hasTrash'] ?? null;
    $isSoftDelete = false;

    if ($hasTrashConfig !== false && !empty($col_delete_time)) {
        $checkColumnQuery = "SHOW COLUMNS FROM {$tableName} LIKE '{$col_delete_time}'";
        $stmt = $conn->prepare($checkColumnQuery);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $isSoftDelete = true;
        }
    }
    
    // 3. 執行刪除
    if ($isSoftDelete) {
        // A. 軟刪除
        $conn->prepare("UPDATE {$tableName} SET {$col_delete_time} = NOW() WHERE {$col_id} = :id")
             ->execute([':id' => $id]);

        // 【新增】重新排序 (消除空缺)
        if (!empty($col_sort)) {
            $sortWhere = "1=1";
            $sortParams = [];

            // 使用該項目的實際值進行精確範圍鎖定
            if ($menuKey) {
                $actualMenuKey = ($menuKey === 'd_class1' && $tableName === 'taxonomies') ? 'taxonomy_type_id' : $menuKey;
                if (isset($itemToDelete[$actualMenuKey])) {
                    $sortWhere .= " AND `{$actualMenuKey}` = :menuValue";
                    $sortParams[':menuValue'] = $itemToDelete[$actualMenuKey];
                }
            }

            if ($categoryField && isset($itemToDelete[$categoryField])) {
                $sortWhere .= " AND `{$categoryField}` = :cat";
                $sortParams[':cat'] = $itemToDelete[$categoryField];
            }

            if ($parentIdField && isset($itemToDelete[$parentIdField])) {
                $sortWhere .= " AND `{$parentIdField}` = :parent_id";
                $sortParams[':parent_id'] = $itemToDelete[$parentIdField];
            }

            if (isset($itemToDelete['lang'])) {
                $sortWhere .= " AND `lang` = :lang";
                $sortParams[':lang'] = $itemToDelete['lang'];
            }

            if (!empty($col_delete_time)) {
                $sortWhere .= " AND (`{$col_delete_time}` IS NULL)";
            }

            SortHelper::reindex($conn, $tableName, $col_id, $col_sort, $sortWhere, $sortParams);
        }

    } else {
        // --- B. 硬刪除 ---
        
        // 先暫存排序資訊
        $deletedSort = 0;
        if (!empty($col_sort)) {
            $deletedSort = $itemToDelete[$col_sort] ?? 0;
        }
        
        // B-1. 刪除關聯圖片
        $imageQuery = "SELECT * FROM file_set WHERE {$col_file_fk} = :id";
        $stmt = $conn->prepare($imageQuery);
        $stmt->execute([':id' => $id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($images as $image) {
            $links = ['file_link1', 'file_link2', 'file_link3', 'file_link4', 'file_link5'];
            foreach ($links as $link) {
                if (!empty($image[$link]) && file_exists("../" . $image[$link])) {
                    unlink("../" . $image[$link]);
                }
            }
        }
        $delFileSql = "DELETE FROM file_set WHERE {$col_file_fk} = :id";
        $stmtFile = $conn->prepare($delFileSql);
        $stmtFile->execute([':id' => $id]);
        
        // B-2. 刪除主資料
        $deleteQuery = "DELETE FROM {$tableName} WHERE {$col_id} = :id";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->execute([':id' => $id]);

        // 【新增】重新排序 (消除空缺)
        if (!empty($col_sort)) {
            $sortWhere = "1=1";
            $sortParams = [];

            // 使用該項目的實際值進行精確範圍鎖定
            if ($menuKey) {
                $actualMenuKey = ($menuKey === 'd_class1' && $tableName === 'taxonomies') ? 'taxonomy_type_id' : $menuKey;
                if (isset($itemToDelete[$actualMenuKey])) {
                    $sortWhere .= " AND `{$actualMenuKey}` = :menuValue";
                    $sortParams[':menuValue'] = $itemToDelete[$actualMenuKey];
                }
            }

            if ($categoryField && isset($itemToDelete[$categoryField])) {
                $sortWhere .= " AND `{$categoryField}` = :cat";
                $sortParams[':cat'] = $itemToDelete[$categoryField];
            }

            if ($parentIdField && isset($itemToDelete[$parentIdField])) {
                $sortWhere .= " AND `{$parentIdField}` = :parent_id";
                $sortParams[':parent_id'] = $itemToDelete[$parentIdField];
            }

            if (isset($itemToDelete['lang'])) {
                $sortWhere .= " AND `lang` = :lang";
                $sortParams[':lang'] = $itemToDelete['lang'];
            }

            if (!empty($col_delete_time)) {
                $sortWhere .= " AND (`{$col_delete_time}` IS NULL)";
            }

            SortHelper::reindex($conn, $tableName, $col_id, $col_sort, $sortWhere, $sortParams);
        }
    }
    
    $conn->commit();
    
    // 重定向回列表頁
    $redirectUrl = PORTAL_AUTH_URL."tpl={$module}/list";
    if ($categoryField && isset($itemToDelete[$categoryField])) {
        $redirectUrl .= "?selected1=" . $itemToDelete[$categoryField];
    }
    
    header("Location: " . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Delete failed: " . $e->getMessage());
}
?>
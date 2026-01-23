<?php
/**
 * AJAX Handler: Batch Restore Records
 * 批次還原資料
 */

require_once('../Connections/connect2data.php');
require_once(__DIR__ . '/includes/SortHelper.php');

header('Content-Type: application/json');

ob_start();

try {
    $conn->beginTransaction();
    $module = $_POST['module'] ?? '';
    $itemIds = $_POST['item_ids'] ?? [];
    
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
    $deleteTimeField = $moduleConfig['cols']['delete_time'] ?? 'd_delete_time';
    
    $successCount = 0;
    $errors = [];

    foreach ($itemIds as $itemId) {
        $itemId = (int)$itemId;
        if ($itemId <= 0) continue;

        try {
            // 還原資料（將 delete_time 設為 NULL）
            $sqlRestore = "UPDATE {$tableName} SET {$deleteTimeField} = NULL WHERE {$primaryKey} = :id";
            $stmtRestore = $conn->prepare($sqlRestore);
            $stmtRestore->execute([':id' => $itemId]);
            
            if ($stmtRestore->rowCount() > 0) {
                $successCount++;
            } else {
                $errors[] = "ID {$itemId}: 找不到資料或已還原";
            }
        } catch (Exception $innerEx) {
            $errors[] = "ID {$itemId}: " . $innerEx->getMessage();
        }
    }

    // 2. 【新增】重新排序 (消除空缺/重複)
    if ($successCount > 0) {
        $cols = $moduleConfig['cols'] ?? [];
        $col_sort = $cols['sort'] ?? 'd_sort';

        if (!empty($col_sort)) {
            $menuKey = $moduleConfig['menuKey'] ?? null;
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
            $parentIdField = $cols['parent_id'] ?? null;

            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $itemQuery = "SELECT * FROM `{$tableName}` WHERE `{$primaryKey}` IN ($placeholders)";
            $stmt = $conn->prepare($itemQuery);
            $stmt->execute($itemIds);
            $restoredItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $affectedScopes = [];
            foreach ($restoredItems as $item) {
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

                $scopeWhere[] = "`{$deleteTimeField}` IS NULL";

                $scopeKey = serialize(['where' => $scopeWhere, 'params' => $scopeParams]);
                if (!isset($affectedScopes[$scopeKey])) {
                    $affectedScopes[$scopeKey] = [
                        'where' => implode(' AND ', $scopeWhere),
                        'params' => $scopeParams
                    ];
                }
            }

            foreach ($affectedScopes as $scope) {
                SortHelper::reindex($conn, $tableName, $primaryKey, $col_sort, $scope['where'], $scope['params']);
            }
        }
    }
    
    $conn->commit();

    echo json_encode([
        'success' => true,
        'count' => $successCount,
        'errors' => $errors,
        'message' => "成功還原 {$successCount} 筆資料" . (!empty($errors) ? "，但有 " . count($errors) . " 筆失敗" : "")
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

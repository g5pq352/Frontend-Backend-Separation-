<?php
/**
 * AJAX 還原處理 - 通用動態版
 * 增加還原後自動重新排序邏輯（置頂並遞補）
 */
session_start();
require_once '../Connections/connect2data.php';
require_once(__DIR__ . '/includes/SortHelper.php');

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$module = $_POST['module'] ?? '';
$itemId = intval($_POST['item_id'] ?? 0);

if (!$module || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // 【修改】使用 ModuleConfigElement 載入配置
    require_once(__DIR__ . '/includes/elements/ModuleConfigElement.php');
    $moduleConfig = ModuleConfigElement::loadConfig($module);
    
    // ---------------------------------------------------------------------
    // 動態欄位定義
    // ---------------------------------------------------------------------
    $tableName       = $moduleConfig['tableName'];
    $col_id          = $moduleConfig['primaryKey'];
    $menuKey         = $moduleConfig['menuKey'] ?? null;
    $menuValue       = $moduleConfig['menuValue'] ?? null;
    
    $cols            = $moduleConfig['cols'] ?? [];
    $col_delete_time = $cols['delete_time'] ?? 'd_delete_time';
    $col_sort        = $cols['sort'] ?? 'd_sort';
    
    // 分類欄位 (處理還原後的排序範圍)
    $categoryField   = $moduleConfig['listPage']['categoryField'] ?? null;
    
    if (empty($col_delete_time)) {
        echo json_encode(['success' => false, 'message' => 'This module does not support trash feature']);
        exit;
    }
    // ---------------------------------------------------------------------

    $conn->beginTransaction();
    
    // 1. 獲取該項目的資訊
    $getItemSql = "SELECT * FROM {$tableName} WHERE {$col_id} = :item_id";
    $stmt = $conn->prepare($getItemSql);
    $stmt->execute([':item_id' => $itemId]);
    $itemRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$itemRow) {
        throw new Exception('找不到該項目資料');
    }

    // 2. 執行還原：僅將刪除時間設為 NULL (保持原排序,不影響其他資料)
    $restoreQuery = "UPDATE {$tableName} SET 
                     {$col_delete_time} = NULL 
                     WHERE {$col_id} = :item_id";
    
    $stmt = $conn->prepare($restoreQuery);
    $stmt->execute([':item_id' => $itemId]);

    // 【新增】重新排序 (消除空缺/重複)
    if (!empty($col_sort)) {
        $sortWhere = "1=1";
        $sortParams = [];

        // 使用該項目的實際值進行精確範圍鎖定
        if ($menuKey) {
            $actualMenuKey = ($menuKey === 'd_class1' && $tableName === 'taxonomies') ? 'taxonomy_type_id' : $menuKey;
            if (isset($itemRow[$actualMenuKey])) {
                $sortWhere .= " AND `{$actualMenuKey}` = :menuValue";
                $sortParams[':menuValue'] = $itemRow[$actualMenuKey];
            }
        }

        if ($categoryField && isset($itemRow[$categoryField])) {
            $sortWhere .= " AND `{$categoryField}` = :cat";
            $sortParams[':cat'] = $itemRow[$categoryField];
        }

        if (isset($cols['parent_id']) && isset($itemRow[$cols['parent_id']])) {
            $sortWhere .= " AND `{$cols['parent_id']}` = :parent_id";
            $sortParams[':parent_id'] = $itemRow[$cols['parent_id']];
        }

        if (isset($itemRow['lang'])) {
            $sortWhere .= " AND `lang` = :lang";
            $sortParams[':lang'] = $itemRow['lang'];
        }

        // 排除置頂項目
        $col_top = $cols['top'] ?? null;
        if ($col_top) {
            $sortWhere .= " AND (`{$col_top}` = 0 OR `{$col_top}` IS NULL)";
        }

        $sortWhere .= " AND (`{$col_delete_time}` IS NULL)";

        SortHelper::reindex($conn, $tableName, $col_id, $col_sort, $sortWhere, $sortParams);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => '項目已還原'
    ]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
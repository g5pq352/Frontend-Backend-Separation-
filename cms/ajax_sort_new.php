<?php
/**
 * AJAX 排序處理 - 通用動態版
 * 自動讀取設定檔中的欄位名稱，支援無置頂欄位的資料表
 */
session_start();
require_once '../Connections/connect2data.php';

// 【除錯】開啟錯誤顯示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 【除錯】捕捉 Fatal Error
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        if (!headers_sent()) { header('Content-Type: application/json'); }
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message'] . ' on line ' . $error['line']]);
        exit;
    }
});
header('Content-Type: application/json');

// 啟用錯誤報告用於調試 (正式上線建議關閉 display_errors)
error_reporting(E_ALL);
ini_set('display_errors', 0); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$module     = $_POST['module'] ?? '';
$itemId     = intval($_POST['item_id'] ?? 0);
$newSort    = intval($_POST['new_sort'] ?? 0);
$categoryId = intval($_POST['category_id'] ?? 0);

if (!$module || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// 載入模組配置
$configFile = __DIR__ . "/set/{$module}Set.php";
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => "Module config not found: {$module}Set.php"]);
    exit;
}

// 載入設定檔，有些設定檔回傳 array，有些是用變數 $settingPage
$moduleConfig = require $configFile;
if (!is_array($moduleConfig) && isset($settingPage)) {
    $moduleConfig = $settingPage;
}

if (!is_array($moduleConfig)) {
    echo json_encode(['success' => false, 'message' => 'Invalid config format']);
    exit;
}

// ---------------------------------------------------------------------
// 【關鍵修改】動態欄位對應
// ---------------------------------------------------------------------
$tableName = $moduleConfig['tableName'];
$menuKey   = $moduleConfig['menuKey'] ?? null;
$menuValue = $moduleConfig['menuValue'] ?? null;

// 取得主鍵名稱 (例如 d_id 或 t_id)
$col_id    = $moduleConfig['primaryKey'];

// 取得自定義欄位設定
$cols      = $moduleConfig['cols'] ?? [];

// 定義排序與置頂欄位 (如果沒設定，預設為 d_ 開頭，但確保 null 被保留)
$col_sort  = array_key_exists('sort', $cols) ? $cols['sort'] : 'd_sort';
$col_top   = array_key_exists('top', $cols) ? $cols['top'] : 'd_top';

// 取得分類欄位 (如果有)
$categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
// ---------------------------------------------------------------------

try {
    $conn->beginTransaction();
    
    // 1. 獲取當前項目資訊 (使用動態主鍵)
    $getCurrentQuery = "SELECT * FROM {$tableName} WHERE {$col_id} = :item_id";
    $stmt = $conn->prepare($getCurrentQuery);
    $stmt->execute([':item_id' => $itemId]);
    $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentItem) {
        throw new Exception('Item not found');
    }
    
    // 2. 檢查置頂狀態 (移除檢查，允許置頂項目排序，因為置頂僅為視覺效果)
    /*
    if ($col_top !== null && isset($currentItem[$col_top])) {
        if ($currentItem[$col_top] == 1) {
            throw new Exception('置頂項目無法調整排序，請先取消置頂');
        }
    }
    */
    
    // 3. 建立查詢所有項目的條件
    // 基本條件（只在有 menuKey 時才加入）
    $whereClause = "1=1";
    $params = [];
    
    if ($menuKey && $menuValue !== null) {
        $whereClause = "{$menuKey} = :menuValue";
        $params[':menuValue'] = $menuValue;
    }
    
    // 如果有置頂功能，只撈出「非置頂」的項目來排序
    // 這樣可以確保手動排序只在一群非置頂的項目中進行，不會影響到置頂項目的排序值
    if ($col_top !== null) {
        $whereClause .= " AND ({$col_top} = 0 OR {$col_top} IS NULL)";
    }
    
    // 【階層導航】如果有 parent_id 欄位，需要過濾同一層級
    $parentIdField = $cols['parent_id'] ?? null;
    if ($parentIdField && isset($currentItem[$parentIdField])) {
        $whereClause .= " AND {$parentIdField} = :parent_id";
        $params[':parent_id'] = $currentItem[$parentIdField];
    }
    
    // 分類過濾 (如果有的話)
    if ($categoryField && isset($currentItem[$categoryField])) {
        // 有些表分類可能是 0 或 NULL，需小心處理
        $itemCategory = $currentItem[$categoryField];
        $whereClause .= " AND {$categoryField} = :item_category";
        $params[':item_category'] = $itemCategory;
    }
    
    // 4. 取得舊的排序值
    $oldSort = intval($currentItem[$col_sort]);
    
    if ($oldSort === $newSort) {
        throw new Exception('目標排序值與原排序值相同，無需更新');
    }

    // 5. 執行 SQL 範圍位移 (Range Shift)
    // 這是高效能關鍵：只需 2 次 SQL 指令即可處理百萬級資料
    if ($newSort < $oldSort) {
        // [移動向上] 範例：從 10 移到 5
        // 將 5 到 9 之間的項目全部 +1 (往下擠)
        $shiftQuery = "UPDATE {$tableName} 
                       SET {$col_sort} = {$col_sort} + 1 
                       WHERE {$whereClause} 
                       AND {$col_sort} >= :new_sort 
                       AND {$col_sort} < :old_sort";
    } else {
        // [移動向下] 範例：從 5 移到 10
        // 將 6 到 10 之間的項目全部 -1 (往上擠)
        $shiftQuery = "UPDATE {$tableName} 
                       SET {$col_sort} = {$col_sort} - 1 
                       WHERE {$whereClause} 
                       AND {$col_sort} > :old_sort 
                       AND {$col_sort} <= :new_sort";
    }

    $stmt = $conn->prepare($shiftQuery);
    $shiftParams = $params;
    $shiftParams[':new_sort'] = $newSort;
    $shiftParams[':old_sort'] = $oldSort;
    $stmt->execute($shiftParams);

    // 6. 更新目標項目的新排序值
    $updateTargetQuery = "UPDATE {$tableName} SET {$col_sort} = :new_sort WHERE {$col_id} = :id";
    $stmt = $conn->prepare($updateTargetQuery);
    $stmt->execute([
        ':new_sort' => $newSort,
        ':id'       => $itemId
    ]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => '排序更新成功 (高效模式)']);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Sort Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
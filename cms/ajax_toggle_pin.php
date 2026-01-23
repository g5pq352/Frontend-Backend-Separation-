<?php
/**
 * AJAX 置頂切換處理 - 高效能優化版
 * 優化目標：5ms 內完成置頂切換
 */

require_once __DIR__ . '/auth_check.php';
requireCmsAuth();

require_once '../Connections/connect2data.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$module = $_POST['module'] ?? '';
$itemId = (int)($_POST['item_id'] ?? 0);

if (!$module || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// 快速載入模組配置
static $configCache = [];
if (!isset($configCache[$module])) {
    $configFile = __DIR__ . "/set/{$module}Set.php";
    if (!file_exists($configFile)) {
        echo json_encode(['success' => false, 'message' => 'Config not found']);
        exit;
    }
    $moduleConfig = require $configFile;
    if (!is_array($moduleConfig) && isset($settingPage)) {
        $moduleConfig = $settingPage;
    }
    $configCache[$module] = $moduleConfig;
} else {
    $moduleConfig = $configCache[$module];
}

$tableName = $moduleConfig['tableName'];
$col_id = $moduleConfig['primaryKey'];
$cols = $moduleConfig['cols'] ?? [];
$col_top = $cols['top'] ?? 'd_top';
$col_sort = $cols['sort'] ?? 'd_sort';
$categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
$menuKey = $moduleConfig['menuKey'] ?? null;
$menuValue = $moduleConfig['menuValue'] ?? null;

try {
    $conn->beginTransaction();

    // 1. 快速獲取當前狀態（單次查詢，只取需要的欄位）
    $selectFields = "{$col_top}, {$col_sort}";
    if ($categoryField) {
        $selectFields .= ", {$categoryField}";
    }

    $stmt = $conn->prepare("SELECT {$selectFields} FROM {$tableName} WHERE {$col_id} = ? LIMIT 1");
    $stmt->execute([$itemId]);
    $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentItem) {
        throw new Exception('Item not found');
    }

    $currentTop = (int)($currentItem[$col_top] ?? 0);
    $currentSort = (int)$currentItem[$col_sort];
    $newTop = $currentTop ? 0 : 1;

    // 2. 更新置頂狀態（單次 UPDATE）
    $stmt = $conn->prepare("UPDATE {$tableName} SET {$col_top} = ? WHERE {$col_id} = ?");
    $stmt->execute([$newTop, $itemId]);

    // 3. 調整其他項目的排序（單次 UPDATE）
    $whereConditions = ["{$col_top} = 0"];
    $params = [];

    if ($menuKey && $menuValue !== null) {
        $whereConditions[] = "{$menuKey} = ?";
        $params[] = $menuValue;
    }

    if ($categoryField && isset($currentItem[$categoryField])) {
        $whereConditions[] = "{$categoryField} = ?";
        $params[] = $currentItem[$categoryField];
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    if ($newTop == 1) {
        // 設定置頂：後面的項目往前補位
        $sql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} - 1
                {$whereClause} AND {$col_sort} > ?";
        $params[] = $currentSort;
    } else {
        // 取消置頂：後面的項目往後讓位
        $sql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} + 1
                {$whereClause} AND {$col_sort} >= ? AND {$col_id} != ?";
        $params[] = $currentSort;
        $params[] = $itemId;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $newTop ? '已置頂' : '已取消置頂',
        'is_pinned' => $newTop
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
/**
 * AJAX Endpoint: Check for Child Categories
 * 檢查分類是否有子分類（通用，支援所有階層式模組）
 */
session_start();
require_once '../Connections/connect2data.php';

// 設定 JSON 回應標頭
header('Content-Type: application/json');

// 檢查必要參數
if (!isset($_GET['module']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// 安全過濾輸入
$module = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['module']);
$id = intval($_GET['id']);

try {
    // 載入模組配置
    $configFile = __DIR__ . "/set/{$module}Set.php";
    if (!file_exists($configFile)) {
        echo json_encode(['error' => 'Module not found']);
        exit;
    }

    $moduleConfig = require $configFile;
    
    // 檢查是否為 array，如果是舊格式則使用 $settingPage
    if (!is_array($moduleConfig) && isset($settingPage)) {
        $moduleConfig = $settingPage;
    }
    
    $tableName = $moduleConfig['tableName'];
    $cols = $moduleConfig['cols'] ?? [];
    $parentIdField = $cols['parent_id'] ?? null;

    // 如果沒有 parent_id 欄位，表示不是階層式結構
    if (!$parentIdField) {
        echo json_encode(['hasChildren' => false, 'count' => 0]);
        exit;
    }

    // 查詢子分類數量
    $query = "SELECT COUNT(*) as child_count FROM {$tableName} WHERE {$parentIdField} = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // 回傳結果
    echo json_encode([
        'hasChildren' => $result['child_count'] > 0,
        'count' => (int)$result['child_count']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

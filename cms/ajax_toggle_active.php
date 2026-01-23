<?php
/**
 * AJAX 狀態切換處理 - 高效能優化版
 * 優化目標：5ms 內完成狀態切換
 * 支援狀態：0=不顯示, 1=顯示, 2=草稿
 */

require_once __DIR__ . '/auth_check.php';
requireCmsAuth();

require_once('../Connections/connect2data.php');

header('Content-Type: application/json');
ini_set('display_errors', 0);

try {
    $module = $_POST['module'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    $newValue = (int)($_POST['new_value'] ?? 0);

    if (empty($module) || $itemId <= 0) {
        throw new Exception('缺少必要參數');
    }

    // 快速載入模組配置
    static $configCache = [];
    if (!isset($configCache[$module])) {
        $configFile = __DIR__ . "/set/{$module}Set.php";
        if (!file_exists($configFile)) {
            throw new Exception('找不到模組配置檔案');
        }
        $moduleConfig = require $configFile;
        $configCache[$module] = $moduleConfig;
    } else {
        $moduleConfig = $configCache[$module];
    }

    $tableName = $moduleConfig['tableName'];
    $primaryKey = $moduleConfig['primaryKey'];
    $col_active = $moduleConfig['cols']['active'] ?? 'd_active';

    // 單次 UPDATE 更新狀態
    $stmt = $conn->prepare("UPDATE {$tableName} SET {$col_active} = ? WHERE {$primaryKey} = ?");
    $stmt->execute([$newValue, $itemId]);

    echo json_encode([
        'success' => true,
        'message' => '狀態已更新'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

<?php
/**
 * 測試 AJAX 排序功能
 */

// 設置環境變數
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['module'] = 'portfolio';
$_POST['item_id'] = 3;
$_POST['new_sort'] = 2;
$_POST['category_id'] = 0;

// 模擬登入狀態
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'test';

// 執行 AJAX 腳本
ob_start();
$startTime = microtime(true);

try {
    include 'ajax_sort.php';
    $output = ob_get_clean();
    $endTime = microtime(true);

    $executionTime = ($endTime - $startTime) * 1000;

    echo "執行時間: " . number_format($executionTime, 2) . " ms\n";
    echo "回應內容: " . $output . "\n";

    $response = json_decode($output, true);
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✓ 測試成功！\n";
        } else {
            echo "✗ 測試失敗: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ 無效的 JSON 回應\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ 發生錯誤: " . $e->getMessage() . "\n";
}

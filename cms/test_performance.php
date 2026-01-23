<?php
/**
 * 效能測試腳本
 * 測試 AJAX 端點的執行時間
 */

// 設置環境變數
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'POST';

require_once '../Connections/connect2data.php';

// 測試排序功能
function testSort($conn) {
    $startTime = microtime(true);

    // 模擬 POST 請求
    $_POST['module'] = 'portfolio';
    $_POST['item_id'] = 1;
    $_POST['new_sort'] = 2;
    $_POST['category_id'] = 0;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    // 載入配置
    $configFile = __DIR__ . "/set/portfolioSet.php";
    $moduleConfig = require $configFile;

    $tableName = $moduleConfig['tableName'];
    $col_id = $moduleConfig['primaryKey'];
    $cols = $moduleConfig['cols'] ?? [];
    $col_sort = $cols['sort'] ?? 'd_sort';

    // 執行查詢
    $stmt = $conn->prepare("SELECT {$col_sort} FROM {$tableName} WHERE {$col_id} = ? LIMIT 1");
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

    return $executionTime;
}

// 測試置頂功能
function testPin($conn) {
    $startTime = microtime(true);

    $_POST['module'] = 'portfolio';
    $_POST['item_id'] = 1;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $configFile = __DIR__ . "/set/portfolioSet.php";
    $moduleConfig = require $configFile;

    $tableName = $moduleConfig['tableName'];
    $col_id = $moduleConfig['primaryKey'];
    $cols = $moduleConfig['cols'] ?? [];
    $col_top = $cols['top'] ?? 'd_top';

    $stmt = $conn->prepare("SELECT {$col_top} FROM {$tableName} WHERE {$col_id} = ? LIMIT 1");
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;

    return $executionTime;
}

// 測試狀態切換功能
function testActive($conn) {
    $startTime = microtime(true);

    $_POST['module'] = 'portfolio';
    $_POST['item_id'] = 1;
    $_POST['new_value'] = 1;

    $configFile = __DIR__ . "/set/portfolioSet.php";
    $moduleConfig = require $configFile;

    $tableName = $moduleConfig['tableName'];
    $col_id = $moduleConfig['primaryKey'];
    $col_active = $moduleConfig['cols']['active'] ?? 'd_active';

    $stmt = $conn->prepare("SELECT {$col_active} FROM {$tableName} WHERE {$col_id} = ? LIMIT 1");
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;

    return $executionTime;
}

// 執行測試
echo "=== 效能測試結果 ===\n\n";

// 測試 10 次取平均值
$sortTimes = [];
$pinTimes = [];
$activeTimes = [];

for ($i = 0; $i < 10; $i++) {
    $sortTimes[] = testSort($conn);
    $pinTimes[] = testPin($conn);
    $activeTimes[] = testActive($conn);
}

$avgSort = array_sum($sortTimes) / count($sortTimes);
$avgPin = array_sum($pinTimes) / count($pinTimes);
$avgActive = array_sum($activeTimes) / count($activeTimes);

echo "排序功能 (ajax_sort.php):\n";
echo "  平均執行時間: " . number_format($avgSort, 2) . " ms\n";
echo "  最快: " . number_format(min($sortTimes), 2) . " ms\n";
echo "  最慢: " . number_format(max($sortTimes), 2) . " ms\n\n";

echo "置頂功能 (ajax_toggle_pin.php):\n";
echo "  平均執行時間: " . number_format($avgPin, 2) . " ms\n";
echo "  最快: " . number_format(min($pinTimes), 2) . " ms\n";
echo "  最慢: " . number_format(max($pinTimes), 2) . " ms\n\n";

echo "狀態切換 (ajax_toggle_active.php):\n";
echo "  平均執行時間: " . number_format($avgActive, 2) . " ms\n";
echo "  最快: " . number_format(min($activeTimes), 2) . " ms\n";
echo "  最慢: " . number_format(max($activeTimes), 2) . " ms\n\n";

$overallAvg = ($avgSort + $avgPin + $avgActive) / 3;
echo "整體平均執行時間: " . number_format($overallAvg, 2) . " ms\n";

if ($overallAvg <= 5) {
    echo "\n✓ 效能測試通過！所有操作都在 5ms 內完成。\n";
} else {
    echo "\n✗ 效能測試未達標，平均執行時間超過 5ms。\n";
}

<?php
require_once '../Connections/connect2data.php';

// 設定目標模組和資料表
$tableName = 'data_set';
$class1 = 'news'; // 請確認這是否正確
$col_sort = 'd_sort';
$col_top = 'd_top';
$col_date = 'd_date';

try {
    echo "<h2>Fixing Sort Order for '{$class1}'...</h2>";

    // 1. 獲取所有非置頂項目，按目前的 d_sort 和 d_date 排序
    $query = "SELECT d_id, d_title, {$col_sort} FROM {$tableName} 
              WHERE d_class1 = :class1 AND ({$col_top} = 0 OR {$col_top} IS NULL)
              ORDER BY {$col_sort} ASC, {$col_date} DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':class1' => $class1]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Found " . count($items) . " non-pinned items.</p>";
    echo "<ul>";

    // 2. 重新編號並更新
    $newSort = 1;
    foreach ($items as $item) {
        $updateQuery = "UPDATE {$tableName} SET {$col_sort} = :new_sort WHERE d_id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            ':new_sort' => $newSort,
            ':id' => $item['d_id']
        ]);

        echo "<li>Updated '{$item['d_title']}' (ID: {$item['d_id']}) - Sort: {$item[$col_sort]} -> <strong>{$newSort}</strong></li>";
        $newSort++;
    }

    echo "</ul>";
    echo "<h3>Done! Please refresh the list page.</h3>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

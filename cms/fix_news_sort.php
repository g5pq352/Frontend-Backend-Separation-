<?php
require_once '../Connections/connect2data.php';

// 1. Get all News items
// Assuming d_class1 = 'news' as per newsSet.php
$sql = "SELECT d_id, d_title, d_top, d_sort, d_date FROM data_set WHERE d_class1 = 'news' ORDER BY d_top DESC, d_sort ASC, d_date DESC";
$stmt = $conn->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total items: " . count($rows) . "<br>";

$pinned = [];
$normal = [];

foreach ($rows as $row) {
    if ($row['d_top'] == 1) {
        $pinned[] = $row;
    } else {
        $normal[] = $row;
    }
}

echo "Pinned: " . count($pinned) . "<br>";
echo "Normal: " . count($normal) . "<br>";

// 2. Re-index Normal items
echo "<h3>Re-indexing Normal Items</h3>";
$i = 1;
foreach ($normal as $row) {
    echo "ID: {$row['d_id']} ({$row['d_title']}) - Old Sort: {$row['d_sort']} -> New Sort: {$i}<br>";
    
    // Update DB
    $update = $conn->prepare("UPDATE data_set SET d_sort = :sort WHERE d_id = :id");
    $update->execute([':sort' => $i, ':id' => $row['d_id']]);
    
    $i++;
}

echo "<h3>Pinned Items (Unchanged or Re-indexed locally?)</h3>";
// We don't necessarily need to re-index pinned items, but to be clean, let's keep them as is or re-index them?
// The user message suggests "Original Sort: 2", so maybe keep them.
// But if they were sorting manually before pinning, maybe just leave them.
foreach ($pinned as $row) {
    echo "ID: {$row['d_id']} ({$row['d_title']}) - Sort: {$row['d_sort']} (Skipped)<br>";
}

echo "<hr>Done.";
?>

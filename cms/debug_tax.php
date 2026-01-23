<?php
require_once '../Connections/connect2data.php';

echo "<h2>Taxonomy Types</h2>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Key</th></tr>";
$stmt = $conn->query("SELECT * FROM taxonomy_types");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['key_name']}</td>"; // 假設有 key 欄位
    echo "</tr>";
}
echo "</table>";

echo "<h2>First 10 Rows from 'taxonomies'</h2>";
echo "<table border='1'><tr><th>ID</th><th>DB Value (menuValue)</th><th>Name</th><th>Date</th></tr>";
$stmt = $conn->query("SELECT t_id, taxonomy_type_id, t_name, created_at FROM taxonomies LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['t_id']}</td>";
    echo "<td>{$row['taxonomy_type_id']}</td>";
    echo "<td>{$row['t_name']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";
?>

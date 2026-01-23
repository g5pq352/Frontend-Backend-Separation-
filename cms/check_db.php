<?php
require_once '../Connections/connect2data.php';

echo "<h2>Current Database Values</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>d_top</th><th>d_sort</th><th>d_date</th></tr>";

$query = "SELECT d_id, d_title, d_top, d_sort, d_date FROM data_set WHERE d_class1 = 'news' ORDER BY d_top DESC, d_sort ASC";
$stmt = $conn->query($query);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['d_id']}</td>";
    echo "<td>{$row['d_title']}</td>";
    echo "<td>{$row['d_top']}</td>";
    echo "<td><strong>{$row['d_sort']}</strong></td>";
    echo "<td>{$row['d_date']}</td>";
    echo "</tr>";
}
echo "</table>";
?>

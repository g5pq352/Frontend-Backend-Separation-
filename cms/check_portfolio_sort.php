<?php
require_once('../Connections/connect2data.php');
$stmt = $conn->query("SELECT d_id, d_title, d_sort, d_top, d_date FROM data_set WHERE d_class1 = 'portfolio' ORDER BY d_top DESC, d_sort ASC, d_date DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

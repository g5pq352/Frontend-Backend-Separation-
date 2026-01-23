<?php
require_once('../Connections/connect2data.php');
$stmt = $conn->query("DESC taxonomies");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $cols);
?>

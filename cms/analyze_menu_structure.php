<?php
require_once('../Connections/connect2data.php');

echo "<h1>選單結構分析</h1>";

// 查詢所有選單
$stmt = $conn->query("
    SELECT 
        m1.menu_id,
        m1.menu_title,
        m1.menu_type,
        m1.menu_parent_id,
        m1.menu_link,
        m2.menu_title as parent_title
    FROM cms_menus m1
    LEFT JOIN cms_menus m2 ON m1.menu_parent_id = m2.menu_id
    WHERE m1.menu_active = 1
    ORDER BY m1.menu_parent_id, m1.menu_sort
");

$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 分類顯示
$parentMenus = [];
$childMenus = [];

foreach ($menus as $menu) {
    if ($menu['menu_parent_id'] == 0) {
        $parentMenus[] = $menu;
    } else {
        if (!isset($childMenus[$menu['menu_parent_id']])) {
            $childMenus[$menu['menu_parent_id']] = [];
        }
        $childMenus[$menu['menu_parent_id']][] = $menu;
    }
}

echo "<h2>主選單（父選單）：</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>名稱</th><th>連結</th><th>子選單數量</th></tr>";
foreach ($parentMenus as $parent) {
    $childCount = count($childMenus[$parent['menu_id']] ?? []);
    echo "<tr>";
    echo "<td>{$parent['menu_id']}</td>";
    echo "<td><strong>{$parent['menu_title']}</strong></td>";
    echo "<td>{$parent['menu_link']}</td>";
    echo "<td>{$childCount}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>完整階層結構：</h2>";
foreach ($parentMenus as $parent) {
    echo "<h3>📁 {$parent['menu_title']} (ID: {$parent['menu_id']})</h3>";
    echo "<ul>";
    
    if (isset($childMenus[$parent['menu_id']])) {
        foreach ($childMenus[$parent['menu_id']] as $child) {
            echo "<li>📄 {$child['menu_title']} (ID: {$child['menu_id']}, Type: {$child['menu_type']})</li>";
        }
    } else {
        echo "<li><em>沒有子選單</em></li>";
    }
    
    echo "</ul>";
}

echo "<h2>建議的權限結構：</h2>";
echo "<ul>";
echo "<li><strong>主選單</strong>：只有「顯示/隱藏」權限（1 個欄位）</li>";
echo "<li><strong>子選單</strong>：有「檢視、新增、修改、刪除」權限（4 個欄位）</li>";
echo "</ul>";
?>

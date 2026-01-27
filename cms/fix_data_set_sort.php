<?php
/**
 * 修復 data_set 資料表的排序編號
 * 針對每個模組（d_class1）和分類（d_class2）組合，重新整理排序
 */

session_start();
require_once '../Connections/connect2data.php';
require_once(__DIR__ . '/includes/SortReorganizer.php');

// 檢查是否為管理員
if (!isset($_SESSION['admin'])) {
    die('請先登入管理後台');
}

echo "<h1>修復 data_set 排序編號</h1>";
echo "<pre>";

try {
    $conn->beginTransaction();

    // 1. 找出所有的模組（d_class1）
    $modulesQuery = "SELECT DISTINCT d_class1 FROM data_set WHERE d_delete_time IS NULL ORDER BY d_class1";
    $modulesStmt = $conn->query($modulesQuery);
    $modules = $modulesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo "找到 " . count($modules) . " 個模組\n\n";

    $totalUpdated = 0;

    foreach ($modules as $module) {
        echo "處理模組: {$module}\n";
        echo str_repeat('-', 50) . "\n";

        // 2. 找出該模組下所有的分類（d_class2）
        $categoriesQuery = "SELECT DISTINCT d_class2 FROM data_set
                           WHERE d_class1 = :module
                           AND d_delete_time IS NULL
                           AND d_class2 IS NOT NULL
                           AND d_class2 != ''
                           AND d_class2 != '0'
                           ORDER BY d_class2";
        $categoriesStmt = $conn->prepare($categoriesQuery);
        $categoriesStmt->execute([':module' => $module]);
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($categories)) {
            echo "  └─ 沒有分類，跳過\n\n";
            continue;
        }

        echo "  找到 " . count($categories) . " 個分類\n";

        // 3. 針對每個分類重新整理排序
        foreach ($categories as $categoryId) {
            // 查詢該分類的名稱（從 taxonomies 表）
            $catNameQuery = "SELECT t_name FROM taxonomies WHERE t_id = :id LIMIT 1";
            $catNameStmt = $conn->prepare($catNameQuery);
            $catNameStmt->execute([':id' => $categoryId]);
            $catName = $catNameStmt->fetchColumn();

            echo "  ├─ 分類 ID: {$categoryId}";
            if ($catName) {
                echo " ({$catName})";
            }

            // 查詢該分類下所有未刪除的資料
            $itemsQuery = "SELECT d_id, d_sort, d_title FROM data_set
                          WHERE d_class1 = :module
                          AND d_class2 = :category
                          AND d_delete_time IS NULL
                          ORDER BY d_sort ASC, d_id ASC";
            $itemsStmt = $conn->prepare($itemsQuery);
            $itemsStmt->execute([
                ':module' => $module,
                ':category' => $categoryId
            ]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                echo " - 無資料\n";
                continue;
            }

            echo " - 共 " . count($items) . " 筆資料\n";

            // 重新分配排序編號
            $newSortNum = 1;
            foreach ($items as $item) {
                $oldSort = $item['d_sort'];

                // 只有當排序編號不正確時才更新
                if ($oldSort != $newSortNum) {
                    $updateSql = "UPDATE data_set SET d_sort = :newSort WHERE d_id = :id";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->execute([
                        ':newSort' => $newSortNum,
                        ':id' => $item['d_id']
                    ]);

                    echo "      └─ ID {$item['d_id']}: {$oldSort} → {$newSortNum} ({$item['d_title']})\n";
                    $totalUpdated++;
                }

                $newSortNum++;
            }
        }

        echo "\n";
    }

    // 4. 處理沒有分類的資料（d_class2 為 NULL 或 0）
    echo "處理沒有分類的資料\n";
    echo str_repeat('-', 50) . "\n";

    foreach ($modules as $module) {
        $itemsQuery = "SELECT d_id, d_sort, d_title FROM data_set
                      WHERE d_class1 = :module
                      AND (d_class2 IS NULL OR d_class2 = '' OR d_class2 = '0')
                      AND d_delete_time IS NULL
                      ORDER BY d_sort ASC, d_id ASC";
        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([':module' => $module]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            continue;
        }

        echo "模組 {$module} - 共 " . count($items) . " 筆無分類資料\n";

        $newSortNum = 1;
        foreach ($items as $item) {
            $oldSort = $item['d_sort'];

            if ($oldSort != $newSortNum) {
                $updateSql = "UPDATE data_set SET d_sort = :newSort WHERE d_id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':newSort' => $newSortNum,
                    ':id' => $item['d_id']
                ]);

                echo "  └─ ID {$item['d_id']}: {$oldSort} → {$newSortNum} ({$item['d_title']})\n";
                $totalUpdated++;
            }

            $newSortNum++;
        }
    }

    $conn->commit();

    echo "\n";
    echo str_repeat('=', 50) . "\n";
    echo "修復完成！共更新 {$totalUpdated} 筆資料\n";
    echo str_repeat('=', 50) . "\n";

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "\n錯誤: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='javascript:history.back()'>返回</a>";
?>

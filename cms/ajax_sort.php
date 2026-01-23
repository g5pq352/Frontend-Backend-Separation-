<?php
/**
 * AJAX 排序處理 - 高效能優化版
 * 優化目標：5ms 內完成排序更新
 */

// 載入認證檢查
require_once __DIR__ . '/auth_check.php';
requireCmsAuth();

require_once '../Connections/connect2data.php';

header('Content-Type: application/json');
ini_set('display_errors', 0); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$module     = $_POST['module'] ?? '';
$itemId     = intval($_POST['item_id'] ?? 0);
$newSort    = intval($_POST['new_sort'] ?? 0);
$categoryId = intval($_POST['category_id'] ?? 0);

if (!$module || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// 快速載入模組配置（使用靜態緩存）
static $configCache = [];
if (!isset($configCache[$module])) {
    $configFile = __DIR__ . "/set/{$module}Set.php";
    if (!file_exists($configFile)) {
        echo json_encode(['success' => false, 'message' => 'Config not found']);
        exit;
    }
    $moduleConfig = require $configFile;
    if (!is_array($moduleConfig) && isset($settingPage)) {
        $moduleConfig = $settingPage;
    }
    $configCache[$module] = $moduleConfig;
} else {
    $moduleConfig = $configCache[$module];
}

// 快速提取配置
$tableName = $moduleConfig['tableName'];
$primaryKey = $moduleConfig['primaryKey'];
$cols = $moduleConfig['cols'] ?? [];
$col_sort = array_key_exists('sort', $cols) ? $cols['sort'] : 'd_sort';
$col_top = array_key_exists('top', $cols) ? $cols['top'] : 'd_top';
$menuKey = $moduleConfig['menuKey'] ?? null;
$menuValue = $moduleConfig['menuValue'] ?? null;
$categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
$parentIdField = $cols['parent_id'] ?? null;

// 載入 Map Helper
require_once __DIR__ . '/includes/taxonomyMapHelper.php';

try {
    $conn->beginTransaction();

    // 1. 取得當前項目資料
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE {$primaryKey} = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) throw new Exception('找不到資料');

    // 2. 判斷是否使用 Map Table 排序 (預設改為 false,除非配置明確啟用)
    $useMapTableSort = false;
    $configUseTaxonomyMapSort = $moduleConfig['listPage']['useTaxonomyMapSort'] ?? false;
    if ($categoryId > 0 && hasTaxonomyMapTable($conn) && $configUseTaxonomyMapSort) {
        $useMapTableSort = true;
    }

    // 3. 檢查置頂項目是否可排序
    $isPinned = false;
    if ($useMapTableSort) {
        $checkMapTop = $conn->prepare("SELECT d_top FROM data_taxonomy_map WHERE d_id = ? AND t_id = ?");
        $checkMapTop->execute([$itemId, $categoryId]);
        $mapRow = $checkMapTop->fetch(PDO::FETCH_ASSOC);
        if ($mapRow && ($mapRow['d_top'] ?? 0) == 1) $isPinned = true;
    } else {
        $isGlobalSort = ($categoryId == 0);
        if ($isGlobalSort && $col_top && ($item[$col_top] ?? 0) == 1) $isPinned = true;
    }

    if ($isPinned) throw new Exception('置頂項目無法調整排序');

    // 4. 執行排序邏輯
    if ($useMapTableSort) {
        $mapStmt = $conn->prepare("SELECT sort_num FROM data_taxonomy_map WHERE d_id = ? AND t_id = ?");
        $mapStmt->execute([$itemId, $categoryId]);
        $mapRow = $mapStmt->fetch(PDO::FETCH_ASSOC);
        if (!$mapRow) throw new Exception('找不到分類關聯');

        $oldSort = (int)$mapRow['sort_num'];
        if ($oldSort === $newSort) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => '无需更新']);
            exit;
        }

        if ($newSort < $oldSort) {
            $shiftSql = "UPDATE data_taxonomy_map SET sort_num = sort_num + 1 
                        WHERE t_id = ? AND sort_num >= ? AND sort_num < ? AND (d_top = 0 OR d_top IS NULL)";
            $shiftParams = [$categoryId, $newSort, $oldSort];
        } else {
            $shiftSql = "UPDATE data_taxonomy_map SET sort_num = sort_num - 1 
                        WHERE t_id = ? AND sort_num > ? AND sort_num <= ? AND (d_top = 0 OR d_top IS NULL)";
            $shiftParams = [$categoryId, $oldSort, $newSort];
        }
        $conn->prepare($shiftSql)->execute($shiftParams);

        $conn->prepare("UPDATE data_taxonomy_map SET sort_num = ? WHERE d_id = ? AND t_id = ?")
             ->execute([$newSort, $itemId, $categoryId]);

    } else {
        $oldSort = (int)$item[$col_sort];
        if ($oldSort === $newSort) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => '无需更新']);
            exit;
        }

        $where = ["1=1"];
        $params = [];

        // 【優化】更嚴謹的範圍鎖定，確保不影響其他分類或類型的排序
        if ($menuKey) {
            $actualMenuKey = ($menuKey === 'd_class1' && $tableName === 'taxonomies') ? 'taxonomy_type_id' : $menuKey;
            if (isset($item[$actualMenuKey])) {
                $where[] = "{$actualMenuKey} = ?";
                $params[] = $item[$actualMenuKey];
            }
        }

        if ($col_top) {
            $where[] = "({$col_top} = 0 OR {$col_top} IS NULL)";
        }

        if ($categoryField && isset($item[$categoryField])) {
            $where[] = "{$categoryField} = ?";
            $params[] = $item[$categoryField];
        }

        if ($parentIdField && isset($item[$parentIdField])) {
            $where[] = "{$parentIdField} = ?";
            $params[] = $item[$parentIdField];
        }

        if (isset($item['lang'])) {
            $where[] = "lang = ?";
            $params[] = $item['lang'];
        }

        // 軟刪除過濾
        $trashCol = $moduleConfig['cols']['delete_time'] ?? null;
        if (!$trashCol) {
            foreach (['d_delete_time', 'deleted_at', 'delete_time'] as $tc) {
                if (array_key_exists($tc, $item)) { $trashCol = $tc; break; }
            }
        }
        if ($trashCol) {
            $where[] = "{$trashCol} IS NULL";
        }

        $whereSql = implode(' AND ', $where);

        if ($newSort < $oldSort) {
            $shiftSql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} + 1 
                        WHERE {$whereSql} AND {$col_sort} >= ? AND {$col_sort} < ?";
            $shiftParams = array_merge($params, [$newSort, $oldSort]);
        } else {
            $shiftSql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} - 1 
                        WHERE {$whereSql} AND {$col_sort} > ? AND {$col_sort} <= ?";
            $shiftParams = array_merge($params, [$oldSort, $newSort]);
        }
        $conn->prepare($shiftSql)->execute($shiftParams);

        $conn->prepare("UPDATE {$tableName} SET {$col_sort} = ? WHERE {$primaryKey} = ?")
             ->execute([$newSort, $itemId]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => '排序已更新']);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
/**
 * Generic List Page
 * 通用列表頁面 - 完全客製化欄位名稱版本
 */

require_once('../Connections/connect2data.php');
require_once('../config/config.php');
require_once 'auth.php';

// 載入 Element 模組
require_once(__DIR__ . '/includes/elements/ModuleConfigElement.php');
require_once(__DIR__ . '/includes/elements/PermissionElement.php');

// 載入其他輔助函數
require_once(__DIR__ . '/includes/permissionCheck.php');
require_once(__DIR__ . '/includes/categoryHelper.php');
require_once(__DIR__ . '/includes/buttonElement.php');
require_once(__DIR__ . '/includes/SortCountHelper.php');

// 獲取模組名稱
$module = $_GET['module'] ?? '';

try {
    // 載入模組配置（使用 Element）
    $moduleConfig = ModuleConfigElement::loadConfig($module);

    // 檢查使用者對此模組的權限（使用 Element）
    list($canView, $canAdd, $canEdit, $canDelete) = PermissionElement::checkModulePermission($conn, $module);

    // 要求檢視權限
    PermissionElement::requireViewPermission($canView);

} catch (Exception $e) {
    die($e->getMessage());
}

$menu_is = $moduleConfig['module'];
$_SESSION['nowMenu'] = $menu_is;

// 設定每頁顯示筆數與當前頁碼
$maxRows = $moduleConfig['listPage']['itemsPerPage'] ?? 20;
$pageNum = isset($_GET['pageNum']) ? (int) $_GET['pageNum'] : 0;
$startRow = $pageNum * $maxRows;

// -----------------------------------------------------------------------
// 【關鍵修改】讀取設定檔中的欄位對應 (若沒設定則使用預設值 d_xxx)
// -----------------------------------------------------------------------
$tableName = $moduleConfig['tableName'];
$col_id = $moduleConfig['primaryKey'];
$primaryKey = $moduleConfig['primaryKey'];  // 【新增】供按鈕函數使用

// 嘗試從設定檔讀取 cols，如果沒有就用空陣列
$customCols = $moduleConfig['cols'] ?? [];

// 定義系統欄位變數 (優先使用設定檔，否則使用預設 d_ 開頭)
$col_date = array_key_exists('date', $customCols) ? $customCols['date'] : 'd_date';
$col_title = array_key_exists('title', $customCols) ? $customCols['title'] : 'd_title';
$col_sort = array_key_exists('sort', $customCols) ? $customCols['sort'] : 'd_sort';
$col_top = array_key_exists('top', $customCols) ? $customCols['top'] : 'd_top';
$col_active = array_key_exists('active', $customCols) ? $customCols['active'] : 'd_active';
$col_delete_time = array_key_exists('delete_time', $customCols) ? $customCols['delete_time'] : 'd_delete_time';
$col_read = array_key_exists('read', $customCols) ? $customCols['read'] : 'd_read'; // 新增
$col_reply = array_key_exists('reply', $customCols) ? $customCols['reply'] : 'd_reply'; // 新增回覆狀態
$col_file_fk = $customCols['file_fk'] ?? 'file_d_id';
// -----------------------------------------------------------------------

// 【新增】語系處理
$langField = 'lang';
$activeLanguages = $conn->query("SELECT * FROM languages WHERE l_active = 1 ORDER BY l_sort ASC, l_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$defaultLang = 'tw'; // 預設值
foreach ($activeLanguages as $al) {
    if ($al['l_is_default']) $defaultLang = $al['l_slug'];
}

// 優先順序：網址參數 > Session > 預設語系
$currentLang = $_GET['language'] ?? $_SESSION['editing_lang'] ?? $defaultLang;
$_SESSION['editing_lang'] = $currentLang;

// 3. 分類處理
$hasCategory = $moduleConfig['listPage']['hasCategory'] ?? false;
$categoryName = $hasCategory ? $moduleConfig['listPage']['categoryName'] : null;
$categoryField = $hasCategory ? $moduleConfig['listPage']['categoryField'] : null;
$selectedCategory = $hasCategory && isset($_GET['selected1']) ? (int) $_GET['selected1'] : null;

// 如果有分類，載入分類選項
$categories = [];
if ($hasCategory && $categoryName) {
    $categories = getCategoryOptions($categoryName, null, null, true);
}

// 建立查詢條件
$menuKey = $moduleConfig['menuKey'] ?? null;
$menuValue = $moduleConfig['menuValue'] ?? null;
$orderBy = $moduleConfig['listPage']['orderBy'] ?? "{$col_id} DESC";

// 判斷是否為回收桶模式
$isTrashMode = isset($_GET['trash']) && $_GET['trash'] == '1';

// 檢查資料表是否有回收桶欄位
// 【修正】如果 col_delete_time 為 null，直接設為 false
if ($col_delete_time === null || $col_delete_time === '') {
    $columnExists = false;
} else {
    $checkColumnQuery = "SHOW COLUMNS FROM {$tableName} LIKE '{$col_delete_time}'";
    $stmt = $conn->prepare($checkColumnQuery);
    $stmt->execute();
    $columnExists = ($stmt->rowCount() > 0);
}

// 檢查是否支援回收桶（設定優先於資料表檢測）
$hasTrashConfig = $moduleConfig['listPage']['hasTrash'] ?? null;
if ($hasTrashConfig === false) {
    $hasTrash = false;
    $isTrashMode = false; // 如果模組不支援回收桶，強制關閉回收桶模式
} else {
    $hasTrash = $columnExists ? true : false;
}

$hasTrashData = false;

if ($hasTrash) {
    // 【修改】檢查是否有語系欄位
    $checkLangColQuery = "SHOW COLUMNS FROM {$tableName} LIKE '{$langField}'";
    $langColStmt = $conn->prepare($checkLangColQuery);
    $langColStmt->execute();
    $hasLangField = ($langColStmt->rowCount() > 0);
    
    // 【修改】使用變數，並加入語系過濾
    $checkTrashDataQuery = "SELECT 1 FROM {$tableName} 
                            WHERE {$menuKey} = :menuValue 
                            AND {$col_delete_time} IS NOT NULL";
    
    // 如果有語系欄位且不是 languages 表，加入語系過濾
    if ($hasLangField && $tableName !== 'languages') {
        $checkTrashDataQuery .= " AND {$langField} = :currentLang";
    }
    
    $checkTrashDataQuery .= " LIMIT 1";

    $trashStmt = $conn->prepare($checkTrashDataQuery);
    $trashParams = [':menuValue' => $menuValue];
    
    if ($hasLangField && $tableName !== 'languages') {
        $trashParams[':currentLang'] = $currentLang;
    }
    
    $trashStmt->execute($trashParams);

    if ($trashStmt->fetchColumn()) {
        $hasTrashData = true;
    }
}

// 【階層導航】檢查是否有 parent_id 參數（用於階層式選單）
$parentId = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;
$hasHierarchy = $moduleConfig['listPage']['hasHierarchy'] ?? false;
$hasHierarchicalNav = $hasHierarchy && isset($moduleConfig['cols']['parent_id']);
$parentIdField = $customCols['parent_id'] ?? null;

// 5. 構建查詢條件
$conditions = [];
$params = [];

// 基本條件：模組過濾
if ($menuKey && $menuValue !== null) {
    if ($menuKey === 'd_class1' && $tableName === 'taxonomies') {
        // 特殊處理：taxonomies 表實際上是用 taxonomy_type_id
        $conditions[] = "{$tableName}.taxonomy_type_id = :menuValue";
    } else {
        $conditions[] = "{$tableName}.{$menuKey} = :menuValue";
    }
    $params[':menuValue'] = $menuValue;
}

// 【階層導航】如果支援階層且有 parent_id 參數，添加過濾條件
if ($hasHierarchicalNav && !$isTrashMode) {
    $parentCol = $moduleConfig['cols']['parent_id'];
    if ($parentId > 0) {
        $conditions[] = "{$tableName}.{$parentCol} = :parentId";
        $params[':parentId'] = $parentId;
    } else {
        // 如果支援階層但沒有指定 parent_id (或指定為0)，顯示頂層 (0 或 NULL)
        $conditions[] = "({$tableName}.{$parentCol} = 0 OR {$tableName}.{$parentCol} IS NULL)";
    }
}

// 【多語系】加入語系過濾 (排除 languages 表及其它不支援多語系的表)
// 【修正】也要檢查 languageEnabled 設定
$languageEnabled = $moduleConfig['languageEnabled'] ?? true;
if ($tableName !== 'languages' && $languageEnabled !== false) {
    // 檢查資料表是否有 lang 欄位
    $checkLangColQuery = "SHOW COLUMNS FROM {$tableName} LIKE '{$langField}'";
    $langColStmt = $conn->prepare($checkLangColQuery);
    $langColStmt->execute();
    if ($langColStmt->rowCount() > 0) {
        $conditions[] = "{$tableName}.{$langField} = :currentLang";
        $params[':currentLang'] = $currentLang;
    }
}

// 分類過濾 (垃圾桶模式下通常顯示全部，除非有特別選定)
if ($hasCategory && $selectedCategory && !$isTrashMode) {
    if ($categoryField) {
        // 【防呆】檢查該分類是否屬於當前語系，避免跨語系切換時顯示空白
        $isValidCategory = true;
        if ($tableName !== 'languages') {
            // 找出分類表 (從 cms_menus 找)
            $catCheckStmt = $conn->prepare("SELECT menu_table FROM cms_menus WHERE menu_type = :type LIMIT 1");
            $catCheckStmt->execute([':type' => $moduleConfig['listPage']['categoryName'] ?? '']);
            $catMenuRow = $catCheckStmt->fetch();
            
            if ($catMenuRow && !empty($catMenuRow['menu_table'])) {
                $cTable = $catMenuRow['menu_table'];
                $cPK = ($cTable === 'taxonomies') ? 't_id' : 'd_id';
                
                // 檢查欄位是否存在 (防呆)
                try {
                    $checkCCQuery = "SHOW COLUMNS FROM `{$cTable}` LIKE '{$langField}'";
                    $ccStmt = $conn->query($checkCCQuery);
                    if ($ccStmt && $ccStmt->rowCount() > 0) {
                        $verifyQuery = "SELECT COUNT(*) FROM `{$cTable}` WHERE {$cPK} = :cid AND lang = :lang";
                        $vStmt = $conn->prepare($verifyQuery);
                        $vStmt->execute([':cid' => $selectedCategory, ':lang' => $currentLang]);
                        if ($vStmt->fetchColumn() == 0) {
                            $isValidCategory = false; // 該語系下找不到此分類
                        }
                    }
                } catch (PDOException $e) {
                    // 如果查詢失敗（例如表不存在），忽略錯誤
                    error_log("Category validation error: " . $e->getMessage());
                }
            }
        }

        if ($isValidCategory) {
            // 【修改】使用 data_taxonomy_map 表進行多對多分類查詢
            // 檢查是否有 data_taxonomy_map 表（新系統）
            $checkMapTable = "SHOW TABLES LIKE 'data_taxonomy_map'";
            $mapTableStmt = $conn->query($checkMapTable);
            
            // 【新增】檢查設定檔是否啟用 useTaxonomyMapSort (預設為 true)
            $configUseTaxonomyMapSort = $moduleConfig['listPage']['useTaxonomyMapSort'] ?? true;
            
            if ($mapTableStmt && $mapTableStmt->rowCount() > 0 && $configUseTaxonomyMapSort) {
                // 使用 data_taxonomy_map 表（推薦）
                $conditions[] = "EXISTS (
                    SELECT 1 FROM data_taxonomy_map 
                    WHERE data_taxonomy_map.d_id = {$tableName}.{$col_id} 
                    AND data_taxonomy_map.t_id = :categoryId
                )";
                $params[':categoryId'] = $selectedCategory;
            } else {
                // 降級使用 d_tag 欄位（向後兼容）
                // 修正：如果不是用 Map Table，那就是直接過濾欄位 (例如 d_class2)
                $conditions[] = "{$tableName}.{$categoryField} = :categoryId";
                // 原本的 FIND_IN_SET 也可以，但對於 d_class2 這種 INT 欄位，用 = 比較準確且快
                // $conditions[] = "FIND_IN_SET(:categoryId, {$tableName}.{$categoryField})";
                $params[':categoryId'] = $selectedCategory;
            }
        } else {
            // 如果不合法，清空變數以便 UI 顯示「全部」
            $selectedCategory = ""; 
        }
    }
}

// 回收桶模式
if ($columnExists) { // Only apply trash filter if the column exists
    if ($isTrashMode) {
        $conditions[] = "{$tableName}.{$col_delete_time} IS NOT NULL";
    } else {
        $conditions[] = "{$tableName}.{$col_delete_time} IS NULL";
    }
}


// 組合 WHERE 子句
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 【調試】記錄查詢條件
error_log("Module: {$module}, Table: {$tableName}, WHERE: {$whereClause}, Params: " . json_encode($params));

// 6. 查詢總筆數
$countQuery = "SELECT COUNT(*) as total FROM {$tableName} {$whereClause}";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalRows = $countStmt->fetch()['total'];

error_log("Total rows found: {$totalRows}");

// 查詢資料（分頁）
// 【防呆】檢查欄位是否存在於資料表中
$tableColumns = [];
$columnsQuery = "SHOW COLUMNS FROM {$tableName}";
$columnsStmt = $conn->prepare($columnsQuery);
$columnsStmt->execute();
while ($colInfo = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
    $tableColumns[] = $colInfo['Field'];
}

// 【防呆】只在欄位存在時才使用
$safeOrderBy = $orderBy;
// 檢查 ORDER BY 中的欄位是否存在
preg_match('/^(\w+)/', $orderBy, $matches);
if (isset($matches[1]) && !in_array($matches[1], $tableColumns)) {
    // 如果排序欄位不存在，使用主鍵
    $safeOrderBy = "{$col_id} DESC";
}

// 【修正】如果有 d_top 欄位，從 orderBy 中移除它，因為我們會在 sortSql 中統一加入
if ($col_top !== null && in_array($col_top, $tableColumns)) {
    // 從 safeOrderBy 中移除 d_top 的排序（避免重複）
    $safeOrderBy = preg_replace('/\b' . preg_quote($col_top, '/') . '\s+(DESC|ASC)\s*,?\s*/i', '', $safeOrderBy);
    $safeOrderBy = trim($safeOrderBy, ', ');
    
    // 【修改】恢復無條件置頂排序。只要有 d_top 欄位，就應該排在最前面。
    // 無論是否在分類下，置頂項目都應該優先顯示（全域置頂）。
    $sortSql = "{$tableName}.{$col_top} DESC, ";
} else {
    $sortSql = "";
}

// 【新增】為了避免 JOIN 查詢導致欄位衝突 (Ambiguous column)，對 safeOrderBy 也加上 Table Name 前綴
// 【新增】為了避免 JOIN 查詢導致欄位衝突 (Ambiguous column)，對 safeOrderBy 也加上 Table Name 前綴
if (!empty($safeOrderBy)) {
    $safeOrderByParts = explode(',', $safeOrderBy);
    foreach ($safeOrderByParts as &$part) {
        $part = trim($part);
        if (!empty($part) && !strpos($part, '.')) { // 如果還沒有點號 (表示未指定 Table)
            $part = "{$tableName}.{$part}";
        }
    }
    $safeOrderBy = implode(', ', $safeOrderByParts);
}

// 【新增】支援 customQuery（用於 JOIN 查詢）
$customQuery = $moduleConfig['listPage']['customQuery'] ?? null;

// 【修改】當有選擇分類時，使用 data_taxonomy_map 進行 JOIN 和排序
$useMapTableSort = false;
// 【新增】檢查設定檔是否啟用 useTaxonomyMapSort (預設為 true)
$configUseTaxonomyMapSort = $moduleConfig['listPage']['useTaxonomyMapSort'] ?? true;

if ($hasCategory && $selectedCategory && !$isTrashMode && $configUseTaxonomyMapSort) {
    $checkMapTable = "SHOW TABLES LIKE 'data_taxonomy_map'";
    $mapTableStmt = $conn->query($checkMapTable);
    if ($mapTableStmt && $mapTableStmt->rowCount() > 0) {
        $useMapTableSort = true;
    }
}

if ($useMapTableSort) {
    // 使用 data_taxonomy_map JOIN 查詢，並用 sort_num 排序
    // 【關鍵】將 sort_num 別名為 d_sort，讓後續程式碼統一使用
    // 【修正】使用 data_taxonomy_map 的 d_top (如果有) 作為置頂依據
    // SELECT 中覆蓋 d_top，這樣列表顯示的置頂狀態就是該分類下的狀態
    $dataQuery = "SELECT {$tableName}.*, data_taxonomy_map.sort_num AS {$col_sort}, data_taxonomy_map.d_top AS {$col_top}
                  FROM {$tableName}
                  INNER JOIN data_taxonomy_map ON {$tableName}.{$col_id} = data_taxonomy_map.d_id
                  {$whereClause} 
                  AND data_taxonomy_map.t_id = :map_category_id
                  ORDER BY data_taxonomy_map.{$col_top} DESC, data_taxonomy_map.sort_num ASC
                  LIMIT :offset, :limit";
    $params[':map_category_id'] = $selectedCategory;
} elseif ($customQuery) {
    // 使用自訂查詢（已包含 SELECT 和 FROM）
    $dataQuery = "{$customQuery} {$whereClause} ORDER BY {$sortSql}{$safeOrderBy} LIMIT :offset, :limit";
} else {
    // 使用預設查詢
    $dataQuery = "SELECT * FROM {$tableName} {$whereClause} ORDER BY {$sortSql}{$safeOrderBy} LIMIT :offset, :limit";
    echo "<!-- DEBUG SQL (Default): {$dataQuery} -->";
}

$dataStmt = $conn->prepare($dataQuery);

// --- 3. 綁定參數 ---
foreach ($params as $key => $value) {
    $dataStmt->bindValue($key, $value);
}

// 分頁參數必須使用 PARAM_INT
$dataStmt->bindValue(':offset', (int) $startRow, PDO::PARAM_INT);
$dataStmt->bindValue(':limit', (int) $maxRows, PDO::PARAM_INT);
$dataStmt->execute();

// 【調試】檢查查詢結果
$debugRow = $dataStmt->fetch(PDO::FETCH_ASSOC);
if ($debugRow) {
    error_log("First row data: d_id={$debugRow[$col_id]}, {$col_sort}=" . ($debugRow[$col_sort] ?? 'NULL'));
    // 重新執行查詢以取得所有資料
    $dataStmt->execute();
}

$totalPages = ceil($totalRows / $maxRows) - 1;

// 建立查詢字串 (保持原樣)
$queryString = "";
if (!empty($_SERVER['QUERY_STRING'])) {
    $params = explode("&", $_SERVER['QUERY_STRING']);
    $newParams = array();
    foreach ($params as $param) {
        if (stristr($param, "pageNum") == false && stristr($param, "totalRows") == false) {
            array_push($newParams, $param);
        }
    }
    if (count($newParams) != 0) {
        $queryString = "&" . htmlentities(implode("&", $newParams));
    }
}
$queryString = sprintf("&totalRows=%d%s", $totalRows, $queryString);

require_once('display_page.php');
?>

<!DOCTYPE html>
<html class="sidebar-left-big-icons">

<head>
    <title><?php require_once('cmsTitle.php'); ?></title>
    <?php require_once('head.php'); ?>
    <?php require_once('script.php'); ?>
</head>

<body>
    <section class="body">
        <!-- start: header -->
        <?php require_once('header.php'); ?>
        <!-- end: header -->

        <div class="inner-wrapper">
            <!-- start: sidebar -->
            <?php require_once('sidebar.php'); ?>
            <!-- end: sidebar -->

            <section role="main" class="content-body">
                <header class="page-header">
                    <h2><?php echo $isTrashMode ? '回收桶' : $moduleConfig['moduleName']; ?></h2>

                    <div class="right-wrapper text-end">
                        <ol class="breadcrumbs">
                            <?php 
                            require_once(__DIR__ . '/includes/menuHelper.php');
                            $currentPageTitle = $isTrashMode ? '回收桶' : '列表';
                            echo renderBreadcrumbsHtml($conn, $module, $currentPageTitle);
                            ?>
                        </ol>

                        <a class="sidebar-right-toggle" data-open="sidebar-right" style="pointer-events: none;"></a>
                    </div>
                </header>

                <!-- start: page -->
                <div class="row">
                    <div class="col">

                        <div class="row align-items-center mb-3">
                            <div class="col-12 col-lg-auto mb-3 mb-lg-0">
                                <?php
                                if ($hasHierarchicalNav && !$isTrashMode) {
                                    if ($parentId !== null && $parentId > 0) {
                                        $parentCol = $moduleConfig['cols']['parent_id'];
                                        $primaryKey = $moduleConfig['primaryKey'];
                                        $titleCol = $moduleConfig['cols']['title'];

                                        $breadcrumbQuery = "SELECT {$primaryKey}, {$parentCol}, {$titleCol} FROM {$tableName} WHERE {$primaryKey} = :currentId";
                                        $breadcrumbStmt = $conn->prepare($breadcrumbQuery);
                                        $breadcrumbStmt->execute([':currentId' => $parentId]);
                                        $currentItem = $breadcrumbStmt->fetch(PDO::FETCH_ASSOC);

                                        if ($currentItem) {
                                            $backParentId = $currentItem[$parentCol];
                                            $backUrl = PORTAL_AUTH_URL."tpl={$module}/list?language={$currentLang}" . ($backParentId > 0 ? "&parent_id={$backParentId}" : "");
                                            echo "<span class='me-3'>當前位置：{$currentItem[$titleCol]}</span>";
                                            echo "<a href=\"{$backUrl}\" class=\"btn btn-primary btn-md font-weight-semibold btn-py-2 px-4\"><i class=\"fas fa-arrow-left\"></i> 返回上一層</a>";
                                        }
                                    } else {
                                        echo "<span class='me-3'>當前位置：頂層選單</span>";
                                    }
                                }
                                ?>

                                <?php if ($isTrashMode): ?>
                                    <a href="<?=PORTAL_AUTH_URL?>tpl=<?=$module?>/list"
                                        class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4"><i
                                            class="fas fa-arrow-left"></i> 返回</a>
                                <?php else: ?>
                                    <?php
                                    $showAddButton = $moduleConfig['listPage']['showAddButton'] ?? true;

                                    if ($showAddButton && $canAdd) {
                                        $addUrl = PORTAL_AUTH_URL."tpl={$module}/detail";
                                        $urlParams = [];
                                        if ($hasHierarchicalNav && isset($_GET['parent_id'])) {
                                            $urlParams[] = "parent_id=" . urlencode($_GET['parent_id']);
                                        }
                                        if ($hasCategory && isset($_GET['selected1'])) {
                                            $urlParams[] = "selected1=" . urlencode($_GET['selected1']);
                                        }
                                        
                                        // 【多語系】加入語系參數
                                        $urlParams[] = "language=" . urlencode($currentLang);

                                        if (!empty($urlParams)) {
                                            $addUrl .= "?" . implode('&', $urlParams);
                                        }
                                        
                                        echo "<a href=\"{$addUrl}\" class=\"btn btn-primary btn-md font-weight-semibold btn-py-2 px-4\"><i class=\"fas fa-plus-circle\"></i> 新增</a>";
                                    }
                                    ?>
                                    <?php if ($hasTrashData) {
                                        echo " " . renderTrashButton($module);
                                    } ?>
                                <?php endif; ?>
                            </div>

                            <?php if (($moduleConfig['listPage']['hasLanguage'] ?? true) && count($activeLanguages) > 1): ?>
                                <div class="col-12 col-lg-auto ms-auto mb-3 mb-lg-0">
                                    <ul class="nav nav-pills nav-pills-primary">
                                        <?php 
                                        $urlParams = $_GET;
                                        unset($urlParams['language'], $urlParams['pageNum'], $urlParams['totalRows'], $urlParams['module'], $urlParams['trash'], $urlParams['selected1']);
                                        if (isset($categoryField)) {
                                            unset($urlParams[$categoryField]);
                                        }
                                        $baseQuery = http_build_query($urlParams);
                                        
                                        foreach ($activeLanguages as $lang): 
                                            $activeClass = ($lang['l_slug'] == $currentLang) ? 'active' : '';
                                            $langUrl = PORTAL_AUTH_URL."tpl={$module}/list?" . ($baseQuery ? $baseQuery . "&" : "") . "language=" . $lang['l_slug'];
                                        ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?= $activeClass ?> py-1 px-3" href="<?= $langUrl ?>"><?= $lang['l_name'] ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card card-modern">
                            <div class="card-body">
                                <div class="datatables-header-footer-wrapper mt-2">
                                    <div class="datatable-header">
                                        <div class="row align-items-center mb-3">
                                            <div class="col-8 col-lg-auto ms-auto ml-auto mb-3 mb-lg-0">
                                                <div class="d-flex align-items-lg-center flex-column flex-lg-row">
                                                    <?php if (!$isTrashMode && ($moduleConfig['listPage']['hasCategory'] ?? false)): ?>
                                                        <label class="ws-nowrap me-3 mb-0">Filter By:</label>
                                                        <select name="select1" id="select1" class="chosen-select form-control select-style-1 filter-by">
                                                            <?php foreach ($categories as $cat): ?>
                                                                <?php $selected = ($cat['id'] == $selectedCategory) ? "selected" : ""; ?>
                                                                <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>>
                                                                    <?php echo $cat['name']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php else: ?>
                                                        <select name="select1" id="select1" class="chosen-select form-control select-style-1 filter-by" style="display: none;">
                                                            <option value="all">all</option>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-4 col-lg-auto ps-lg-1 mb-3 mb-lg-0">
                                                <div class="d-flex align-items-lg-center flex-column flex-lg-row">
                                                    <label class="ws-nowrap me-3 mb-0">Show:</label>
                                                    <select class="form-control select-style-1 results-per-page" name="results-per-page">
                                                        <option value="12" selected>12</option>
                                                        <option value="24">24</option>
                                                        <option value="36">36</option>
                                                        <option value="100">100</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-auto ps-lg-1">
                                                <div class="search search-style-1 search-style-1-lg mx-lg-auto">
                                                    <div class="input-group">
                                                        <input type="text" class="search-term form-control" name="search-term"
                                                            id="search-term" placeholder="Search Category">
                                                        <button class="btn btn-default" type="submit"><i
                                                                class="bx bx-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <table class="table table-ecommerce-simple table-striped mb-0" id="datatable-ecommerce-list" style="min-width: 550px;">
                                        <thead>
                                            <tr>
                                                <th width="3%"><input type="checkbox" name="select-all" class="select-all checkbox-style-1 p-relative top-2" value="" /></th>
                                                <?php
                                                if ($isTrashMode) {
                                                    // 1. 先判斷原本的配置中有沒有圖片欄位
                                                    $hasImageColumn = false;
                                                    foreach ($moduleConfig['listPage']['columns'] as $col) {
                                                        if ($col['type'] === 'image') {
                                                            $hasImageColumn = true;
                                                            break;
                                                        }
                                                    }

                                                    // 2. 定義基礎的回收桶欄位
                                                    $trashColumns = [
                                                        ['field' => $col_date, 'label' => '日期', 'width' => '142'],
                                                        ['field' => $col_title, 'label' => '標題', 'width' => '470']
                                                    ];

                                                    // 3. 如果原本有圖片欄位，才加入圖片顯示
                                                    if ($hasImageColumn) {
                                                        $trashColumns[] = ['field' => 'image', 'label' => '圖片', 'width' => '140'];
                                                    }

                                                    // 4. 加入功能按鈕
                                                    $trashColumns[] = ['field' => 'view', 'label' => '查看', 'width' => '30'];
                                                    $trashColumns[] = ['field' => 'restore', 'label' => '還原', 'width' => '30'];
                                                    $trashColumns[] = ['field' => 'delete', 'label' => '刪除', 'width' => '30'];

                                                    $displayColumns = $trashColumns;
                                                } else {
                                                    $displayColumns = $moduleConfig['listPage']['columns'];

                                                    // 【修改】如果選擇「全部」分類，隱藏置頂和排序欄位
                                                    if ($hasCategory && empty($selectedCategory)) {
                                                        $displayColumns = array_filter($displayColumns, function ($col) {
                                                            // 過濾掉 pin 按鈕和 sort 下拉選單
                                                            if ($col['type'] === 'sort')
                                                                return false;
                                                            if ($col['type'] === 'button' && $col['field'] === 'pin')
                                                                return false;
                                                            return true;
                                                        });
                                                    }
                                                }

                                                foreach ($displayColumns as $col):
                                                    ?>
                                                    <td width="<?php echo $col['width'] ?? 'auto'; ?>" align="center">
                                                        <?php echo $col['label']; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)):
                                                $rowId = $row[$moduleConfig['primaryKey']];
                                                
                                            ?>
                                                <tr>
                                                <td width="30"><input type="checkbox" name="checkboxRow1" class="row-checkbox checkbox-style-1 p-relative top-2" value="<?php echo $rowId; ?>" /></td>
                                                    <?php foreach ($displayColumns as $col): ?>
                                                        <td align="center">
                                                            <?php
                                                            if ($isTrashMode) {
                                                                // 【修改】回收桶內文顯示使用變數比對
                                                                if ($col['field'] == $col_date) {
                                                                    echo htmlspecialchars($row[$col_date] ?? '', ENT_QUOTES, 'UTF-8');
                                                                } elseif ($col['field'] == $col_title) {
                                                                    echo htmlspecialchars($row[$col_title] ?? '', ENT_QUOTES, 'UTF-8');
                                                                } elseif ($col['field'] == 'image') {
                                                                    // 從配置讀起 imageFileType，預設為 'image'
                                                                    $imageFileType = $moduleConfig['listPage']['imageFileType'] ?? 'image';
                                                                    $imgQuery = "SELECT * FROM file_set WHERE file_type=:file_type AND {$col_file_fk} = :id ORDER BY file_sort ASC LIMIT 1";
                                                                    $imgStmt = $conn->prepare($imgQuery);
                                                                    $imgStmt->execute([':file_type' => $imageFileType, ':id' => $rowId]);
                                                                    $imgRow = $imgStmt->fetch();
                                                                    if ($imgRow) {
                                                                        echo "<img src=\"../{$imgRow['file_link1']}\" style=\"max-width: 100px;\">";
                                                                    } else {
                                                                        echo "<img src=\"image/default_image_s.jpg\">";
                                                                    }
                                                                } elseif ($col['field'] == 'view') {
                                                                    echo renderViewButton($rowId, $module, $primaryKey, true);
                                                                } elseif ($col['field'] == 'edit') {
                                                                    echo renderEditButton($rowId, $module, $primaryKey, true);
                                                                } elseif ($col['field'] == 'restore') {
                                                                    echo renderRestoreButton($rowId, $module);
                                                                } elseif ($col['field'] == 'delete') {
                                                                    echo renderPermanentDeleteButton($rowId, $module);
                                                                }
                                                            } else {
                                                                // 正常模式
                                                                switch ($col['type']) {
                                                                    case 'date':
                                                                    case 'text':
                                                                        echo "<a href=\"".PORTAL_AUTH_URL."tpl={$module}/detail?{$primaryKey}={$rowId}\">" . htmlspecialchars($row[$col['field']] ?? '', ENT_QUOTES, 'UTF-8') . "</a>";
                                                                        break;
                                                                    case 'view_count':
                                                                        echo $row['d_view'] ?? 0;
                                                                    break;
                                                                case 'sort':
                                                                    // 檢查 Map Table 是否存在 (用於 context)
                                                                    $checkMapTable = "SHOW TABLES LIKE 'data_taxonomy_map'";
                                                                    $mapTableResult = $conn->query($checkMapTable);
                                                                    $hasMapTable = ($mapTableResult && $mapTableResult->rowCount() > 0);

                                                                    // 使用 SortCountHelper 計算排序筆數
                                                                    $sortContext = [
                                                                        'tableName' => $tableName,
                                                                        'col_id' => $col_id,
                                                                        'totalRows' => $totalRows,
                                                                        'row' => $row,
                                                                        'menuKey' => $menuKey,
                                                                        'menuValue' => $menuValue,
                                                                        'col_top' => $cols['top'] ?? null,
                                                                        'hasCategory' => $hasCategory,
                                                                        'selectedCategory' => $selectedCategory,
                                                                        'categoryField' => $categoryField,
                                                                        'useTaxonomyMapSort' => $moduleConfig['listPage']['useTaxonomyMapSort'] ?? true,
                                                                        'hasHierarchicalNav' => $hasHierarchicalNav,
                                                                        'parentIdField' => $parentIdField,
                                                                        'currentParentId' => $parentId,
                                                                        'col_delete_time' => $col_delete_time,
                                                                        'hasDeleteTime' => $columnExists,
                                                                        'hasMapTable' => $hasMapTable
                                                                    ];
                                                                    
                                                                    $sortRowCount = SortCountHelper::getCount($conn, $sortContext);

                                                                    // 【重要】如果項目是置頂的，不顯示排序下拉選單，顯示文字即可
                                                                    // 這樣使用者就不會嘗試去排序置頂項目，也不會混淆
                                                                    if ($col_top !== null && isset($row[$col_top]) && $row[$col_top] == 1) {
                                                                        echo "<span class='badge badge-warning'>置頂中 (原排序: {$row[$col_sort]})</span>";
                                                                    } else {
                                                                        $sortVal = $row[$col_sort] ?? 0;
                                                                        echo renderSortDropdown($sortVal, $sortRowCount, $rowId, $pageNum, $selectedCategory, $col_sort);
                                                                    }
                                                                    break;
                                                                case 'image':
                                                                    // 從配置讀取 imageFileType，預設為 'image'
                                                                    $imageFileType = $moduleConfig['listPage']['imageFileType'] ?? 'image';
                                                                    $imgQuery = "SELECT * FROM file_set WHERE file_type=:file_type AND {$col_file_fk} = :id ORDER BY file_sort ASC LIMIT 1";
                                                                    $imgStmt = $conn->prepare($imgQuery);
                                                                    $imgStmt->execute([':file_type' => $imageFileType, ':id' => $rowId]);
                                                                    $imgRow = $imgStmt->fetch();
                                                                    if ($imgRow) {
                                                                        echo "<a href=\"".PORTAL_AUTH_URL."tpl={$module}/detail?{$primaryKey}={$rowId}\"><img src=\"../{$imgRow['file_link2']}\"></a>";
                                                                    } else {
                                                                        echo "<a href=\"".PORTAL_AUTH_URL."tpl={$module}/detail?{$primaryKey}={$rowId}\"><img src=\"image/default_image_s.jpg\"></a>";
                                                                    }
                                                                    break;
                                                                case 'select':
                                                                    // 【新增】處理 select 欄位顯示
                                                                    $fieldValue = $row[$col['field']] ?? '';
                                                                    $displayLabel = $fieldValue; // 預設顯示原始值
                                                                    
                                                                    // 如果有定義 options，找出對應的 label
                                                                    if (isset($col['options']) && is_array($col['options'])) {
                                                                        foreach ($col['options'] as $option) {
                                                                            if (isset($option['value']) && $option['value'] == $fieldValue) {
                                                                                $displayLabel = $option['label'] ?? $fieldValue;
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                    
                                                                    echo htmlspecialchars($displayLabel, ENT_QUOTES, 'UTF-8');
                                                                    break;
                                                                case 'active':
                                                                    // 【修改】使用變數 $col_active
                                                                    $activeVal = $row[$col_active] ?? 1;
                                                                    echo renderActiveToggle($activeVal, $rowId);
                                                                    break;
                                                                case 'read_toggle':
                                                                    // 【新增】已讀/未讀狀態切換
                                                                    $readVal = $row[$col_read] ?? 0;
                                                                    echo renderReadToggle($readVal, $rowId);
                                                                    break;
                                                                case 'reply_status':
                                                                    // 【新增】回覆狀態顯示
                                                                    $replyVal = $row[$col_reply] ?? 0;
                                                                    echo renderReplyStatus($replyVal);
                                                                    break;
                                                                case 'button':
                                                                    if ($col['field'] == 'pin') {
                                                                        // 【修改】使用變數 $col_top
                                                                        $topVal = $row[$col_top] ?? 0;
                                                                        echo renderPinButton($rowId, $module, $topVal);
                                                                    } elseif ($col['field'] == 'edit') {
                                                                        // 【權限檢查】只有有編輯權限才顯示
                                                                        if ($canEdit) {
                                                                            echo renderEditButton($rowId, $module, $primaryKey);
                                                                        }
                                                                    } elseif ($col['field'] == 'delete') {
                                                                        // 【權限檢查】只有有刪除權限才顯示
                                                                        if ($canDelete) {
                                                                            echo renderDeleteButton($rowId, $module, $hasTrash, $hasHierarchy);
                                                                        }
                                                                    } elseif ($col['field'] === 'view') {
                                                                        // 回收桶的查看按鈕 OR viewOnly 的查看按鈕
                                                                        $isTrashView = isset($_GET['trash']) && $_GET['trash'] == '1';
                                                                        echo renderViewButton($rowId, $module, $primaryKey, $isTrashView);
                                                                    } elseif ($col['field'] === 'restore') {
                                                                        echo renderRestoreButton($rowId, $module);
                                                                    } elseif ($col['field'] === 'next_level') {
                                                                        // 【階層導航】下一層按鈕
                                                                        if ($hasHierarchicalNav) {
                                                                            $nextLevelUrl = PORTAL_AUTH_URL."tpl={$module}/list?parent_id={$rowId}";
                                                                            echo "<a href=\"{$nextLevelUrl}\" class=\"btn btn-primary\" title=\"下一層\"><i class=\"fas fa-level-down-alt\"></i></a>";
                                                                        }
                                                                    }
                                                                break;
                                                                break;
                                                                }
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                    <hr class="solid mt-5 opacity-4">
                                    <div class="datatable-footer">
                                        <div class="row align-items-center justify-content-between mt-3">
                                            <div class="col-md-auto order-1 mb-3 mb-lg-0">
                                                <div class="d-flex align-items-stretch">
                                                     <div class="d-grid gap-3 d-md-flex justify-content-md-end me-4">
                                                         <select class="form-control select-style-1 bulk-action" name="bulk-action" style="min-width: 170px;">
                                                             <option value="" selected>批次操作</option>
                                                             <?php if ($isTrashMode) { ?>
                                                                 <?php if ($canDelete) { ?>
                                                                     <option value="restore">還原所選</option>
                                                                     <option value="delete">永久刪除</option>
                                                                 <?php } ?>
                                                             <?php } else { ?>
                                                                 <?php if ($canDelete) { ?>
                                                                     <option value="delete">刪除所選</option>
                                                                 <?php } ?>
                                                                 <?php if ($canAdd) { ?>
                                                                     <option value="clone_local">複製資料</option>
                                                                     <?php if (count($activeLanguages) > 1) { ?>
                                                                        <option value="clone">複製到語系</option>
                                                                     <?php } ?>
                                                                 <?php } ?>
                                                             <?php } ?>
                                                         </select>
                                                         <select class="form-control select-style-1 bulk-action-lang d-none" name="bulk-action-lang" style="min-width: 140px;">
                                                            <option value="">選擇語系...</option>
                                                            <?php foreach ($activeLanguages as $lang): ?>
                                                                <?php if($lang['l_slug'] !== $currentLang): ?>
                                                                    <option value="<?= $lang['l_slug'] ?>"><?= $lang['l_name'] ?> (<?= $lang['l_slug'] ?>)</option>
                                                                 <?php endif; ?>
                                                            <?php endforeach; ?>
                                                         </select>
                                                         <a href="javascript:void(0);" class="bulk-action-apply btn btn-light btn-px-4 py-3 border font-weight-semibold text-color-dark text-3" style="min-width: 90px;">執行</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-auto text-center order-3 order-lg-2">
                                                <div class="results-info-wrapper"></div>
                                            </div>
                                            <div class="col-lg-auto order-2 order-lg-3 mb-3 mb-lg-0">
                                                <div class="pagination-wrapper"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <!-- end: page -->
            </section>
        </div>
    </section>
    
    <script src="template-style/js/examples/examples.ecommerce.datatables.list.js"></script>
</body>

</html>

<script type="text/javascript">
    // 新的 AJAX 排序邏輯
    function changeSort(pageNum, totalRows, itemId, newSort, categoryId) {
        // 顯示載入提示
        // 【修正】這裡不能寫死 d_sort，改用 PHP 變數 $col_sort (即 'sort_order')
        // 這樣 jQuery 才能抓到正確的 ID: #sort_order_17
        const $select = $('#<?php echo $col_sort; ?>_' + itemId);

        $select.prop('disabled', true);

        $.ajax({
            url: 'ajax_sort.php',
            type: 'POST',
            data: {
                module: '<?php echo $module; ?>',
                item_id: itemId,
                new_sort: newSort,
                category_id: categoryId || 0 // 【新增】傳遞分類 ID
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // 重新載入頁面以顯示新的排序（保留當前 URL 參數）
                    window.location.href = window.location.href;
                } else {
                    alert('排序失敗: ' + response.message);
                    $select.prop('disabled', false);
                }
            },
            error: function (xhr) {
                console.group('AJAX Error Debugging');
                console.log('URL:', 'ajax_sort.php');
                console.log('Status:', xhr.status);
                console.log('Response Text:', xhr.responseText);
                console.groupEnd();

                const url = 'ajax_sort.php';
                alert('排序失敗!\nURL: ' + url + '\n狀態碼: ' + xhr.status + '\n錯誤: ' + xhr.statusText + '\n\n請打開控制台(F12)查看詳細回傳內容');
                $select.prop('disabled', false);
            }
        });
    }

    $(document).ready(function () {
        // 分類切換
        $('#select1').change(function () {
            window.location.href = "<?=PORTAL_AUTH_URL?>tpl=<?php echo $module; ?>/list?selected1=" + $(this).val();
        });
    });

    // 置頂切換功能
    function togglePin(element) {
        const $btn = $(element);
        const itemId = $btn.data('id');
        const module = $btn.data('module');
        const isPinned = $btn.data('pinned');

        $.ajax({
            url: 'ajax_toggle_pin.php',
            type: 'POST',
            data: {
                module: module,
                item_id: itemId,
                category_id: '<?php echo $selectedCategory ?? ""; ?>'
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // 重新載入頁面以顯示新的置頂狀態
                    location.reload();
                } else {
                    alert('操作失敗: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                alert('操作失敗 (HTTP ' + xhr.status + '): ' + error);
            }
        });
    }

    // 草稿/顯示/不顯示 切換功能
    function toggleActive(element, itemId, nextValue) {
        const $badge = $(element);
        const module = '<?php echo $module; ?>';

        $.ajax({
            url: 'ajax_toggle_active.php',
            type: 'POST',
            data: {
                module: module,
                item_id: itemId,
                new_value: nextValue
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // 重新載入頁面以顯示新的狀態
                    location.reload();
                } else {
                    alert('操作失敗: ' + response.message);
                }
            },
            error: function (xhr) {
                alert('操作失敗 (HTTP ' + xhr.status + ')');
            }
        });
    }
</script>

<script>
    // 全域函數：還原功能
    function restoreItem(element) {
        const $btn = $(element);
        const itemId = $btn.data('id');
        const module = $btn.data('module');

        Swal.fire({
            title: '確定要還原嗎？',
            text: '還原後此項目將回到正常列表',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '確定還原',
            cancelButtonText: '取消'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '處理中...',
                    text: '正在還原資料',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'ajax_restore.php',
                    type: 'POST',
                    data: {
                        module: module,
                        item_id: itemId
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: '還原成功！',
                                text: '資料已成功還原',
                                icon: 'success',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                // 返回回收桶列表
                                window.location.href = '<?=PORTAL_AUTH_URL?>tpl=' + module + '/list?trash=1';
                            });
                        } else {
                            Swal.fire({
                                title: '還原失敗',
                                text: response.message || '發生未知錯誤',
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: '請求失敗',
                            text: '無法連接到伺服器，請稍後再試',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    }

    /**
     * 全域函數：永久刪除功能 (含串聯刪除防呆)
     */
    async function permanentDelete(element) {
        const $btn = $(element);
        const itemId = $btn.data('id');
        const module = $btn.data('module');

        // 第一階段：基本確認
        const firstConfirm = await Swal.fire({
            title: '確定要永久刪除嗎？',
            html: '<strong style="color: #dc3545;">⚠️ 此操作無法復原！</strong><br>資料將被永久刪除',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: '確定刪除',
            cancelButtonText: '取消'
        });

        if (!firstConfirm.isConfirmed) return;

        // 顯示處理中
        Swal.fire({
            title: '處理中...',
            text: '正在檢查並刪除資料',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await $.ajax({
                url: 'ajax_permanent_delete.php',
                type: 'POST',
                data: { module, item_id: itemId, force: 0 }, // 先嘗試普通刪除
                dataType: 'json'
            });

            if (response.success) {
                showSuccessAndReload(module);
            } else if (response.has_data) {
                // 第二階段：發現有子資料，提示串聯刪除
                const secondConfirm = await Swal.fire({
                    title: '分類內尚有資料',
                    html: response.message + '<br>是否要連同這些文章一起「永久刪除」？',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '全部刪除',
                    cancelButtonText: '再考慮一下'
                });

                if (secondConfirm.isConfirmed) {
                    Swal.fire({ title: '執行深度刪除...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    const forceResponse = await $.ajax({
                        url: 'ajax_permanent_delete.php',
                        type: 'POST',
                        data: { module, item_id: itemId, force: 1 }, // 執意刪除
                        dataType: 'json'
                    });

                    if (forceResponse.success) {
                        showSuccessAndReload(module);
                    } else {
                        showError(forceResponse.message);
                    }
                }
            } else {
                showError(response.message);
            }
        } catch (e) {
            showError('網路通訊失敗');
        }
    }

    // CSRF Tokens (從 Session 獲取 Slim CSRF 產生的 Token)
    const CSRF_NAME = '<?= $_SESSION['csrf_name'] ?? 'csrf_name' ?>';
    const CSRF_VALUE = '<?= $_SESSION['csrf_value'] ?? '' ?>';


    function showSuccessAndReload(module) {
        Swal.fire({ title: '刪除成功！', icon: 'success' }).then(() => {
            window.location.href = '<?=PORTAL_AUTH_URL?>tpl=' + module + '/list?trash=1';
        });
    }

    function showError(msg) {
        Swal.fire({ title: '操作失敗', text: msg, icon: 'error' });
    }

    // 全域函數：刪除功能（移至回收桶或直接刪除）
    function deleteItem(element) {
        const $btn = $(element);
        const itemId = $btn.data('id');
        const module = $btn.data('module');
        const hasTrash = $btn.data('has-trash');
        const hasHierarchy = $btn.data('has-hierarchy') == '1';

        // 【新增】如果是階層式結構，先檢查是否有子分類
        if (hasHierarchy) {
            // 顯示檢查中
            Swal.fire({
                title: '檢查中...',
                text: '正在檢查是否有子分類',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // AJAX 檢查是否有子分類
            $.ajax({
                url: 'ajax_check_children.php',
                method: 'GET',
                data: { module: module, id: itemId },
                dataType: 'json',
                success: function(response) {
                    Swal.close(); // 關閉檢查中的提示

                    if (response.error) {
                        showError(response.error);
                        return;
                    }

                    // 根據是否有子分類顯示不同的確認訊息
                    let title, text, icon, confirmButtonColor;
                    
                    if (response.hasChildren) {
                        // 【修改】有子分類時的處理
                        if (hasTrash == '1') {
                            // 有回收桶：不提供級聯刪除選項，要求先刪除子分類
                            title = '無法刪除';
                            text = `此分類下還有 <strong style="color: #dc3545;">${response.count}</strong> 個子分類<br><br>` +
                                   `<strong style="color: #dc3545;">⚠️ 使用回收桶的模組不支援級聯刪除</strong><br><br>` +
                                   `請先手動刪除或移動所有子分類後再刪除此分類`;
                            icon = 'error';
                            
                            Swal.fire({
                                title: title,
                                html: text,
                                icon: icon,
                                confirmButtonText: '知道了',
                                confirmButtonColor: '#3085d6'
                            });
                        } else {
                            // 無回收桶（硬刪除）：提供級聯刪除選項
                            const warningText = `⚠️ 選擇「連同子分類一起刪除」將會永久刪除此分類及其所有子分類！`;
                            
                            title = '此分類下有子分類';
                            text = `此分類下還有 <strong style="color: #dc3545;">${response.count}</strong> 個子分類<br><br>` +
                                   `<strong style="color: #dc3545;">${warningText}</strong><br><br>` +
                                   `或者您可以先手動刪除或移動子分類後再刪除此分類`;
                            icon = 'warning';
                            
                            // 只提供2個按鈕：確認級聯刪除 或 取消
                            Swal.fire({
                                title: title,
                                html: text,
                                icon: icon,
                                showCancelButton: true,
                                confirmButtonText: '連同子分類一起永久刪除',
                                cancelButtonText: '取消',
                                confirmButtonColor: '#dc3545',
                                cancelButtonColor: '#6c757d'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // 用戶選擇強制刪除（cascade delete）
                                    executeDelete(module, itemId, 0, true); // 第四個參數表示 cascade
                                }
                                // 如果點擊取消，則不執行任何操作
                            });
                        }
                    } else {
                        // 沒有子分類，顯示正常的刪除確認
                        if (hasTrash == '1') {
                            title = '確定要刪除此分類嗎？';
                            text = '資料將移至回收桶，可以稍後還原';
                            icon = 'warning';
                            confirmButtonColor = '#3085d6';
                        } else {
                            title = '確定要永久刪除此分類嗎？';
                            text = '<strong style="color: #dc3545;">⚠️ 此操作無法復原，將直接刪除！</strong>';
                            icon = 'warning';
                            confirmButtonColor = '#dc3545';
                        }

                        Swal.fire({
                            title: title,
                            html: text,
                            icon: icon,
                            showCancelButton: true,
                            confirmButtonColor: confirmButtonColor,
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '確定刪除',
                            cancelButtonText: '取消'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                executeDelete(module, itemId, 0);
                            }
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    showError('檢查子分類時發生錯誤');
                }
            });
        } else {
            // 非階層式結構，使用原本的刪除流程
            const title = hasTrash == '1' ? '確定要刪除嗎？' : '確定要永久刪除嗎？';
            const text = hasTrash == '1' ? '資料將移至回收桶，可以稍後還原' : '⚠️ 此操作無法復原，資料將被永久刪除！';

            Swal.fire({
                title: title,
                html: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '確定刪除',
                cancelButtonText: '取消'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDelete(module, itemId, 0);
                }
            });
        }
    }

    function executeDelete(module, itemId, force = 0, cascade = false) {
        // 顯示處理中
        Swal.fire({
            title: '處理中...',
            text: cascade ? '正在刪除分類及所有子分類...' : '正在執行刪除動作',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        // 使用 delete_handler.php 方式
        const deleteUrl = `delete_handler.php?module=${module}&id=${itemId}${cascade ? '&cascade=1' : ''}`;
        window.location.href = deleteUrl;
    }

    /**
     * 新版批次操作處理
     */
    $(document).ready(function() {
        // 切換操作類型時，顯示或隱藏語系選單
        $('.bulk-action').on('change', function() {
            if ($(this).val() === 'clone') {
                $('.bulk-action-lang').removeClass('d-none');
            } else {
                $('.bulk-action-lang').addClass('d-none').val('');
            }
        });

        $('.bulk-action-apply').on('click', function(e) {
            e.preventDefault();
            const action = $('.bulk-action').val();
            const itemIds = [];
            $('.row-checkbox:checked').each(function() {
                itemIds.push($(this).val());
            });

            if (itemIds.length === 0) {
                Swal.fire('提醒', '請先勾選要處理的資料', 'warning');
                return;
            }

            if (!action) {
                Swal.fire('提醒', '請先選擇操作項目', 'warning');
                return;
            }

            if (action === 'delete') {
                // 批次刪除
                Swal.fire({
                    title: '確定要刪除嗎？',
                    text: "所選的 " + itemIds.length + " 筆資料將被刪除",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '確定',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeBatchDelete(itemIds);
                    }
                });
            } else if (action === 'restore') {
                // 批次還原
                Swal.fire({
                    title: '確定要還原嗎？',
                    text: "所選的 " + itemIds.length + " 筆資料將被還原",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '確定還原',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeBatchRestore(itemIds);
                    }
                });
            } else if (action === 'clone') {
                // 批次複製
                const targetLang = $('.bulk-action-lang').val();
                if (!targetLang) {
                    Swal.fire('提醒', '請先選擇目標語系', 'warning');
                    return;
                }
                Swal.fire({
                    title: '確定要複製嗎？',
                    text: `即將複製 ${itemIds.length} 筆資料到 ${targetLang} 語系`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '確定複製',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeBatchClone(itemIds, targetLang);
                    }
                });
            } else if (action === 'clone_local') {
                // 批次複製 (同語系)
                Swal.fire({
                    title: '確定要複製嗎？',
                    text: `即將複製 ${itemIds.length} 筆資料`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '確定複製',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeBatchClone(itemIds, '<?= $currentLang ?>');
                    }
                });
            }
        });
    });

    function executeBatchRestore(itemIds) {
        Swal.fire({
            title: '處理中...',
            text: '正在還原資料...',
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'ajax_batch_restore.php',
            type: 'POST',
            data: {
                module: '<?= $module ?>',
                item_ids: itemIds
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('成功', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('失敗', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('錯誤', '無法連接到伺服器', 'error');
            }
        });
    }

    function executeBatchDelete(itemIds, force = 0) {
        Swal.fire({
            title: '處理中...',
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'ajax_batch_delete.php',
            type: 'POST',
            data: {
                module: '<?= $module ?>',
                item_ids: itemIds,
                trash: '<?= $isTrashMode ? 1 : 0 ?>',
                force: force
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('成功', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else if (response.has_data) {
                    // 【新增】第二階段：發現有關連資料
                    Swal.fire({
                        title: '分類內尚有資料',
                        html: '<div style="line-height: 1.8;">' +
                              '<strong>以下分類無法刪除：</strong><br><br>' + 
                              response.message.replace(/\n/g, '<br>') + 
                              '<br><br>是否要連同這些文章「全部永久刪除」？' +
                              '</div>',
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: '全部刪除',
                        cancelButtonText: '再考慮一下',
                        width: '500px'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            executeBatchDelete(itemIds, 1); // 強制刪除
                        }
                    });
                } else {
                    Swal.fire('失敗', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('錯誤', '無法連接到伺服器', 'error');
            }
        });
    }

    function executeBatchClone(itemIds, targetLang) {
        Swal.fire({
            title: '處理中...',
            text: '正在複製資料...',
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'ajax_batch_translate_clone.php',
            type: 'POST',
            data: {
                module: '<?= $module ?>',
                item_ids: itemIds,
                target_lang: targetLang
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('成功', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('失敗', response.message, 'error');
                }
            }
        });
    }
</script>

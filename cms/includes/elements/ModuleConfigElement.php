<?php
/**
 * Module Configuration Element
 * 模組配置載入元件
 */

class ModuleConfigElement
{
    /**
     * 載入模組配置
     * @param string $module 模組名稱
     * @param string $configDir 配置目錄（預設為 'set'）
     * @return array 模組配置
     * @throws Exception 如果配置檔案不存在或格式錯誤
     */
    public static function loadConfig($module, $configDir = 'set')
    {
        if (empty($module)) {
            throw new Exception('錯誤：未指定模組');
        }
        
        // 安全過濾：僅允許字母數字與底線，防止 LFI (Local File Inclusion)
        $module = preg_replace('/[^a-zA-Z0-9_]/', '', $module);
        
        $configFile = __DIR__ . "/../../{$configDir}/{$module}Set.php";
        
        if (!file_exists($configFile)) {
            throw new Exception("錯誤：找不到模組配置檔案：{$module}Set.php");
        }
        
        $moduleConfig = require $configFile;
        
        if (!is_array($moduleConfig) || empty($moduleConfig)) {
            throw new Exception("錯誤：配置檔案格式不正確或為空：{$configFile}");
        }

        // 【新增】動態注入標籤類型 (taxonomy_type_id)
        // 如果資料庫中有針對此模組設定標籤類型，則自動覆蓋 Set.php 中的寫死數值
        global $conn;
        if (isset($conn) && $conn instanceof \PDO) {
            try {
                $stmt = $conn->prepare("SELECT taxonomy_type_id FROM cms_menus WHERE menu_type = :module AND menu_active = 1 LIMIT 1");
                $stmt->execute([':module' => $module]);
                $menuRow = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($menuRow && !empty($menuRow['taxonomy_type_id'])) {
                    $dbTaxId = (int)$menuRow['taxonomy_type_id'];
                    
                    // 1. 覆蓋列表過濾值 (menuValue)
                    // 僅在 menuKey 為 taxonomy_type_id 時處理，避免誤傷其他欄位
                    if (isset($moduleConfig['menuKey']) && $moduleConfig['menuKey'] === 'taxonomy_type_id') {
                        $moduleConfig['menuValue'] = $dbTaxId;
                    }

                    // 2. 覆蓋或新增隱藏欄位值 (hiddenFields)
                    // 確保新增資料時也會正確歸類
                    if (!isset($moduleConfig['hiddenFields'])) {
                        $moduleConfig['hiddenFields'] = [];
                    }
                    
                    if (isset($moduleConfig['menuKey']) && $moduleConfig['menuKey'] === 'taxonomy_type_id') {
                        $moduleConfig['hiddenFields']['taxonomy_type_id'] = $dbTaxId;
                    }
                }
            } catch (Exception $e) {
                // 資料庫查詢失敗時不中斷流程，保留 Set.php 原本的值
                error_log("ModuleConfigElement: Failed to fetch taxonomy_type_id for {$module}: " . $e->getMessage());
            }
        }

        // 【新增】動態注入層級資訊 (parent_id)
        // 如果模組開啟了階層功能 (hasHierarchy)，自動將網址上的 parent_id 注入到隱藏欄位中
        $hasHierarchy = $moduleConfig['listPage']['hasHierarchy'] ?? false;
        if ($hasHierarchy) {
            $parentCol = $moduleConfig['cols']['parent_id'] ?? 'parent_id';
            
            // 檢查是否已經在 detailPage 的可見欄位中
            $hasInForm = false;
            foreach ($moduleConfig['detailPage'] ?? [] as $sheet) {
                $items = isset($sheet['items']) ? $sheet['items'] : [$sheet];
                foreach ($items as $item) {
                    if (isset($item['field']) && $item['field'] === $parentCol) {
                        $hasInForm = true;
                        break 2;
                    }
                }
            }

            // 如果不在表單中，才注入到 hiddenFields
            if (!$hasInForm) {
                // 確保 hiddenFields 存在
                if (!isset($moduleConfig['hiddenFields'])) {
                    $moduleConfig['hiddenFields'] = [];
                }
                
                // 自動注入當前的 parent_id，供新增資料時使用
                if (!isset($moduleConfig['hiddenFields'][$parentCol])) {
                    $moduleConfig['hiddenFields'][$parentCol] = $_GET['parent_id'] ?? 0;
                }
            } else {
                // 如果在表單中，嘗試為「新增模式」預設選取當前層級
                $isNewRecord = !isset($_GET[$moduleConfig['primaryKey']]);
                if ($isNewRecord && isset($_GET['parent_id'])) {
                    $targetParentId = (int)$_GET['parent_id'];
                    foreach ($moduleConfig['detailPage'] ?? [] as &$sheet) {
                        $items = &$sheet['items'];
                        foreach ($items as &$item) {
                            if (isset($item['field']) && $item['field'] === $parentCol) {
                                $item['default'] = $targetParentId;
                            }
                        }
                    }
                }
            }
        }

        // 【新增】動態注入分類資訊 (selected1 -> categoryField)
        // 當從已過濾的列表頁面點擊「新增」時，自動帶入該分類
        $hasCategory = $moduleConfig['listPage']['hasCategory'] ?? false;
        if ($hasCategory && isset($_GET['selected1']) && $_GET['selected1'] !== 'all') {
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? 'd_class2';
            $selectedId = (int)$_GET['selected1'];
            
            // 嘗試為「新增模式」預設選取該分類
            $isNewRecord = !isset($_GET[$moduleConfig['primaryKey']]);
            if ($isNewRecord) {
                if (isset($moduleConfig['detailPage']) && is_array($moduleConfig['detailPage'])) {
                    foreach ($moduleConfig['detailPage'] as &$sheet) {
                        if (isset($sheet['items']) && is_array($sheet['items'])) {
                            foreach ($sheet['items'] as &$item) {
                                if (isset($item['field']) && $item['field'] === $categoryField) {
                                    $item['default'] = $selectedId;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $moduleConfig;
    }
    
    /**
     * 取得模組的自訂欄位配置
     * @param array $moduleConfig 模組配置
     * @return array 自訂欄位配置
     */
    public static function getCustomCols($moduleConfig)
    {
        return $moduleConfig['cols'] ?? [];
    }
    
    /**
     * 取得模組的主鍵欄位名稱
     * @param array $moduleConfig 模組配置
     * @return string 主鍵欄位名稱
     */
    public static function getPrimaryKey($moduleConfig)
    {
        return $moduleConfig['primaryKey'] ?? 'd_id';
    }
    
    /**
     * 取得模組的資料表名稱
     * @param array $moduleConfig 模組配置
     * @return string 資料表名稱
     */
    public static function getTableName($moduleConfig)
    {
        return $moduleConfig['tableName'] ?? '';
    }
    
    /**
     * 取得欄位名稱（支援自訂欄位）
     * @param array $customCols 自訂欄位配置
     * @param string $field 欄位類型（如 'sort', 'active', 'title' 等）
     * @param string $default 預設欄位名稱
     * @return string|null 欄位名稱（如果設為 null 則返回 null）
     */
    public static function getColumnName($customCols, $field, $default)
    {
        $value = $customCols[$field] ?? $default;
        return $value === null ? null : $value;
    }
}

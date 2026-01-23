<?php
namespace App\Repositories;

use App\Models\Model;

class DataRepository extends Model {
    protected $isAdmin;
    protected $currentLang; // 【新增】當前語系
    
    public function __construct($db = null, $isAdmin = null) {
        parent::__construct();
        if ($db) $this->db = $db;
        if ($isAdmin !== null) $this->isAdmin = $isAdmin;
        
        // 【新增】獲取當前語系
        // 優先順序：Global > Session > 資料庫預設值
        if (isset($GLOBALS['frontend_lang']) && !empty($GLOBALS['frontend_lang'])) {
             $this->currentLang = $GLOBALS['frontend_lang'];
        } elseif (isset($_SESSION['frontend_lang']) && !empty($_SESSION['frontend_lang'])) {
            $this->currentLang = $_SESSION['frontend_lang'];
        } else {
            $this->currentLang = $this->getDefaultLanguage();
        }
    }
    
    /**
     * 【新增】獲取預設語系
     */
    private function getDefaultLanguage() {
        // 確保 db 已初始化
        if (!$this->db) {
            return 'tw';
        }
        
        try {
            $result = $this->db->row("SELECT l_slug FROM languages WHERE l_is_default = 1 AND l_active = 1 LIMIT 1");
            return $result['l_slug'] ?? 'tw';
        } catch (\Exception $e) {
            return 'tw';
        }
    }
    
    /**
     * [安全過濾] 內部小工具，防止 SQL Injection
     */
    private function escape($value) {
        // 如果你的 DB 類別有 escape 方法，建議改成 return $this->db->escape($value);
        return addslashes($value);
    }

    /**
     * 萬用單筆查詢(只有資料)
     */
    public function getDataOne($class1, $columns = '*') {
        $c1 = $this->escape($class1);
        $lang = $this->escape($this->currentLang);

        $sql = "SELECT $columns FROM data_set
                WHERE d_class1 = '$c1' AND lang = '$lang'
                AND d_delete_time IS NULL
                LIMIT 1";

        return $this->db->row($sql);
    }
    /**
     * 萬用多筆查詢(只有資料)
     */
    /**
     * 萬用多筆查詢(只有資料)
     * 增加 LIMIT 與 OFFSET 避免一次撈取過多資料導致記憶體爆掉
     */
    public function getData($class1, $columns = '*', $order = 'd_date DESC', $limit = 0, $offset = 0) {
        $c1 = $this->escape($class1);
        $lang = $this->escape($this->currentLang);

        $sql = "SELECT $columns FROM data_set
                WHERE d_class1 = '$c1' AND lang = '$lang'
                AND d_delete_time IS NULL
                ORDER BY $order";

        if ($limit > 0) {
            $offset = (int)$offset;
            $limit = (int)$limit;
            $sql .= " LIMIT $offset, $limit";
        }

        return $this->db->query($sql);
    }

    /**
     * 萬用單筆查詢
     */
    /**
     * 萬用單筆查詢 (Explicit JOIN)
     */
    public function getRow($class1, $fileType, $columns = '*') {
        $c1 = $this->escape($class1);
        $ft = $this->escape($fileType);
        $lang = $this->escape($this->currentLang);

        $sql = "SELECT $columns FROM data_set
                INNER JOIN file_set ON data_set.d_id = file_set.file_d_id
                WHERE data_set.d_class1 = '$c1'
                AND data_set.lang = '$lang'
                AND data_set.d_delete_time IS NULL
                AND file_set.file_type = '$ft'
                ORDER BY data_set.d_date DESC LIMIT 1";

        return $this->db->row($sql);
    }

    /**
     * 萬用多筆查詢
     */
    public function getQuery($class1, $fileType, $columns = '*', $limit = 0) {
        $c1 = $this->escape($class1);
        $ft = $this->escape($fileType);
        $lang = $this->escape($this->currentLang);

        $sql = "SELECT $columns FROM data_set
                INNER JOIN file_set ON data_set.d_id = file_set.file_d_id
                WHERE data_set.d_class1 = '$c1'
                AND data_set.lang = '$lang'
                AND data_set.d_delete_time IS NULL
                AND file_set.file_type = '$ft'
                AND data_set.d_active=1
                ORDER BY data_set.d_sort ASC";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

       return $this->db->query($sql);
    }

    /**
     * 通用的撈取「單筆」資料方法
     */
    /**
     * 通用的撈取「單筆」資料方法 (Explicit JOIN)
     */
    public function getOne($class1, $fileType, $extra = '') {
        $defaultColumns = 'd_id, d_title, d_title_en, d_content, file_link1, file_title, file_content';
        $columns = $extra ? "$defaultColumns, $extra" : $defaultColumns;

        $c1 = $this->escape($class1);
        $ft = $this->escape($fileType);
        $lang = $this->escape($this->currentLang);

        $sql = "SELECT $columns FROM data_set
                INNER JOIN file_set ON data_set.d_id = file_set.file_d_id
                WHERE data_set.d_class1='$c1'
                AND data_set.lang = '$lang'
                AND data_set.d_delete_time IS NULL
                AND file_set.file_type='$ft'
                LIMIT 1";

        return $this->db->row($sql);
    }

    /**
     * [Blog] 取得計算總數的 SQL 字串
     */
    public function getListCountSql($class1, $categoryId = null) {
        $c1 = $this->escape($class1);
        $lang = $this->escape($this->currentLang);
        $sql = "SELECT COUNT(*) as total FROM data_set WHERE d_class1='$c1' AND d_active=1 AND lang = '$lang'";
        
        if ($categoryId) {
            $cat = $this->escape($categoryId);
            $sql .= " AND d_class2 = '$cat'";
        }
        return $sql;
    }

    /**
     * 取得分類資訊
     */
    /**
     * 取得分類資訊
     * 優化：使用 JOIN 減少一次資料庫查詢 (N+1 問題優化)
     */
    public function getCategory($ttpCategory, $columns = 'taxonomies.*') {
        $catName = $this->escape($ttpCategory);
        $lang = $this->escape($this->currentLang);
        
        $sql = "SELECT $columns 
                FROM taxonomies 
                INNER JOIN taxonomy_types ON taxonomies.taxonomy_type_id = taxonomy_types.ttp_id
                WHERE taxonomy_types.ttp_category = '$catName' 
                AND taxonomy_types.ttp_active = 1
                AND taxonomies.t_active = 1 
                AND taxonomies.lang = '$lang' 
                ORDER BY taxonomies.sort_order ASC";

        return $this->db->query($sql);
    }

    /**
     * [工具] 用 Slug 取得分類資訊
     */
    public function getCategoryBySlug($slug) {
        $slug = $this->escape(strtolower($slug));
        $lang = $this->escape($this->currentLang);
        $sql = "SELECT * FROM taxonomies WHERE t_slug = '$slug' AND t_active=1 AND lang = '$lang' AND deleted_at IS NULL LIMIT 1";
        return $this->db->row($sql);
    }

    /**
     * [工具] 用 ID 取得分類資訊
     */
    public function getCategoryById($categoryId) {
        $id = $this->escape($categoryId);
        $lang = $this->escape($this->currentLang);
        $sql = "SELECT * FROM taxonomies WHERE t_id = '$id' AND t_active=1 AND lang = '$lang' AND deleted_at IS NULL LIMIT 1";
        return $this->db->row($sql);
    }

    /**
     * [Blog] 取得文章列表
     * 前提：確認 $fileType 與文章是 1對1 關係 (例如封面圖)
     */
    public function getList($class1, $fileType, $limitStr, $categoryId = null) {
        $c1 = $this->escape($class1);
        $ft = $this->escape($fileType);
        
        $select = "data_set.*, 
                   taxonomies.t_name as category_name, 
                   taxonomies.t_name_en as category_name_en,
                   file_set.file_link1 as cover_link, 
                   file_set.file_title as cover_title";

        $lang = $this->escape($this->currentLang);
        
        $sql = "SELECT $select
                FROM data_set
                LEFT JOIN taxonomies ON data_set.d_class2 = taxonomies.t_id
                LEFT JOIN file_set ON (data_set.d_id = file_set.file_d_id AND file_set.file_type = '$ft')
                WHERE data_set.d_class1='$c1' 
                AND data_set.d_active=1 AND data_set.lang = '$lang'";

        if ($categoryId) {
            $cat = $this->escape($categoryId);
            $sql .= " AND data_set.d_class2 = '$cat'";
            $sql .= " ORDER BY data_set.d_sort ASC $limitStr";
        } else {
            $sql .= " ORDER BY data_set.d_date DESC $limitStr";
        }

        $results = $this->db->query($sql);

        $response = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $coverImage = false;
                if (!empty($row['cover_link'])) {
                    $coverImage = [
                        'file_link1' => $row['cover_link'],
                        'file_title' => $row['cover_title']
                    ];
                }

                unset($row['cover_link'], $row['cover_title']);
                $row['cover_image'] = $coverImage;
                
                $response[] = $row;
            }
        }

        return $response;
    }

    /**
     * [Blog] 取得單篇文章詳情
     */
    public function getDetail($slug, $class1, $withCategory = true) {
        $select = "data_set.*";
        $join   = "";

        if ($withCategory) {
            $select .= ", taxonomies.t_name as category_name";
            $join   = "LEFT JOIN taxonomies ON data_set.d_class2 = taxonomies.t_id";
        }

        // 使用 PDO prepared statement 處理中文和特殊字符
        // 先嘗試帶 lang 條件查詢
        $sql = "SELECT $select
                FROM data_set
                $join
                WHERE d_slug = ?
                AND d_class1 = ? AND data_set.lang = ?
                AND d_active = 1
                LIMIT 1";

        try {
            $result = $this->db->row($sql, [$slug, $class1, $this->currentLang]);
            if ($result) {
                return $result;
            }
        } catch (\Exception $e) {
            // lang 欄位可能不存在，記錄錯誤
            error_log('[DataRepository] getDetail with lang failed: ' . $e->getMessage());
        }

        // 如果沒有結果或出錯，嘗試不帶 lang 條件查詢（向下兼容）
        $sqlNoLang = "SELECT $select
                FROM data_set
                $join
                WHERE d_slug = ?
                AND d_class1 = ?
                AND d_active = 1
                LIMIT 1";

        return $this->db->row($sqlNoLang, [$slug, $class1]);
    }

    /**
     * [副查詢] 專門抓「單張」圖片
     */
    public function getOneFile($d_id, $fileType, $columns = '*') {
        $id = $this->escape($d_id);
        $ft = $this->escape($fileType);

        $sql = "SELECT $columns FROM file_set 
                WHERE file_d_id = '$id' 
                AND file_type = '$ft' 
                ORDER BY file_sort ASC 
                LIMIT 1";

        return $this->db->row($sql);
    }

    /**
     * [附屬資料] 取得某篇文章底下的特定類型檔案
     */
    public function getListFile($d_id, $fileType, $columns = '*') {
        $id = $this->escape($d_id);
        $ft = $this->escape($fileType);

        $sql = "SELECT $columns FROM file_set 
                WHERE file_d_id = '$id' 
                AND file_type = '$ft' 
                ORDER BY file_sort ASC";

        return $this->db->query($sql);
    }

    /**
     * 一次抓取指定 ID 的多種圖片類型
     * * @param int|string $d_id  主資料 ID
     * @param array $types      想要抓取的類型陣列，例如 ['experienceMap', 'experienceBanner']
     * @return array            回傳以 file_type 為 Key 的對照表
     */
    public function getFilesByTypes($d_id, array $types) {
        $id = (int)$d_id;
        if (empty($types) || $id <= 0) {
            return [];
        }

        $safeTypesString = implode("','", array_map('addslashes', $types));

        $sql = "SELECT * FROM file_set 
                WHERE file_d_id = $id 
                AND file_type IN ('$safeTypesString') 
                ORDER BY file_sort ASC"; // 確保如果有排序需求能生效

        $results = $this->db->query($sql);

        $filesMap = [];
        foreach ($results as $row) {
            $type = $row['file_type'];
            $filesMap[$type][] = $row;
        }

        return $filesMap;
    }

    /**
     * 取得資料並合併圖片
     * 核心邏輯：先抓主資料 -> 轉成陣列 -> 進迴圈 -> 抓圖片 -> 合併
     */
    public function getFullDataWithImages($class1, $fileType) {
        $c1 = $this->escape($class1);
        $lang = $this->escape($this->currentLang);
        
        // 修改為使用完整的 SQL 查詢而不是調用 getData
        $sql = "SELECT d_id, d_title FROM data_set WHERE d_class1 = '$c1' AND lang = '$lang' ORDER BY d_sort ASC";
        $dataList = $this->db->query($sql);

        if (empty($dataList)) {
            return []; 
        }

        $ids = [];
        foreach ($dataList as $d) {
            $ids[] = (int)$d['d_id'];
        }
        
        if (empty($ids)) {
            return $dataList;
        }

        $idString = implode(',', $ids);
        $ft = $this->escape($fileType);

        $sql = "SELECT * FROM file_set 
                WHERE file_d_id IN ($idString) 
                AND file_type = '$ft' 
                ORDER BY file_sort ASC";
        
        $allFiles = $this->db->query($sql);

        $filesMap = [];
        if ($allFiles) {
            foreach ($allFiles as $file) {
                $filesMap[$file['file_d_id']][] = $file;
            }
        }

        foreach ($dataList as &$row) {
            $id = $row['d_id'];
            $row['images'] = isset($filesMap[$id]) ? $filesMap[$id] : [];
        }

        return $dataList;
    }

    /**
     * [通用] 增加瀏覽次數 (使用資料庫記錄防止重複計數 - 5分鐘)
     */
    public function incrementView($d_id) {
        // -----------------------------------------------------------
        // [Level 2] 防機器人 (Bot/Crawler Filtering)
        // -----------------------------------------------------------
        // 檢查 User-Agent，如果包含這些關鍵字，直接回傳 false 不計數
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $botKeywords = 'bot|crawl|slurp|spider|mediapartners|facebook|ahrefs|google|bing|yahoo';

        if (preg_match("/{$botKeywords}/i", $userAgent)) {
            return false; // 判定為機器人，不動作
        }

        // -----------------------------------------------------------
        // [Level 1] 防重複觀看 (使用資料庫記錄 IP + 文章 ID)
        // -----------------------------------------------------------
        // 取得訪客 IP 位址
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // 如果使用了代理或負載平衡器，可能需要從 HTTP_X_FORWARDED_FOR 取得真實 IP
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        // 檢查此 IP 在 5 分鐘內是否已經瀏覽過此文章
        $checkSql = "SELECT id FROM view_log
                     WHERE article_id = ?
                     AND ip_address = ?
                     AND viewed_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                     LIMIT 1";
        $existing = $this->db->row($checkSql, [$d_id, $ipAddress]);

        if ($existing) {
            return false; // 5 分鐘內已經看過了，不動作
        }

        // -----------------------------------------------------------
        // [裝置資訊偵測] 解析 User-Agent
        // -----------------------------------------------------------
        $deviceType = $this->detectDeviceType($userAgent);
        $browser = $this->detectBrowser($userAgent);
        $os = $this->detectOS($userAgent);

        // -----------------------------------------------------------
        // [地理位置資訊] 根據 IP 取得國家和城市
        // -----------------------------------------------------------
        $geoData = $this->getGeoLocation($ipAddress);
        $country = $geoData['country'] ?? null;
        $city = $geoData['city'] ?? null;

        // -----------------------------------------------------------
        // [來源頁面]
        // -----------------------------------------------------------
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        // -----------------------------------------------------------
        // [Core] 執行資料庫更新
        // -----------------------------------------------------------
        $id = $this->escape($d_id);
        $lang = $this->escape($this->currentLang);

        $sql = "UPDATE data_set SET d_view = d_view + 1 WHERE d_id = '$id' AND lang = '$lang'";
        $result = $this->db->query($sql);

        // -----------------------------------------------------------
        // [Final] 寫入瀏覽記錄
        // -----------------------------------------------------------
        // 如果資料庫更新成功，記錄此次瀏覽
        $userAgentData = substr($userAgent ?? '', 0, 500);
        $refererData   = substr($referer ?? '', 0, 500);

        if ($result) {
            $insertSql = "INSERT INTO view_log
                        (article_id, ip_address, user_agent, device_type, browser, os, country, city, referer, viewed_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        
            $this->db->query($insertSql, [
                $d_id,
                $ipAddress,
                $userAgentData ?: null,
                $deviceType,
                $browser,
                $os,
                $country,
                $city,
                $refererData ?: null
            ]);
        }

        return $result;
    }

    /**
     * 偵測裝置類型
     */
    private function detectDeviceType($userAgent) {
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'Mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'Tablet';
        }
        return 'Desktop';
    }

    /**
     * 偵測瀏覽器
     */
    private function detectBrowser($userAgent) {
        if (preg_match('/edg/i', $userAgent)) return 'Edge';
        if (preg_match('/chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/safari/i', $userAgent)) return 'Safari';
        if (preg_match('/firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/msie|trident/i', $userAgent)) return 'Internet Explorer';
        if (preg_match('/opera|opr/i', $userAgent)) return 'Opera';
        return 'Unknown';
    }

    /**
     * 偵測作業系統
     */
    private function detectOS($userAgent) {
        if (preg_match('/windows nt 10/i', $userAgent)) return 'Windows 10';
        if (preg_match('/windows nt 11/i', $userAgent)) return 'Windows 11';
        if (preg_match('/windows/i', $userAgent)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $userAgent)) return 'macOS';
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) return 'iOS';
        if (preg_match('/android/i', $userAgent)) return 'Android';
        if (preg_match('/linux/i', $userAgent)) return 'Linux';
        return 'Unknown';
    }

    /**
     * 根據 IP 取得地理位置資訊
     * 使用免費的 ip-api.com 服務（每分鐘限制 45 個請求）
     */
    private function getGeoLocation($ip) {
        // 如果是本地 IP，直接返回
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0') {
            return [
                'country' => 'Local',
                'city' => 'Localhost'
            ];
        }

        try {
            // 使用 ip-api.com 的免費 API
            $url = "http://ip-api.com/json/{$ip}?fields=status,country,city";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2  // 2 秒超時
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response) {
                $data = json_decode($response, true);

                if ($data && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null
                    ];
                }
            }
        } catch (\Exception $e) {
            // 如果 API 呼叫失敗，靜默失敗
        }

        return ['country' => null, 'city' => null];
    }

    /**
     * 輔助方法：根據分類 ID 字串取得分類名稱陣列
     * @param string $idString 逗號分隔的 ID 字串，例如 "1,3,5"
     * @return array 分類名稱陣列
     */
    public function getCategoryNames($idString) {
        if (empty($idString)) {
            return [];
        }

        // 分割 ID 字串
        $ids = array_filter(array_map('trim', explode(',', $idString)));

        if (empty($ids)) {
            return [];
        }

        // 查詢分類名稱
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT t_id, t_name FROM taxonomies WHERE t_id IN ($placeholders) AND t_active = 1 AND deleted_at IS NULL";

        $result = $this->db->query($sql, $ids);

        if (!$result) {
            return [];
        }

        // 轉換為名稱陣列
        return array_map(function($row) {
            return $row['t_name'];
        }, $result);
    }
}
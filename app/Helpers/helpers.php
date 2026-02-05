<?php
/**
 * Global Helper Functions
 * 全域輔助函數
 */

if (!function_exists('hsc')) {
    /**
     * 全域 HTML 跳脫函式
     * @param string|null $string
     * @return string
     */
    function hsc($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('processMedia')) {
    /**
     * 處理內容中的媒體 token 和 data-media-id
     * 自動將 [media:ID] token 和 data-media-id 屬性轉換為實際的圖片 URL
     * 
     * 使用範例:
     * <?= processMedia($work['d_content']) ?>
     * 
     * @param string $content HTML 內容
     * @return string 處理後的內容
     */
    function processMedia($content) {
        if (empty($content)) {
            return $content;
        }
        
        // 取得全域資料庫連線
        global $db;
        if (!$db) {
            error_log('processMedia: Database connection not available');
            return $content;
        }
        
        // 取得前端 URL
        $frontendUrl = defined('APP_FRONTEND_PATH') ? APP_FRONTEND_PATH : '';
        
        try {
            // 建立 MediaHelper 實例
            $mediaHelper = new \App\Helpers\MediaHelper($db, $frontendUrl);
            
            // 處理內容中的媒體 token 和 data-media-id
            return $mediaHelper->processContentWithMixedMode($content);
        } catch (\Exception $e) {
            error_log('processMedia error: ' . $e->getMessage());
            return $content;
        }
    }
}

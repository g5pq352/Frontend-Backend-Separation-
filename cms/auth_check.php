<?php
/**
 * CMS 認證檢查 - 使用 IP 認證
 * 用於後台 AJAX 請求的認證
 */

// 載入資料庫連線
require_once __DIR__ . '/../Connections/connect2data.php';

/**
 * 檢查是否已認證
 * @return bool
 */
function checkCmsAuth() {
    global $conn;

    // 取得客戶端 IP
    $clientIp = getClientIp();

    // 檢查 IP 白名單
    $ipWhitelist = require __DIR__ . '/../config/ip_whitelist.php';
    if (in_array($clientIp, $ipWhitelist)) {
        return true;
    }

    // 檢查資料庫認證
    try {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT COUNT(*) as count
                FROM auth_sessions
                WHERE ip_address = :ip_address
                AND is_active = 1
                AND expires_at > :now";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ip_address' => $clientIp,
            ':now' => $now
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['count']) && $result['count'] > 0;
    } catch (Exception $e) {
        error_log('Auth check error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 取得客戶端 IP
 * @return string
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }

    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * 要求認證,如果未認證則返回錯誤並終止
 */
function requireCmsAuth() {
    if (!checkCmsAuth()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '未授權訪問,請先登入'
        ]);
        exit;
    }
}

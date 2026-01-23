<?php
/**
 * 網站存取密碼設定
 */

return [
    // 網站密碼 (明文,建議之後改用雜湊)
    'password' => '5566',

    // Session 過期時間 (秒)
    'session_lifetime' => 86400, // 24 小時

    // 是否啟用 IP 限制功能
    'enable_ip_restriction' => true,
];

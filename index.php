<?php
/**
 * Application Entry Point
 * 極簡化引導文件
 */

# 1. 初始化 Session (跨網域設定 - 支援手機瀏覽器)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

// 判斷是否為跨網域請求
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isCrossDomain = !empty($origin) && strpos($origin, $_SERVER['HTTP_HOST']) === false;

// 手機瀏覽器(特別是 Safari)對跨網域 Cookie 的要求更嚴格
// 必須確保 secure=true 且 samesite=None
$cookieParams = [
    'lifetime' => 86400, // 24 小時
    'path'     => '/',
    'domain'   => '', // 跨網域必須留空,讓瀏覽器自動處理
    'secure'   => $isHttps, // HTTPS 必須為 true
    'httponly' => true, // 防止 XSS 攻擊
    'samesite' => $isHttps ? 'None' : 'Lax' // HTTPS 環境統一使用 None 支援跨網域
];

session_set_cookie_params($cookieParams);

// 設定 Session 名稱 (避免與其他網站衝突)
session_name('TEMPLATE_SESSION');

// 啟動 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();

    // 手機瀏覽器額外處理:確保 Cookie 被正確設定
    if ($isHttps && $isCrossDomain) {
        // 手動設定 SameSite=None; Secure 的 Cookie header
        header('Set-Cookie: ' . session_name() . '=' . session_id() . '; Path=/; Secure; HttpOnly; SameSite=None; Max-Age=86400', false);
    }
}

# 2. 基礎路徑定義
defined('APP_DIR') OR define('APP_DIR', __DIR__.DIRECTORY_SEPARATOR."app/"); 

# 3. 自動載入與配置
require __DIR__ . '/vendor/autoload.php';

# 載入 .env 環境變數
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require __DIR__ . '/config/config.php';

use DI\Container;
use Slim\Factory\AppFactory;

# 4. 初始化 DI 容器與設定
$container = new Container();
require APP_DIR . "template_set.php";
include APP_DIR . "dependencies.php";

# 5. 建立 Slim 應用程式
AppFactory::setContainer($container);
$app = AppFactory::create();

if (!empty(BASE_PATH)) {
    $app->setBasePath(BASE_PATH);
}

# 【新增】URL 重定向處理
require APP_DIR . "url_redirect.php";

# 6. 載入中間件與路由
require APP_DIR . "middleware.php";
require APP_DIR . "routes_set.php";

# 7. 啟動應用
$app->run();
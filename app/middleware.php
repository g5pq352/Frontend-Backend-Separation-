<?php
use App\Middleware\SecurityHeadersMiddleware;

/**
 * Application Middleware
 */

$app->add(\App\Middleware\LanguageMiddleware::class);
$app->addRoutingMiddleware();

# 1. CSRF 防護 (排除 /admin/ 動作以相容舊版 CMS 介面)
$container->set('csrf', function() use ($app) {
    $guard = new \Slim\Csrf\Guard($app->getResponseFactory());
    $guard->setPersistentTokenMode(true);
    return $guard;
});

// 自定義中間件來排除特定路徑的 CSRF 檢查
$app->add(function($request, $handler) use ($container) {
    $path = $request->getUri()->getPath();
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $relative    = str_replace($basePath, '', $path);

    // 如果是 /admin/ 或 /api/ 開頭的 POST 請求，跳過 CSRF
    if (strpos($relative, '/admin/') === 0 || strpos($relative, '/api/') === 0) {
        return $handler->handle($request);
    }

    return $container->get('csrf')->process($request, $handler);
});

# 2. 安全標頭與基礎連結處理
$app->add(new SecurityHeadersMiddleware());
$app->add(\App\Middleware\BaseUrlMiddleware::class);

# 3. 錯誤處理控器
$displayErrorDetails = true;
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

# 4. CORS 中間件 (必須在最後加入,這樣才會最先執行)
$app->add(\App\Middleware\CorsMiddleware::class);

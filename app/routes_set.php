<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

/**
 * Application Routes
 */

$view = $container->get(PhpRenderer::class);

# 1. 靜態資源處理 (Assets)
// $app->get('/{ignore:.*}images/{path:.+}', function ($request, $response, $args) {
//     $path = $args['path'];
//     $baseDir = dirname(__DIR__) . '/views/images/';
//     $imagePath = realpath($baseDir . $path);
//     if ($imagePath && strpos($imagePath, realpath($baseDir)) === 0 && file_exists($imagePath)) {
//         $response->getBody()->write(file_get_contents($imagePath));
//         $mime = mime_content_type($imagePath) ?: 'application/octet-stream';
//         return $response->withHeader('Content-Type', $mime);
//     }
//     return $response->withStatus(404);
// });

// $app->get('/{ignore:.*}files/{path:.+}', function ($request, $response, $args) {
//     $path = $args['path'];
//     $baseDir = dirname(__DIR__) . '/views/files/ ';
//     $filePath = realpath($baseDir . $path);
//     if ($filePath && strpos($filePath, realpath($baseDir)) === 0 && file_exists($filePath)) {
//         $response->getBody()->write(file_get_contents($filePath));
//         $mime = mime_content_type($filePath) ?: 'application/octet-stream';
//         return $response->withHeader('Content-Type', $mime);
//     }
//     return $response->withStatus(404);
// });

$app->get('/{ignore:.*}images/{path:.+}', function ($request, $response, $args) {
    $path = $args['path'];
    $baseDir = dirname(__DIR__) . '/template/img/images/';
    $imagePath = realpath($baseDir . $path);
    if ($imagePath && strpos($imagePath, realpath($baseDir)) === 0 && file_exists($imagePath)) {
        $response->getBody()->write(file_get_contents($imagePath));
        $mime = mime_content_type($imagePath) ?: 'application/octet-stream';
        return $response->withHeader('Content-Type', $mime);
    }
    return $response->withStatus(404);
});

$app->get('/{ignore:.*}img/{path:.+}', function ($request, $response, $args) {
    $path = $args['path'];
    $baseDir = dirname(__DIR__) . '/template/img/';
    $imagePath = realpath($baseDir . $path);
    if ($imagePath && strpos($imagePath, realpath($baseDir)) === 0 && file_exists($imagePath)) {
        $response->getBody()->write(file_get_contents($imagePath));
        $mime = mime_content_type($imagePath) ?: 'application/octet-stream';
        return $response->withHeader('Content-Type', $mime);
    }
    return $response->withStatus(404);
});

$app->get('/{ignore:.*}files/{path:.+}', function ($request, $response, $args) {
    $path = $args['path'];
    $baseDir = dirname(__DIR__) . '/template/files/ ';
    $filePath = realpath($baseDir . $path);
    if ($filePath && strpos($filePath, realpath($baseDir)) === 0 && file_exists($filePath)) {
        $response->getBody()->write(file_get_contents($filePath));
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        return $response->withHeader('Content-Type', $mime);
    }
    return $response->withStatus(404);
});

# 2. 錯誤頁面 (Error Pages)
$app->get('/404', function ($request, $response) use ($view) {
    return $view->render($response, '404.php');
});

# 3. 前台頁面路由 (Page Routes)

/**
 * 輔助函數：自動註冊帶語系前綴的路由（支援多語系）
 * @param object $app Slim App 實例
 * @param string $method HTTP 方法 (get/post)
 * @param string $pattern 路由模式
 * @param array $handler 控制器處理器
 */
function registerRoute($app, $method, $pattern, $handler) {
    // 註冊預設路由（無語系前綴，使用預設語系）
    $app->$method($pattern, $handler);
    
    // 註冊語系前綴路由（支援 2-3 個字母的語系代碼，例如：en, jp, tw, cn）
    $langPattern = '/{lang:[a-z]{2,3}}' . $pattern;
    $app->$method($langPattern, $handler);
}

# 4. API 路由 (Protected)
$app->group('/api', function ($group) {
    // 首頁api
    $group->get('/home-data', [\App\Controllers\HomeController::class, 'index']);

    // Portfolio api
    $group->get('/portfolio-list', [\App\Controllers\PortfolioController::class, 'getPortfolioList']);
    $group->get('/portfolio-detail/{slug}', [\App\Controllers\PortfolioController::class, 'getPortfolioDetail']);
})->add(new \App\Middleware\ApiAuthMiddleware());

# 4.1 IP 限制與密碼驗證 API (Public - 不需要 API Token)
$app->group('/api', function ($group) {
    // 檢查存取權限
    $group->get('/check-access', [\App\Controllers\AuthController::class, 'checkAccess']);

    // 驗證密碼
    $group->post('/verify-password', [\App\Controllers\AuthController::class, 'verifyPassword']);

    // 登出
    $group->post('/logout', [\App\Controllers\AuthController::class, 'logout']);
});
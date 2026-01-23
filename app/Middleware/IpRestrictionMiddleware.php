<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class IpRestrictionMiddleware
{
    private $ipWhitelist;
    private $sitePassword;

    public function __construct()
    {
        $this->ipWhitelist = require __DIR__ . '/../../config/ip_whitelist.php';
        $this->sitePassword = require __DIR__ . '/../../config/site_password.php';
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // 如果功能未啟用,直接通過
        if (!$this->sitePassword['enable_ip_restriction']) {
            return $handler->handle($request);
        }

        // 取得訪客 IP
        $clientIp = $this->getClientIp($request);

        // 檢查是否在白名單中
        if ($this->isIpWhitelisted($clientIp)) {
            // 白名單 IP,直接通過
            return $handler->handle($request);
        }

        // 檢查 Session 是否已驗證
        if ($this->isAuthenticated()) {
            // 已經驗證過密碼,通過
            return $handler->handle($request);
        }

        // 需要密碼驗證,返回未授權狀態
        $response = new Response();
        $data = [
            'status' => 'unauthorized',
            'message' => 'Access denied. Password required.',
            'requires_password' => true,
            'client_ip' => $clientIp
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(401);
    }

    /**
     * 取得客戶端真實 IP
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();

        // 優先從代理伺服器頭部取得真實 IP
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }

        if (!empty($serverParams['HTTP_CLIENT_IP'])) {
            return $serverParams['HTTP_CLIENT_IP'];
        }

        return $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * 檢查 IP 是否在白名單中
     */
    private function isIpWhitelisted(string $ip): bool
    {
        return in_array($ip, $this->ipWhitelist);
    }

    /**
     * 檢查是否已通過密碼驗證
     */
    private function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 檢查 Session 是否存在且未過期
        if (!isset($_SESSION['site_authenticated'])) {
            return false;
        }

        if (!isset($_SESSION['auth_timestamp'])) {
            return false;
        }

        // 檢查是否過期
        $lifetime = $this->sitePassword['session_lifetime'];
        $elapsed = time() - $_SESSION['auth_timestamp'];

        if ($elapsed > $lifetime) {
            // Session 過期,清除
            unset($_SESSION['site_authenticated']);
            unset($_SESSION['auth_timestamp']);
            return false;
        }

        // 更新時間戳
        $_SESSION['auth_timestamp'] = time();

        return true;
    }
}

<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AuthSessionModel;

class AuthController extends Controller
{
    private $sitePassword;
    private $ipWhitelist;
    private $authSessionModel;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->sitePassword = require __DIR__ . '/../../config/site_password.php';
        $this->ipWhitelist = require __DIR__ . '/../../config/ip_whitelist.php';
        $this->authSessionModel = new AuthSessionModel($container);
    }

    /**
     * 檢查訪問權限
     * GET /api/check-access
     */
    public function checkAccess(Request $request, Response $response, $args)
    {
        $clientIp = $this->getClientIp($request);
        $isWhitelisted = in_array($clientIp, $this->ipWhitelist);
        $isAuthenticated = $this->authSessionModel->isAuthenticated($clientIp);

        // 如果已認證,延長認證時間
        if ($isAuthenticated) {
            $this->authSessionModel->extendAuthSession($clientIp);
        }

        $authSession = $this->authSessionModel->getAuthSession($clientIp);

        $data = [
            'status' => 'success',
            'data' => [
                'has_access' => $isWhitelisted || $isAuthenticated,
                'is_whitelisted' => $isWhitelisted,
                'is_authenticated' => $isAuthenticated,
                'client_ip' => $clientIp,
                'requires_password' => !$isWhitelisted && !$isAuthenticated,
                'auth_info' => $authSession ? [
                    'authenticated_at' => $authSession['authenticated_at'],
                    'expires_at' => $authSession['expires_at']
                ] : null
            ],
            'timestamp' => time()
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * 驗證密碼
     * POST /api/verify-password
     */
    public function verifyPassword(Request $request, Response $response, $args)
    {
        // 嘗試多種方式獲取請求體
        $body = $request->getParsedBody();
        $rawBody = (string) $request->getBody();

        // 如果 getParsedBody() 返回 null,嘗試手動解析 JSON
        if ($body === null && !empty($rawBody)) {
            $body = json_decode($rawBody, true);
        }

        $password = $body['password'] ?? '';
        $clientIp = $this->getClientIp($request);
        $serverParams = $request->getServerParams();
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? '';

        // 驗證密碼
        if ($password === $this->sitePassword['password']) {
            // 密碼正確,記錄到資料庫 (預設 2 小時 = 7200 秒)
            $expiresInSeconds = $this->sitePassword['session_lifetime'] ?? 7200;
            $success = $this->authSessionModel->createAuthSession($clientIp, $userAgent, $expiresInSeconds);

            if ($success) {
                $authSession = $this->authSessionModel->getAuthSession($clientIp);

                $data = [
                    'status' => 'success',
                    'message' => 'Password verified successfully',
                    'data' => [
                        'authenticated' => true,
                        'expires_in' => $expiresInSeconds,
                        'client_ip' => $clientIp,
                        'auth_info' => [
                            'authenticated_at' => $authSession['authenticated_at'],
                            'expires_at' => $authSession['expires_at']
                        ]
                    ],
                    'timestamp' => time()
                ];

                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->withStatus(200);
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Failed to create auth session',
                    'data' => [
                        'authenticated' => false
                    ],
                    'timestamp' => time()
                ];

                $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
                return $response
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->withStatus(500);
            }
        } else {
            // 密碼錯誤
            $data = [
                'status' => 'error',
                'message' => 'Invalid password',
                'data' => [
                    'authenticated' => false
                ],
                'timestamp' => time()
            ];

            $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(401);
        }
    }

    /**
     * 登出
     * POST /api/logout
     */
    public function logout(Request $request, Response $response, $args)
    {
        $clientIp = $this->getClientIp($request);
        $this->authSessionModel->deleteAuthSession($clientIp);

        $data = [
            'status' => 'success',
            'message' => 'Logged out successfully',
            'timestamp' => time()
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(200);
    }

    /**
     * 取得客戶端 IP
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();

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
}

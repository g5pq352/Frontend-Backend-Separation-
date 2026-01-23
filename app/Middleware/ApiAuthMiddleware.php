<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class ApiAuthMiddleware
{

    public function __invoke(Request $request, RequestHandler $handler): Response
    {

        $referer = $request->getHeaderLine('Referer');
        $origin = $request->getHeaderLine('Origin');
        $host = $request->getHeaderLine('Host');

        $allowedDomains = [
            '127.0.0.1',
            'localhost',
            'template.server-goods-design.com',
            'backedapi.gdlinode.tw'
        ];

        $originHost = parse_url($origin, PHP_URL_HOST);
        $refererHost = parse_url($referer, PHP_URL_HOST);

        $isLocalDev = in_array($host, ['127.0.0.1', 'localhost']);

        $validSource =
            in_array($originHost, $allowedDomains, true) ||
            in_array($refererHost, $allowedDomains, true);


        if (
            (empty($origin) && empty($referer) && in_array($host, $allowedDomains, true))
            ||
            in_array($originHost, $allowedDomains, true) ||
            in_array($refererHost, $allowedDomains, true)
        ) {
            $validSource = true;
        }


        if (!$isLocalDev && !$validSource) {

            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Forbidden: Invalid Origin or Referer',
                'debug_origin' => $origin,
                'debug_referer' => $referer,
                'debug_originHost' => $originHost,
                'debug_refererHost' => $refererHost,
                'debug_host' => $host,
            ], JSON_UNESCAPED_UNICODE));

            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }



        // Token 驗證
        $serverToken = $_ENV['API_TOKEN'] ?? getenv('API_TOKEN');
        $clientToken = $request->getHeaderLine('X-API-TOKEN');

        if (!empty($serverToken) && $clientToken !== $serverToken) {

            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Forbidden: Invalid Token'
            ], JSON_UNESCAPED_UNICODE));

            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}

<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class CorsMiddleware {

    public function __invoke(Request $request, RequestHandler $handler): Response {

        $origin = $request->getHeaderLine('Origin');

        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://template.server-goods-design.com',
            'https://template.server-goods-design.com',
        ];

        $isAllowed = in_array($origin, $allowedOrigins, true);

        // ---- Preflight OPTIONS ----
        if ($request->getMethod() === 'OPTIONS') {

            $response = new Response();

            if ($isAllowed) {
                return $this->applyHeaders($response, $origin)
                    ->withStatus(200);
            }

            return $response->withStatus(204);
        }

        // ---- Handle actual request ----
        $response = $handler->handle($request);

        if ($isAllowed) {
            return $this->applyHeaders($response, $origin);
        }

        return $response;
    }


    private function applyHeaders(Response $response, string $origin): Response
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'X-API-TOKEN, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Vary', 'Origin');
    }
}

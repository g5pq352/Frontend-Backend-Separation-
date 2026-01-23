<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\PhpRenderer;

class BaseUrlMiddleware {
    protected $view;
    protected $basePath;

    public function __construct(PhpRenderer $view, string $basePath) {
        $this->view = $view;
        $this->basePath = $basePath;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        $uri = $request->getUri();
        $baseurl = rtrim($uri->getScheme() . '://' . $uri->getAuthority() . $this->basePath, '/');
        $current_url = (string)$uri;

        $this->view->addAttribute('baseurl', $baseurl);
        $this->view->addAttribute('current_url', $current_url);

        return $handler->handle($request);
    }
}

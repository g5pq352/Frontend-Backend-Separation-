<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

class HomeController extends Controller
{
    /**
     * Protected API Endpoint
     * GET /api/home-data
     */
    public function index(Request $request, Response $response, $args) {
        $portfolioList = $this->repo->getQuery('portfolio', 'portfolioCover', 'd_title, d_slug, d_content, d_data1, d_class2, d_class3, d_class4, d_class5, d_class6, d_class7, file_link1, file_title, d_date');

        // 取得所有篩選器的分類資料
        $typeList = $this->repo->getCategory('typeC', 't_id, t_name');
        $categoryList = $this->repo->getCategory('categoryC', 't_id, t_name');
        $colorList = $this->repo->getCategory('colorC', 't_id, t_name');
        $tagList = $this->repo->getCategory('tagC', 't_id, t_name');
        $authorList = $this->repo->getCategory('authorC', 't_id, t_name');
        $projectList = $this->repo->getCategory('projectC', 't_id, t_name');

        $data = [
            'status' => 'success',
            'data' => [
                'portfolio' => $portfolioList,
                'filters' => [
                    'type' => $typeList ?: [],
                    'category' => $categoryList ?: [],
                    'color' => $colorList ?: [],
                    'tag' => $tagList ?: [],
                    'author' => $authorList ?: [],
                    'project' => $projectList ?: []
                ]
            ],
            'timestamp' => time()
        ];

        // 編碼為 JSON，並處理可能的錯誤
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 檢查 JSON 編碼是否成功
        if ($jsonData === false) {
            $errorData = [
                'status' => 'error',
                'message' => 'JSON encoding failed: ' . json_last_error_msg(),
                'timestamp' => time()
            ];
            $jsonData = json_encode($errorData);
        }

        $response->getBody()->write($jsonData);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(200);
    }
}
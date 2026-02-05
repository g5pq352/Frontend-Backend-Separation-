<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

class ModuleController extends Controller
{
    /**
     * Protected API Endpoint
     * GET /api/module-list?category={t_id}
     */
    public function index(Request $request, Response $response, $args) {
        $moduleCategory = $this->repo->getCategory('moduleC');
        
        // 使用原本的 getQuery，永遠返回所有資料（按 d_sort 排序）
        $moduleList = $this->repo->getQuery(
            'module', 
            'moduleCover', 
            'data_set.d_id, data_set.d_title, data_set.d_slug, data_set.d_content, data_set.d_data1, data_set.d_class2, data_set.d_class3, data_set.d_class4, data_set.d_class5, data_set.d_class6, data_set.d_class7, file_set.file_link1, file_set.file_title, data_set.d_date'
        );

        $data = [
            'status' => 'success',
            'data' => [
                'moduleCategory' => $moduleCategory,
                'module' => $moduleList,
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
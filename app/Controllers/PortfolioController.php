<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Psr\Container\ContainerInterface;

class PortfolioController extends Controller {
    protected $model;
    protected $perPage = 6;

    public function __construct(ContainerInterface $container) {
        parent::__construct($container);
    }

    /**
     * API: 取得 Portfolio 列表
     * GET /api/portfolio-list
     */
    public function getPortfolioList(Request $request, Response $response, $args) {
        // 取得查詢參數
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;

        // 取得 Portfolio 資料
        $portfolioList = $this->repo->getQuery('portfolio', 'portfolioCover',
            'd_id, d_title, d_slug, d_content, d_date, file_link1, file_title',
            $limit
        );

        // 取得總數
        $countSql = "SELECT COUNT(*) as total FROM data_set WHERE d_class1 = 'portfolio' AND d_active = 1";
        $countResult = $this->db->row($countSql);
        $total = $countResult['total'] ?? 0;

        $data = [
            'status' => 'success',
            'data' => [
                'portfolio' => $portfolioList ?: [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $limit)
                ]
            ],
            'timestamp' => time()
        ];

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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

    /**
     * API: 取得單篇 Portfolio 詳細資料
     * GET /api/portfolio-detail/{slug}
     */
    public function getPortfolioDetail(Request $request, Response $response, $args) {
        try {
            $slug = $args['slug'] ?? null;

            if (!$slug) {
                $errorData = [
                    'status' => 'error',
                    'message' => 'Slug parameter is required',
                    'timestamp' => time()
                ];
                $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE));
                return $response
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->withStatus(400);
            }

            // 不使用 addslashes，讓 PDO 的 prepared statement 處理
            $row = $this->repo->getDetail($slug, 'portfolio');

            if (!$row) {
                $errorData = [
                    'status' => 'error',
                    'message' => 'Portfolio not found',
                    'slug' => $slug,
                    'timestamp' => time()
                ];
                $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE));
                return $response
                    ->withHeader('Content-Type', 'application/json; charset=utf-8')
                    ->withStatus(404);
            }

            $d_id = $row['d_id'];

            // 增加瀏覽次數（包裝在 try-catch 中，避免因為 view_log 表不存在而中斷）
            try {
                $this->repo->incrementView($d_id);
            } catch (\Exception $e) {
                // 記錄但不中斷執行
                error_log('[Portfolio API] incrementView failed: ' . $e->getMessage());
            }

            // 取得封面圖片（單張）
            $coverImage = null;
            $coverOg = null;
            $images = [];
            $categories = [
                'type' => [],
                'category' => [],
                'color' => [],
                'tags' => [],
                'auth' => [],
                'project' => [],
            ];

            try {
                $coverImage = $this->repo->getOneFile($d_id, 'portfolioCover', 'file_link1, file_title');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getOneFile (portfolioCover) failed: ' . $e->getMessage());
            }

            try {
                $coverOg = $this->repo->getOneFile($d_id, 'portfolioOg', 'file_link1, file_title');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getOneFile (portfolioOg) failed: ' . $e->getMessage());
            }

            try {
                $images = $this->repo->getListFile($d_id, 'image', 'file_link1, file_title');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getListFile failed: ' . $e->getMessage());
            }

            // 取得分類資訊（每個都包裝在 try-catch 中）
            try {
                $categories['type'] = $this->repo->getCategoryNames($row['d_class2'] ?? '');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getCategoryNames (type) failed: ' . $e->getMessage());
            }

            try {
                $categories['category'] = $this->repo->getCategoryNames($row['d_class3'] ?? '');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getCategoryNames (category) failed: ' . $e->getMessage());
            }

            try {
                $categories['color'] = $this->repo->getCategoryNames($row['d_class4'] ?? '');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getCategoryNames (color) failed: ' . $e->getMessage());
            }

            try {
                $categories['tags'] = $this->repo->getCategoryNames($row['d_class5'] ?? '');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getCategoryNames (tags) failed: ' . $e->getMessage());
            }

            try {
                $categories['auth'] = $this->repo->getCategoryNames($row['d_class6'] ?? '');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getCategoryNames (auth) failed: ' . $e->getMessage());
            }

            try {
                $categories['project'] = $this->repo->getCategoryNames($row['d_class7'] ?? '');
            } catch (\Exception $e) {
                error_log('[Portfolio API] getCategoryNames (project) failed: ' . $e->getMessage());
            }

            $data = [
                'status' => 'success',
                'data' => [
                    'portfolio' => $row,
                    'cover_image' => $coverImage,
                    'cover_og' => $coverOg,
                    'images' => $images ?: [],
                    'categories' => $categories
                ],
                'timestamp' => time()
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($jsonData === false) {
                throw new \Exception('JSON encoding failed: ' . json_last_error_msg());
            }

            $response->getBody()->write($jsonData);

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(200);

        } catch (\Exception $e) {
            // 記錄錯誤到日誌
            error_log('[Portfolio API Error] ' . $e->getMessage() . ' | Slug: ' . ($slug ?? 'N/A'));
            error_log('[Portfolio API Error] Stack trace: ' . $e->getTraceAsString());

            $errorData = [
                'status' => 'error',
                'message' => 'Internal server error',
                'debug' => [
                    'error' => $e->getMessage(),
                    'slug' => $slug ?? null,
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ],
                'timestamp' => time()
            ];

            $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE));

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(500);
        }
    }
}
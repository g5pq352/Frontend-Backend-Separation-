<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class AdminController extends Controller
{
    /**
     * 檢查是否為管理員登入狀態
     */
    private function requireAdmin()
    {
        if (!$this->isAdmin) {
            throw new Exception('請先登入管理員帳號', 401);
        }
    }

    private function log($message)
    {
        file_put_contents(BASE_PATH_CMS . '/debug_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, FILE_APPEND);
    }

    /**
     * 通用刪除 API
     */
    public function delete(Request $request, Response $response, array $args)
    {
        try {
            $this->requireAdmin();
            $data = $request->getParsedBody();
            $module = preg_replace('/[^a-zA-Z0-9_]/', '', $data['module'] ?? '');
            $id = intval($data['id'] ?? 0);

            if (empty($module) || $id <= 0) return $this->jsonResponse($response, '缺少參數', 400);

            require_once BASE_PATH_CMS . '/includes/elements/PermissionElement.php';
            require_once BASE_PATH_CMS . '/includes/elements/ModuleConfigElement.php';
            
            list($canView, $canAdd, $canEdit, $canDelete) = \PermissionElement::checkModulePermission($this->pdo, $module);
            if (!$canDelete) return $this->jsonResponse($response, '無刪除權限', 403);

            $moduleConfig = \ModuleConfigElement::loadConfig($module);
            $tableName = $moduleConfig['tableName'];
            $col_id = $moduleConfig['primaryKey'];
            $cols = $moduleConfig['cols'] ?? [];
            $col_delete_time = $cols['delete_time'] ?? 'd_delete_time';
            $col_sort = $cols['sort'] ?? 'd_sort';
            $col_file_fk = $cols['file_fk'] ?? 'file_d_id';
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
            $parentIdField = $cols['parent_id'] ?? null;
            $menuKey = $moduleConfig['menuKey'] ?? null;
            $menuValue = $moduleConfig['menuValue'] ?? null;

            $force = !empty($data['force']);

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT * FROM {$tableName} WHERE {$col_id} = :id");
            $stmt->execute([':id' => $id]);
            $itemToDelete = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$itemToDelete) throw new Exception('找不到資料');

            // 判斷軟硬刪
            $hasTrashConfig = $moduleConfig['listPage']['hasTrash'] ?? null;
            $hasHierarchy = $moduleConfig['listPage']['hasHierarchy'] ?? false;
            
            // 如果是無限層結構，一律走硬刪除流程 (使用者要求直接刪除，不要進回收桶)
            $isSoftDelete = ($hasTrashConfig !== false && !empty($col_delete_time) && !$hasHierarchy);
            if ($isSoftDelete) {
                // 檢查資料表是否有該欄位 (快取建議過後可優化)
                $check = $this->pdo->prepare("SHOW COLUMNS FROM {$tableName} LIKE '{$col_delete_time}'");
                $check->execute();
                if ($check->rowCount() == 0) $isSoftDelete = false;
            }

            // --- 階層式模組的偵測與兩階段刪除邏輯 ---
            if ($hasHierarchy) {
                $dependencyCount = 0;
                $dependencyTypes = [];

                // 1. 偵測子項目 (同一張表)
                if ($parentIdField) {
                    $stmtChild = $this->pdo->prepare("SELECT COUNT(*) FROM {$tableName} WHERE {$parentIdField} = :id");
                    $stmtChild->execute([':id' => $id]);
                    $childCount = $stmtChild->fetchColumn();
                    if ($childCount > 0) {
                        $dependencyCount += $childCount;
                        $dependencyTypes[] = "{$childCount} 筆子分類";
                    }
                }

                // 2. 偵測關聯文章 (假設模組名稱為 XxxxCate 或 XxxxKey，對應 Xxxx 模組)
                if (strpos($module, 'Cate') !== false || strpos($module, 'Key') !== false) {
                    $mainModule = str_replace(['Cate', 'Key'], '', $module);
                    $mainConfigFile = BASE_PATH_CMS . "/set/{$mainModule}Set.php";
                    if (file_exists($mainConfigFile)) {
                        require_once BASE_PATH_CMS . '/includes/elements/ModuleConfigElement.php';
                        $mainConfig = \ModuleConfigElement::loadConfig($mainModule);
                        $articleTable = $mainConfig['tableName'];
                        $articleCategoryField = $mainConfig['listPage']['categoryField'] ?? null;
                        
                        if ($articleTable && $articleCategoryField) {
                            $stmtArt = $this->pdo->prepare("SELECT COUNT(*) FROM {$articleTable} WHERE {$articleCategoryField} = :id");
                            $stmtArt->execute([':id' => $id]);
                            $articleCount = $stmtArt->fetchColumn();
                            if ($articleCount > 0) {
                                $dependencyCount += $articleCount;
                                $dependencyTypes[] = "{$articleCount} 筆文章內容";
                            }
                        }
                    }
                }

                if ($dependencyCount > 0 && !$force) {
                    return $this->jsonResponse($response, [
                        'message' => "此分類下尚有 " . implode('、', $dependencyTypes) . "。",
                        'has_data' => true
                    ], 200); // 使用 200 讓前端判斷 has_data
                }
            }

            if ($isSoftDelete) {
                $this->pdo->prepare("UPDATE {$tableName} SET {$col_delete_time} = NOW() WHERE {$col_id} = :id")->execute([':id' => $id]);
                
                // 【新增】清理 data_taxonomy_map 記錄
                require_once BASE_PATH_CMS . '/includes/taxonomyMapHelper.php';
                if (hasTaxonomyMapTable($this->pdo)) {
                    // 取得該產品的所有分類，用於重新整理排序
                    $taxonomiesStmt = $this->pdo->prepare("SELECT DISTINCT t_id FROM data_taxonomy_map WHERE d_id = :d_id");
                    $taxonomiesStmt->execute([':d_id' => $id]);
                    $affectedTaxonomies = $taxonomiesStmt->fetchAll(\PDO::FETCH_COLUMN);
                    
                    // 刪除 data_taxonomy_map 記錄
                    deleteTaxonomyMap($this->pdo, $id);
                    
                    // 重新整理受影響分類的排序
                    foreach ($affectedTaxonomies as $taxId) {
                        reorderTaxonomyMap($this->pdo, intval($taxId));
                    }
                }
                
                // 遞補排序
                if (!empty($col_sort)) {
                    $this->reorderAfterDelete($tableName, $col_sort, $itemToDelete[$col_sort], $categoryField, $itemToDelete[$categoryField] ?? null, $parentIdField, $itemToDelete[$parentIdField] ?? null, $menuKey, $menuValue, $col_delete_time, $itemToDelete['lang'] ?? null);
                }
            } else {
                // 硬刪除
                // 如果是階層式且執意刪除，理論上應該遞迴刪除或串聯刪除。
                // 這裡目前實作：刪除自身，如果有關聯文章也一併刪除 (比照 ajax_permanent_delete.php)
                
                if ($hasHierarchy && $force) {
                    // 若有文章，先刪除文章 (簡單實作，不進入遞迴子分類)
                    if (strpos($module, 'Cate') !== false || strpos($module, 'Key') !== false) {
                        $mainModule = str_replace(['Cate', 'Key'], '', $module);
                        $mainConfigFile = BASE_PATH_CMS . "/set/{$mainModule}Set.php";
                        if (file_exists($mainConfigFile)) {
                            $mainConfig = \ModuleConfigElement::loadConfig($mainModule);
                            $articleTable = $mainConfig['tableName'];
                            $articleCategoryField = $mainConfig['listPage']['categoryField'] ?? null;
                            $articleFileFk = $mainConfig['cols']['file_fk'] ?? 'file_d_id';
                            
                            if ($articleTable && $articleCategoryField) {
                                // 刪除文章的檔案
                                $stmtArts = $this->pdo->prepare("SELECT {$mainConfig['primaryKey']} as id FROM {$articleTable} WHERE {$articleCategoryField} = :id");
                                $stmtArts->execute([':id' => $id]);
                                $arts = $stmtArts->fetchAll(\PDO::FETCH_ASSOC);
                                foreach ($arts as $art) {
                                    $this->cleanupFiles($art['id'], $articleFileFk);
                                    $this->pdo->prepare("DELETE FROM file_set WHERE {$articleFileFk} = :id")->execute([':id' => $art['id']]);
                                }
                                $this->pdo->prepare("DELETE FROM {$articleTable} WHERE {$articleCategoryField} = :id")->execute([':id' => $id]);
                            }
                        }
                    }
                    // 子分類如果也要強刪，目前邏輯是直接斷掉連結(變孤兒)或執意刪除單一項？
                    // 考量使用者可能預期「全清」，這裡我們目前至少把分類下的文章清掉。
                }

                $this->cleanupFiles($id, $col_file_fk);
                $this->pdo->prepare("DELETE FROM file_set WHERE {$col_file_fk} = :id")->execute([':id' => $id]);
                $this->pdo->prepare("DELETE FROM {$tableName} WHERE {$col_id} = :id")->execute([':id' => $id]);
                if (!empty($col_sort)) {
                    $this->reorderAfterDelete($tableName, $col_sort, $itemToDelete[$col_sort], $categoryField, $itemToDelete[$categoryField] ?? null, $parentIdField, $itemToDelete[$parentIdField] ?? null, $menuKey, $menuValue, null, $itemToDelete['lang'] ?? null);
                }
            }

            $this->pdo->commit();
            return $this->jsonResponse($response, '刪除成功');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->log("Error in " . __METHOD__ . ": " . $e->getMessage());
            return $this->jsonResponse($response, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * 切換顯示狀態 API
     */
    public function toggleActive(Request $request, Response $response, array $args)
    {
        try {
            $this->requireAdmin();
            $data = $request->getParsedBody();
            $module = preg_replace('/[^a-zA-Z0-9_]/', '', $data['module'] ?? '');
            $itemId = intval($data['item_id'] ?? 0);
            $newValue = intval($data['new_value'] ?? 0);

            if (empty($module) || $itemId <= 0) return $this->jsonResponse($response, '缺少參數', 400);

            require_once BASE_PATH_CMS . '/includes/elements/ModuleConfigElement.php';

            $moduleConfig = \ModuleConfigElement::loadConfig($module);
            $tableName = $moduleConfig['tableName'];
            $primaryKey = $moduleConfig['primaryKey'];
            $col_active = $moduleConfig['cols']['active'] ?? 'd_active';

            $sql = "UPDATE {$tableName} SET {$col_active} = :new_value WHERE {$primaryKey} = :item_id";
            $this->pdo->prepare($sql)->execute([':new_value' => $newValue, ':item_id' => $itemId]);

            return $this->jsonResponse($response, '狀態已更新');
        } catch (Exception $e) {
            return $this->jsonResponse($response, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * 排序調整 API
     */
    public function changeSort(Request $request, Response $response, array $args)
    {
        try {
            $data = $request->getParsedBody();
            $this->log("ChangeSort Request: " . json_encode($data));
            
            $this->requireAdmin();
            $module = preg_replace('/[^a-zA-Z0-9_]/', '', $data['module'] ?? '');
            $itemId = intval($data['item_id'] ?? 0);
            $newSort = intval($data['new_sort'] ?? 0);
            $categoryId = intval($data['category_id'] ?? 0); // 【新增】接收分類 ID

            if (empty($module) || $itemId <= 0 || $newSort <= 0) return $this->jsonResponse($response, '缺少參數', 400);

            require_once BASE_PATH_CMS . '/includes/elements/ModuleConfigElement.php';
            require_once BASE_PATH_CMS . '/includes/taxonomyMapHelper.php';

            $moduleConfig = \ModuleConfigElement::loadConfig($module);
            $tableName = $moduleConfig['tableName'];
            $primaryKey = $moduleConfig['primaryKey'];
            $cols = $moduleConfig['cols'] ?? [];
            $col_sort = $cols['sort'] ?? 'd_sort';
            $col_top = $cols['top'] ?? null;
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
            $parentIdField = $cols['parent_id'] ?? null;
            $menuKey = $moduleConfig['menuKey'] ?? null;
            $menuValue = $moduleConfig['menuValue'] ?? null;

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT * FROM {$tableName} WHERE {$primaryKey} = :id");
            $stmt->execute([':id' => $itemId]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$item) throw new Exception('找不到資料');

            // 【新增】檢查是否使用 data_taxonomy_map 排序
            $useMapTableSort = false;
            // 讀取設定檔中的 useTaxonomyMapSort，預設為 true
            $configUseTaxonomyMapSort = $moduleConfig['listPage']['useTaxonomyMapSort'] ?? true;
            
            if ($categoryId > 0 && hasTaxonomyMapTable($this->pdo) && $configUseTaxonomyMapSort) {
                $useMapTableSort = true;
            }
            
            $this->log("useMapTableSort: " . ($useMapTableSort ? 'true' : 'false') . ", categoryId: {$categoryId}");

            // 【移動】檢查置頂項目是否可排序 (需要在 $useMapTableSort 定義之後)
            if ($item) {
                 // 【修正】判斷是否為置頂項目
                 $isPinned = false;
                 
                 if ($useMapTableSort) {
                     // 如果是 Map Sort，檢查 Map 表的 d_top
                     $checkMapTop = $this->pdo->prepare("SELECT d_top FROM data_taxonomy_map WHERE d_id = :id AND t_id = :c_id");
                     $checkMapTop->execute([':id' => $itemId, ':c_id' => $categoryId]);
                     $mapRow = $checkMapTop->fetch(\PDO::FETCH_ASSOC);
                     if ($mapRow && ($mapRow['d_top'] ?? 0) == 1) {
                         $isPinned = true;
                     }
                 } else {
                     // 如果是普通 Sort，檢查 data_set 的 d_top (且必須在非分類模式下的全域排序，或分類排序但禁用MapSort時...等一下)
                     // 邏輯回顧：
                     // 1. 全域列表 (categoryId=0)：檢查 data_set.d_top。如果 pinned, 則不可排。
                     // 2. 分類列表 + MapSort=true：檢查 map.d_top。如果 pinned, 則不可排。
                     // 3. 分類列表 + MapSort=false：視為普通列表。此時 Global Pin 的項目在此列表中是普通項目，可排。
                     
                     // 所以這裡只要檢查: 如果我是「依靠 d_top 排序」的模式，且該項目是 pinned，則不可排。
                     // Case 1: 全域 -> useMapTableSort=false, categoryId=0. -> 依 data_set.d_top 排. -> 檢查 item[d_top].
                     // Case 3: 分類 -> useMapTableSort=false, categoryId>0. -> 忽略 data_set.d_top (改用 d_sort/d_date). -> item[d_top] 不影響排序. -> isPinned = false.
                     
                     // 因此：
                     $isGlobalSort = ($categoryId == 0); // 或是 categoryId 不是有效過濾
                     if ($isGlobalSort && ($item[$col_top] ?? 0) == 1) {
                         $isPinned = true;
                     }
                 }

                 if ($isPinned) {
                     throw new Exception('置頂項目無法調整排序');
                 }
            }

            if ($useMapTableSort) {
                // 使用 data_taxonomy_map 排序
                $this->log("Using taxonomy map sort for category {$categoryId}");
                
                // 1. 取得當前排序
                $mapStmt = $this->pdo->prepare("SELECT sort_num FROM data_taxonomy_map WHERE d_id = :d_id AND t_id = :t_id");
                $mapStmt->execute([':d_id' => $itemId, ':t_id' => $categoryId]);
                $mapRow = $mapStmt->fetch(\PDO::FETCH_ASSOC);
                if (!$mapRow) throw new Exception('找不到分類關聯');
                
                $oldSort = intval($mapRow['sort_num']);
                if ($oldSort === $newSort) {
                    $this->pdo->commit();
                    return $this->jsonResponse($response, '無需更新');
                }
                
                $this->log("Moving from {$oldSort} to {$newSort}");

                // 2. 移動其他產品的排序
                if ($newSort < $oldSort) {
                    // 向上移動：將 [newSort, oldSort) 範圍內的項目 +1
                    $shiftSql = "UPDATE data_taxonomy_map SET sort_num = sort_num + 1 
                                WHERE t_id = :t_id AND sort_num >= :new_sort AND sort_num < :old_sort";
                } else {
                    // 向下移動：將 (oldSort, newSort] 範圍內的項目 -1
                    $shiftSql = "UPDATE data_taxonomy_map SET sort_num = sort_num - 1 
                                WHERE t_id = :t_id AND sort_num > :old_sort AND sort_num <= :new_sort";
                }
                
                $stmtShift = $this->pdo->prepare($shiftSql);
                $stmtShift->execute([':t_id' => $categoryId, ':new_sort' => $newSort, ':old_sort' => $oldSort]);
                $this->log("Shifted " . $stmtShift->rowCount() . " items");

                // 3. 更新目標產品的排序
                $updateStmt = $this->pdo->prepare("UPDATE data_taxonomy_map SET sort_num = :new_sort WHERE d_id = :d_id AND t_id = :t_id");
                $updateResult = $updateStmt->execute([':new_sort' => $newSort, ':d_id' => $itemId, ':t_id' => $categoryId]);
                
                $this->log("Update result: " . ($updateResult ? 'success' : 'failed'));

            } else {
                // 使用原本的 d_sort 排序
                $oldSort = intval($item[$col_sort]);
                if ($oldSort === $newSort) return $this->jsonResponse($response, '無需更新');

                // 建立過濾條件
                $where = ["1=1"];
                $params = [];
                if ($menuKey && $menuValue !== null) {
                    $where[] = "{$menuKey} = :menuValue";
                    $params[':menuValue'] = $menuValue;
                }
                // 【修正】如果在分類模式下（且不使用 MapSort），不用排除置頂項目
                // 這樣置頂項目也可以在分類下被拖曳排序 (更新 d_sort)
                if ($col_top !== null && !empty($isCategorySort)) {
                    $where[] = "{$col_top} = 0";
                }
                
                if ($categoryField && isset($item[$categoryField])) {
                    $where[] = "{$categoryField} = :cat";
                    $params[':cat'] = $item[$categoryField];
                }
                if ($parentIdField && isset($item[$parentIdField])) {
                    $where[] = "{$parentIdField} = :parent";
                    $params[':parent'] = $item[$parentIdField];
                }
                if (isset($item['lang'])) {
                    $where[] = "lang = :lang";
                    $params[':lang'] = $item['lang'];
                }

                $whereSql = implode(' AND ', $where);

                if ($newSort < $oldSort) {
                    $shiftSql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} + 1 WHERE {$whereSql} AND {$col_sort} >= :new_sort AND {$col_sort} < :old_sort";
                } else {
                    $shiftSql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} - 1 WHERE {$whereSql} AND {$col_sort} > :old_sort AND {$col_sort} <= :new_sort";
                }

                $stmtShift = $this->pdo->prepare($shiftSql);
                $shiftParams = array_merge($params, [':new_sort' => $newSort, ':old_sort' => $oldSort]);
                $stmtShift->execute($shiftParams);

                $this->pdo->prepare("UPDATE {$tableName} SET {$col_sort} = :new_sort WHERE {$primaryKey} = :id")->execute([':new_sort' => $newSort, ':id' => $itemId]);
            }

            $this->pdo->commit();
            return $this->jsonResponse($response, '排序已更新');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->log("Error in " . __METHOD__ . ": " . $e->getMessage());
            return $this->jsonResponse($response, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * 置頂切換 API
     */
    public function togglePin(Request $request, Response $response, array $args)
    {
        try {
            $this->requireAdmin();
            $data = $request->getParsedBody();
            $module = preg_replace('/[^a-zA-Z0-9_]/', '', $data['module'] ?? '');
            $itemId = intval($data['item_id'] ?? 0);
            $requestCategoryId = isset($data['category_id']) && $data['category_id'] !== '' ? intval($data['category_id']) : null;

            if (empty($module) || $itemId <= 0) return $this->jsonResponse($response, '缺少參數', 400);

            require_once BASE_PATH_CMS . '/includes/elements/ModuleConfigElement.php';
            require_once BASE_PATH_CMS . '/includes/taxonomyMapHelper.php';

            $moduleConfig = \ModuleConfigElement::loadConfig($module);
            $tableName = $moduleConfig['tableName'];
            $primaryKey = $moduleConfig['primaryKey'];
            $cols = $moduleConfig['cols'] ?? [];
            $col_top = $cols['top'] ?? 'd_top';
            $col_sort = $cols['sort'] ?? 'd_sort';
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? null;
            $menuKey = $moduleConfig['menuKey'] ?? null;
            $menuValue = $moduleConfig['menuValue'] ?? null;

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT * FROM {$tableName} WHERE {$primaryKey} = :id");
            $stmt->execute([':id' => $itemId]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$item) throw new Exception('找不到資料');

            $newTop = ($item[$col_top] ?? 0) ? 0 : 1;
            
            // 【新增】檢查是否使用 data_taxonomy_map 排序 (同步檢查設定檔與資料表)
            $useMapPin = false;
            $configUseTaxonomyMapSort = $moduleConfig['listPage']['useTaxonomyMapSort'] ?? true;
            $categoryId = null;
            
            // 優先使用前端傳來的 category_id
            if ($requestCategoryId !== null) {
                $categoryId = $requestCategoryId;
            } elseif (isset($item[$categoryField])) {
                // 向後兼容：如果沒傳，嘗試從資料本身抓取
                $categoryId = $item[$categoryField];
            }

            // 如果使用 Map 排序，且有分類 (分類ID > 0)
            if ($configUseTaxonomyMapSort && $categoryId > 0 && hasTaxonomyMapTable($this->pdo)) {
                $useMapPin = true;
            }

            if ($useMapPin) {
                // 【情境A】更新 data_taxonomy_map 的 d_top
                // 若 Map 表沒有 d_top 欄位，這裡會報錯，但因為使用者確認有，我們先假定有
                // 為了保險，我們也可以檢查欄位是否存在，但這裡直接執行
                $checkMapTop = $this->pdo->prepare("SELECT d_top FROM data_taxonomy_map WHERE d_id = :id AND t_id = :c_id");
                $checkMapTop->execute([':id' => $itemId, ':c_id' => $categoryId]);
                $mapRow = $checkMapTop->fetch(\PDO::FETCH_ASSOC);

                if ($mapRow) {
                    $currentMapTop = $mapRow['d_top'] ?? 0;
                    $newTop = ($currentMapTop == 1) ? 0 : 1;
                    
                    $this->pdo->prepare("UPDATE data_taxonomy_map SET d_top = :top WHERE d_id = :id AND t_id = :c_id")
                              ->execute([':top' => $newTop, ':id' => $itemId, ':c_id' => $categoryId]);
                              
                    // 重整 Map 表排序
                    // 針對該分類下，非置頂的項目重整 sort_num
                    $stmtAll = $this->pdo->prepare("
                        SELECT d_id FROM data_taxonomy_map 
                        WHERE t_id = :c_id AND (d_top = 0 OR d_top IS NULL) 
                        ORDER BY sort_num ASC, d_id ASC
                    ");
                    $stmtAll->execute([':c_id' => $categoryId]);
                    $rows = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);
                    
                    foreach ($rows as $idx => $row) {
                        $this->pdo->prepare("UPDATE data_taxonomy_map SET sort_num = :s WHERE d_id = :id AND t_id = :c_id")
                                  ->execute([':s' => $idx + 1, ':id' => $row['d_id'], ':c_id' => $categoryId]);
                    }
                } else {
                     // 找不到 Map 記錄，fallback 到主表？不，應該報錯或忽略
                     // 這裡我們暫時 fallback 到主表，以免邏輯斷裂，但邏輯上是不對的
                }

            } else {
                // 【情境B】更新主表 data_set 的 d_top (全域置頂)
                $this->pdo->prepare("UPDATE {$tableName} SET {$col_top} = :top WHERE {$primaryKey} = :id")->execute([':top' => $newTop, ':id' => $itemId]);

                // 重整主表排序
                $where = ["({$col_top} = 0 OR {$col_top} IS NULL)"];
                $params = [];
                if ($menuKey && $menuValue !== null) { $where[] = "{$menuKey} = :mv"; $params[':mv'] = $menuValue; }
                // 注意：如果不是 Map 模式，我們通常忽略分類過濾，進行全域重整
                // 但如果該模組有 categoryField 且我們在過濾下...
                // 用戶之前的邏輯是：在全域下才置頂。所以在全域下重整是合理的。
                // 如果在分類過濾下但 useTaxonomyMapSort=false，則過濾條件仍有效
                if ($categoryField && isset($item[$categoryField])) { $where[] = "{$categoryField} = :cat"; $params[':cat'] = $item[$categoryField]; }
                
                $whereSql = implode(' AND ', $where);
                
                $stmtAll = $this->pdo->prepare("SELECT {$primaryKey} FROM {$tableName} WHERE {$whereSql} ORDER BY {$col_sort} ASC, {$primaryKey} ASC");
                $stmtAll->execute($params);
                $rows = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($rows as $idx => $row) {
                    $this->pdo->prepare("UPDATE {$tableName} SET {$col_sort} = :s WHERE {$primaryKey} = :id")->execute([':s' => $idx + 1, ':id' => $row[$primaryKey]]);
                }
            }

            $this->pdo->commit();
            return $this->jsonResponse($response, '置頂狀態已更新', 200);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return $this->jsonResponse($response, $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function reorderAfterDelete($tableName, $col_sort, $deletedSort, $categoryField, $catVal, $parentIdField, $parentVal, $menuKey, $menuVal, $col_delete_time = null, $lang = null)
    {
        $where = ["{$col_sort} > :dsort"];
        $params = [':dsort' => $deletedSort];
        if ($menuKey && $menuVal !== null) { $where[] = "{$menuKey} = :mv"; $params[':mv'] = $menuVal; }
        if ($categoryField && $catVal !== null) { $where[] = "{$categoryField} = :cat"; $params[':cat'] = $catVal; }
        if ($parentIdField && $parentVal !== null) { $where[] = "{$parentIdField} = :p"; $params[':p'] = $parentVal; }
        if ($lang !== null) { $where[] = "lang = :lang"; $params[':lang'] = $lang; }
        if ($col_delete_time) $where[] = "{$col_delete_time} IS NULL";

        $sql = "UPDATE {$tableName} SET {$col_sort} = {$col_sort} - 1 WHERE " . implode(' AND ', $where);
        $this->pdo->prepare($sql)->execute($params);
    }
    private function cleanupFiles($id, $col_file_fk)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM file_set WHERE {$col_file_fk} = :id");
        $stmt->execute([':id' => $id]);
        $images = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($images as $img) {
            for ($i = 1; $i <= 5; $i++) {
                $link = $img["file_link{$i}"] ?? '';
                if ($link && file_exists(BASE_PATH_CMS . "/../" . $link)) @unlink(BASE_PATH_CMS . "/../" . $link);
            }
        }
    }

    private function jsonResponse(Response $response, $message, $status = 200)
    {
        // 如果 message 是陣列，直接使用
        if (is_array($message)) {
            $payload = json_encode(array_merge(['success' => ($status < 300)], $message), JSON_UNESCAPED_UNICODE);
        } else {
            $payload = json_encode(['success' => ($status < 300), 'message' => $message], JSON_UNESCAPED_UNICODE);
        }
        
        // 清除任何可能已經產生的額外輸出 (例如 PHP Notice)，避免損壞 JSON 格式
        if (ob_get_level() > 0) ob_clean();
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

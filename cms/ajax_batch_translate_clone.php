<?php
/**
 * AJAX Handler: Batch Translate & Clone Records
 */

require_once('../Connections/connect2data.php');

header('Content-Type: application/json');

ob_start();

try {
    $conn->beginTransaction();
    $module = $_POST['module'] ?? '';
    $itemIds = $_POST['item_ids'] ?? []; // Expected as an array
    $targetLang = $_POST['target_lang'] ?? '';
    $overwrite = (int)($_POST['overwrite'] ?? 0); // 【新增】覆蓋參數
    
    if (empty($module) || empty($itemIds) || !is_array($itemIds) || empty($targetLang)) {
        throw new Exception('缺少必要參數');
    }
    
    // 1. 載入模組配置
    $configFile = __DIR__ . "/set/{$module}Set.php";
    if (!file_exists($configFile)) {
        throw new Exception("找不到模組配置檔案");
    }
    
    $moduleConfig = require $configFile;
    $tableName = $moduleConfig['tableName'];
    $primaryKey = $moduleConfig['primaryKey'];
    $langField = 'lang';
    $fileFk = $moduleConfig['cols']['file_fk'] ?? 'file_d_id';
    
    $successCount = 0;
    $errors = [];
    
    // 【新增】排序計數器，用於批次複製時保持順序
    $sortCounter = [];

    foreach ($itemIds as $itemId) {
        $itemId = (int)$itemId;
        if ($itemId <= 0) continue;

        try {
            // 2. 獲取原始資料
            $sqlSelect = "SELECT * FROM {$tableName} WHERE {$primaryKey} = :id";
            $stmtSelect = $conn->prepare($sqlSelect);
            $stmtSelect->execute([':id' => $itemId]);
            $rowData = $stmtSelect->fetch(PDO::FETCH_ASSOC);
            
            if (!$rowData) {
                throw new Exception("找不到原始資料 (ID: {$itemId})");
            }
            
            // 獲取原始資料的語系
            $sourceLang = $rowData[$langField] ?? '';
            
            // 【新增】判斷是否來自 info.php
            $isInfo = (int)($_POST['is_info'] ?? 0);
            
            if ($isInfo) {
                // info.php 的邏輯：只檢查目標語系是否已有該模組的資料
                $menuKey = $moduleConfig['menuKey'] ?? null;
                $menuValue = $moduleConfig['menuValue'] ?? null;
                
                $checkExistSql = "SELECT {$primaryKey} FROM {$tableName} WHERE {$langField} = :lang";
                $checkParams = [':lang' => $targetLang];
                
                // 如果有 menuKey，加入條件（例如：d_class1 = 'popInfo'）
                if ($menuKey && $menuValue !== null) {
                    $checkExistSql .= " AND {$menuKey} = :menuValue";
                    $checkParams[':menuValue'] = $menuValue;
                }
                
                $checkExistSql .= " LIMIT 1";
                
                $checkExistStmt = $conn->prepare($checkExistSql);
                $checkExistStmt->execute($checkParams);
                $existingId = $checkExistStmt->fetchColumn();
                
                // 如果目標語系已存在資料
                if ($existingId) {
                    if (!$overwrite) {
                        // 未勾選覆蓋，拋出錯誤
                        $moduleName = $moduleConfig['moduleName'] ?? $module;
                        throw new Exception("目標語系 ({$targetLang}) 已存在 {$moduleName} 的資料，請勾選「覆蓋已存在的資料」");
                    }
                    
                    // 勾選覆蓋，先刪除舊資料和關聯檔案
                    // 1. 刪除關聯的檔案記錄和實體檔案
                    $sqlOldFiles = "SELECT * FROM file_set WHERE {$fileFk} = :id";
                    $stmtOldFiles = $conn->prepare($sqlOldFiles);
                    $stmtOldFiles->execute([':id' => $existingId]);
                    $oldFiles = $stmtOldFiles->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($oldFiles as $oldFile) {
                        // 刪除實體檔案
                        foreach (['file_link1', 'file_link2', 'file_link3', 'file_link4', 'file_link5'] as $linkKey) {
                            if (!empty($oldFile[$linkKey])) {
                                $filePath = "../" . $oldFile[$linkKey];
                                if (file_exists($filePath)) {
                                    @unlink($filePath);
                                }
                            }
                        }
                    }
                    
                    // 2. 刪除檔案記錄
                    $sqlDeleteFiles = "DELETE FROM file_set WHERE {$fileFk} = :id";
                    $stmtDeleteFiles = $conn->prepare($sqlDeleteFiles);
                    $stmtDeleteFiles->execute([':id' => $existingId]);
                    
                    // 3. 刪除主資料
                    $sqlDelete = "DELETE FROM {$tableName} WHERE {$primaryKey} = :id";
                    $stmtDelete = $conn->prepare($sqlDelete);
                    $stmtDelete->execute([':id' => $existingId]);
                }
            }
            
            // 3. 準備插入資料
            unset($rowData[$primaryKey]); // 移除主鍵
            $rowData[$langField] = $targetLang; // 設定目標語系
            
            // 處理 slug
            if (isset($rowData['d_slug']) && !empty($rowData['d_slug'])) {
                $rowData['d_slug'] .= '-' . $targetLang;
            }
            if (isset($rowData['t_slug']) && !empty($rowData['t_slug'])) {
                $rowData['t_slug'] .= '-' . $targetLang;
            }

            // 【新增】智慧分類對應
            $hasCategory = $moduleConfig['listPage']['hasCategory'] ?? false;
            $categoryField = $moduleConfig['listPage']['categoryField'] ?? '';
            $categoryName = $moduleConfig['listPage']['categoryName'] ?? '';
            $oldCatTitle = ''; // 【修正】初始化變數

            if ($hasCategory && $categoryField && !empty($rowData[$categoryField]) && $categoryName) {
                $oldCatId = $rowData[$categoryField];
                
                // 1. 改為直接讀取分類模組的設定檔 (解決 cms_menus 資料不全的問題)
                $catConfigFile = __DIR__ . "/set/{$categoryName}Set.php";
                
                if (file_exists($catConfigFile)) {
                    $catConfig = require $catConfigFile;
                    $cTable = $catConfig['tableName'] ?? '';
                    $cPK = $catConfig['primaryKey'] ?? '';
                    // 嘗試從 cols 中找 title 欄位，預設為 d_title 或 t_name
                    $cTitleCol = $catConfig['cols']['title'] ?? ($cTable == 'taxonomies' ? 't_name' : 'd_title');

                    // 確保必要資訊存在才能進行對應
                    if ($cTable && $cPK && $cTitleCol) {
                        try {
                            // 2. 獲取原始分類的名稱
                            $oldCatSql = "SELECT {$cTitleCol} FROM {$cTable} WHERE {$cPK} = :id";
                            $oldCatStmt = $conn->prepare($oldCatSql);
                            $oldCatStmt->execute([':id' => $oldCatId]);
                            $oldCatTitle = $oldCatStmt->fetchColumn();

                            if ($oldCatTitle) {
                                // 3. 在目標語系中找同名的分類
                                $newCatSql = "SELECT {$cPK} FROM {$cTable} WHERE {$cTitleCol} = :title AND lang = :lang LIMIT 1";
                                $newCatStmt = $conn->prepare($newCatSql);
                                $newCatStmt->execute([':title' => $oldCatTitle, ':lang' => $targetLang]);
                                $newCatId = $newCatStmt->fetchColumn();

                                if ($newCatId) {
                                    $rowData[$categoryField] = $newCatId; // 成功對應到目標語系的分類
                                } else {
                                    // 找不到對應分類，清空為 0
                                    $rowData[$categoryField] = 0; 
                                }
                            } else {
                                // 原始分類名稱不存在，清空為 0
                                $rowData[$categoryField] = 0;
                            }
                        } catch (Exception $e) {
                            $rowData[$categoryField] = 0;
                        }
                    } else {
                         $rowData[$categoryField] = 0;
                    }
                } else {
                     $rowData[$categoryField] = 0;
                }
            }

            // 【修正】重新計算目標語系的排序號碼
            $sortField = $moduleConfig['cols']['sort'] ?? 'd_sort';
            if (isset($rowData[$sortField])) {
                // 建立排序計數器的 key
                $sortKey = $targetLang;
                if ($hasCategory && $categoryField && isset($rowData[$categoryField]) && $rowData[$categoryField] > 0) {
                    $sortKey .= '_cat_' . $rowData[$categoryField];
                }
                
                $menuKey = $moduleConfig['menuKey'] ?? null;
                $menuValue = $moduleConfig['menuValue'] ?? null;
                if ($menuKey && $menuValue !== null) {
                    $sortKey .= '_menu_' . $menuValue;
                }
                
                // 【修正】如果這是第一筆，初始化計數器為 0
                if (!isset($sortCounter[$sortKey])) {
                    $sortCounter[$sortKey] = 0;
                }
                
                // 遞增並設定排序號碼（從 1 開始）
                $sortCounter[$sortKey]++;
                $rowData[$sortField] = $sortCounter[$sortKey];
            }

            $columns = array_keys($rowData);
            $values = array_values($rowData);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $colList = implode(',', $columns);
            
            $sqlInsert = "INSERT INTO {$tableName} ($colList) VALUES ($placeholders)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->execute($values);
            
            $newId = $conn->lastInsertId();
            
            // 【修正】主資料插入成功後立即計數
            $successCount++;
            
            // 4. 複製關連檔案 (file_set)
            try {
                $sqlFiles = "SELECT * FROM file_set WHERE {$fileFk} = :oldId";
                $stmtFiles = $conn->prepare($sqlFiles);
                $stmtFiles->execute([':oldId' => $itemId]);
                $files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($files as $file) {
                    unset($file['file_id']); // 移除檔案主鍵
                    $file[$fileFk] = $newId; // 關連到新紀錄
                    
                    // 【修正】依據 photo_process.php 規範與使用者需求
                    $destBaseName = (!empty($oldCatTitle)) ? $oldCatTitle : $module;
                    // 淨化名稱 (移除不合法字元) - 修正 regex 語法
                    $destBaseName = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $destBaseName ?? '');
                    $destBaseName = str_replace(' ', '_', $destBaseName ?? '');
                    // 如果清理後變成空字串，使用模組名稱作為備用
                    if (empty($destBaseName)) {
                        $destBaseName = $module;
                    }

                    foreach (['file_link1', 'file_link2', 'file_link3', 'file_link4', 'file_link5'] as $linkKey) {
                        if (!empty($file[$linkKey])) {
                            $srcPath = "../" . $file[$linkKey];
                            
                            if (file_exists($srcPath)) {
                                // 【修正】保持原始目錄結構，只複製檔案
                                $pathInfo = pathinfo($file[$linkKey]);
                                $originalDir = $pathInfo['dirname'];  // 例如: upload_image/history
                                $ext = $pathInfo['extension'];
                                
                                // 使用相同的目錄，只是檔名不同
                                $destRelDir = $originalDir . "/";
                                $destAbsDir = "../" . $destRelDir;
                                
                                if (!is_dir($destAbsDir)) {
                                    @mkdir($destAbsDir, 0777, true);
                                }

                                if (is_dir($destAbsDir)) {
                                    // 產生新檔名：模組名_時間戳_隨機數.ext
                                    $newFileName = $module . "_" . date('YmdHis') . "_" . rand(100, 999) . "." . $ext;
                                    $destPath = $destAbsDir . $newFileName;
                                    
                                    if (@copy($srcPath, $destPath)) {
                                        $file[$linkKey] = $destRelDir . $newFileName;
                                    }
                                }
                            }
                        }
                    }

                    $fCols = array_keys($file);
                    $fValues = array_values($file);
                    $fPlaceholders = implode(',', array_fill(0, count($fCols), '?'));
                    $fColList = implode(',', $fCols);
                    
                    $sqlInsertFile = "INSERT INTO file_set ($fColList) VALUES ($fPlaceholders)";
                    $stmtInsertFile = $conn->prepare($sqlInsertFile);
                    $stmtInsertFile->execute($fValues);
                }
            } catch (Exception $fileEx) {
                // 檔案複製失敗不影響主資料，只記錄警告
                $errors[] = "ID {$itemId}: 主資料複製成功，但檔案複製時發生錯誤 - " . $fileEx->getMessage();
            }

        } catch (Exception $innerEx) {
            // 主資料插入失敗
            $errorMsg = "ID {$itemId}: " . $innerEx->getMessage() . " (檔案: " . $innerEx->getFile() . ", 行: " . $innerEx->getLine() . ")";
            $errors[] = $errorMsg;
            file_put_contents('debug_clone_error.log', date('Y-m-d H:i:s') . " - " . $errorMsg . "\n", FILE_APPEND);
        }
    }
    
    // 【新增】重新整理排序邏輯 (Re-sort)
    // 針對此次操作涉及的 unique scope 進行重排
    
    // 1. 整理涉及的 Scope
    // key: {targetLang}_{menuValue}_{catId}_{parentId}
    // scopeData: [whereSql, params]
    $scopesToResort = [];
    $menuKey = $moduleConfig['menuKey'] ?? null;
    $menuValue = $moduleConfig['menuValue'] ?? null;
    $sortField = $moduleConfig['cols']['sort'] ?? 'd_sort';
    $hasCategory = $moduleConfig['listPage']['hasCategory'] ?? false;
    $categoryField = $moduleConfig['listPage']['categoryField'] ?? '';
    $hasHierarchy = $moduleConfig['listPage']['hasHierarchy'] ?? false;
    $parentIdField = $moduleConfig['cols']['parent_id'] ?? null;
    $col_delete_time = $moduleConfig['cols']['delete_time'] ?? 'd_delete_time';

    // 由於我們無法精確知道每次 clone 後的分類 ID (智慧對應)，
    // 我們採取較為寬鬆的策略：重新整理目標語系下，該模組的所有資料排序
    // 或者，比較精確的做法是：重新整理 targetLang 下的整個模組排序 (通常模組資料量不大，這樣最保險且簡單)
    
    // 構建重排查詢條件
    $resortWhere = ["1=1"];
    $resortParams = [];
    
    // 條件 1: 語系
    if ($targetLang) {
        $resortWhere[] = "{$langField} = :lang";
        $resortParams[':lang'] = $targetLang;
    }
    
    // 條件 2: Menu (模組過濾)
    if ($menuKey && $menuValue !== null) {
        $resortWhere[] = "{$menuKey} = :menuValue";
        $resortParams[':menuValue'] = $menuValue;
    }
    
    // 條件 3: 排除軟刪除
    $checkDelCol = $conn->query("SHOW COLUMNS FROM {$tableName} LIKE '{$col_delete_time}'");
    if ($checkDelCol->rowCount() > 0) {
        $resortWhere[] = "{$col_delete_time} IS NULL";
    }

    $whereSql = implode(" AND ", $resortWhere);
    
    // 2. 為了支援分類排序與置頂，我們需要更複雜的排序邏輯
    // 但通常批次重排是為了消除間隙。
    // 我們依照目前的排序欄位 ASC 排列，然後依序更新為 1, 2, 3...
    
    // 查詢所有 ID
    // 注意：如果有分類，通常希望分類內排序。但如果資料表是共用的 (如 data_set)，
    // 跨分類的 d_sort 應該是連續的嗎？通常 CMS 是 "Global Sort" 或是 "Category Scoped Sort"
    // 從 list.php 判斷，若有分類，通常會有 FIND_IN_SET 或是 Map Table。
    // 為了安全起見，若是簡單模組，我們直接全域重排。
    // 若是分類模組，可能需要針對每個分類重排？
    // 鑑於 user 只說 "重新幫我整理排序"，我們採取「依照現有 d_sort 順序，重新編號為連續整數」的策略
    
    // 查詢該語系下的所有資料，按 d_sort ASC, d_id ASC 排序
    $sqlGetAll = "SELECT {$primaryKey} FROM {$tableName} WHERE {$whereSql} ORDER BY {$sortField} ASC, {$primaryKey} ASC";
    $stmtGetAll = $conn->prepare($sqlGetAll);
    $stmtGetAll->execute($resortParams);
    $allIds = $stmtGetAll->fetchAll(PDO::FETCH_COLUMN);
    
    // 開始更新
    $sqlUpdateSort = "UPDATE {$tableName} SET {$sortField} = :newSort WHERE {$primaryKey} = :id";
    $stmtUpdateSort = $conn->prepare($sqlUpdateSort);
    
    $newSortNum = 1;
    foreach ($allIds as $rId) {
        $stmtUpdateSort->execute([
            ':newSort' => $newSortNum,
            ':id' => $rId
        ]);
        $newSortNum++;
    }
    
    
    $conn->commit(); // Commit transaction if all items processed without critical errors

    echo json_encode([
        'success' => true,
        'count' => $successCount,
        'errors' => $errors,
        'message' => "成功複製 {$successCount} 筆資料" . (!empty($errors) ? "，但有 " . count($errors) . " 筆失敗" : "")
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack(); // Rollback on any critical error
    }
    if (ob_get_length()) ob_clean();
    
    // 【新增】更詳細的錯誤訊息
    $errorDetails = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    echo json_encode($errorDetails);
}

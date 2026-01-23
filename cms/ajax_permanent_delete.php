<?php
session_start();
require_once '../Connections/connect2data.php';
header('Content-Type: application/json');

$module = $_POST['module'] ?? '';
$itemId = intval($_POST['item_id'] ?? 0);
$force  = intval($_POST['force'] ?? 0); // 是否執意刪除

if (!$module || !$itemId) {
    echo json_encode(['success' => false, 'message' => '缺少參數']);
    exit;
}

$configFile = __DIR__ . "/set/{$module}Set.php";
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => '找不到模組配置']);
    exit;
}

$moduleConfig = require $configFile;
if (!is_array($moduleConfig) && isset($settingPage)) $moduleConfig = $settingPage;

$tableName   = $moduleConfig['tableName'];
$col_id      = $moduleConfig['primaryKey'];
$col_file_fk = $moduleConfig['cols']['file_fk'] ?? 'file_d_id';

try {
    $conn->beginTransaction();

    // --- 防呆檢查：如果是分類模組，檢查是否有關聯文章 ---
    if (strpos($module, 'Cate') !== false) {
        $mainModule = str_replace('Cate', '', $module);
        $mainConfigFile = __DIR__ . "/set/{$mainModule}Set.php";
        
        if (file_exists($mainConfigFile)) {
            unset($settingPage);
            $mainConfig = require $mainConfigFile;
            if (!is_array($mainConfig) && isset($settingPage)) $mainConfig = $settingPage;
            
            $articleTable = $mainConfig['tableName'];
            $articleCategoryField = $mainConfig['listPage']['categoryField'] ?? null;
            $articleFileFk = $mainConfig['cols']['file_fk'] ?? 'file_d_id';

            if ($articleTable && $articleCategoryField) {
                // 找出該分類下所有的文章 ID
                $stmt = $conn->prepare("SELECT {$mainConfig['primaryKey']} as id FROM {$articleTable} WHERE {$articleCategoryField} = :cat_id");
                $stmt->execute([':cat_id' => $itemId]);
                $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $articleCount = count($articles);

                if ($articleCount > 0 && $force == 0) {
                    // 有資料但未執意刪除：攔截並回傳 has_data
                    echo json_encode([
                        'success' => false, 
                        'has_data' => true, 
                        'message' => "此分類下尚有 {$articleCount} 筆文章。"
                    ]);
                    exit;
                }

                if ($articleCount > 0 && $force == 1) {
                    // 執意刪除：開始清理子文章所有檔案
                    foreach ($articles as $art) {
                        deleteRelatedFiles($conn, $articleFileFk, $art['id']);
                        // 刪除文章檔案紀錄
                        $conn->prepare("DELETE FROM file_set WHERE {$articleFileFk} = :id")->execute([':id' => $art['id']]);
                    }
                    // 刪除文章主資料
                    $conn->prepare("DELETE FROM {$articleTable} WHERE {$articleCategoryField} = :cat_id")->execute([':cat_id' => $itemId]);
                }
            }
        }
    }

    // --- 刪除分類本身的檔案與資料 ---
    deleteRelatedFiles($conn, $col_file_fk, $itemId);
    $conn->prepare("DELETE FROM file_set WHERE {$col_file_fk} = :id")->execute([':id' => $itemId]);
    
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE {$col_id} = :item_id");
    $stmt->execute([':item_id' => $itemId]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 輔助函式：刪除實體檔案
 */
function deleteRelatedFiles($conn, $fkColumn, $id) {
    $stmt = $conn->prepare("SELECT * FROM file_set WHERE {$fkColumn} = :id");
    $stmt->execute([':id' => $id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($files as $f) {
        for ($i=1; $i<=5; $i++) {
            $link = "file_link{$i}";
            if (!empty($f[$link]) && file_exists("../" . $f[$link])) {
                @unlink("../" . $f[$link]);
            }
        }
    }
}
<?php
/**
 * 複製資料到語系 API
 * 用於 info.php 的批次操作功能
 */

header('Content-Type: application/json');
session_start();

require_once('../Connections/connect2data.php');

// 檢查登入狀態
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 獲取參數
$module = $_POST['module'] ?? '';
$sourceId = (int)($_POST['source_id'] ?? 0);
$sourceLang = $_POST['source_lang'] ?? '';
$targetLang = $_POST['target_lang'] ?? '';
$overwrite = (int)($_POST['overwrite'] ?? 0);

// 驗證參數
if (empty($module) || $sourceId <= 0 || empty($sourceLang) || empty($targetLang)) {
    echo json_encode(['success' => false, 'message' => '參數不完整']);
    exit;
}

if ($sourceLang === $targetLang) {
    echo json_encode(['success' => false, 'message' => '來源語系和目標語系不能相同']);
    exit;
}

try {
    // 載入模組配置
    $configFile = "../set/{$module}Set.php";
    if (!file_exists($configFile)) {
        throw new Exception("找不到模組配置文件：{$module}");
    }
    
    $moduleConfig = include $configFile;
    $tableName = $moduleConfig['tableName'] ?? 'data_set';
    $primaryKey = $moduleConfig['primaryKey'] ?? 'd_id';
    
    // 檢查來源資料是否存在
    $checkQuery = "SELECT * FROM {$tableName} WHERE {$primaryKey} = :source_id AND lang = :source_lang LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([
        ':source_id' => $sourceId,
        ':source_lang' => $sourceLang
    ]);
    $sourceData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceData) {
        throw new Exception('找不到來源資料');
    }
    
    // 檢查目標語系是否已存在資料
    $targetCheckQuery = "SELECT {$primaryKey} FROM {$tableName} WHERE {$primaryKey} = :source_id AND lang = :target_lang LIMIT 1";
    $targetCheckStmt = $conn->prepare($targetCheckQuery);
    $targetCheckStmt->execute([
        ':source_id' => $sourceId,
        ':target_lang' => $targetLang
    ]);
    $targetExists = $targetCheckStmt->fetchColumn();
    
    if ($targetExists && !$overwrite) {
        throw new Exception('目標語系已存在資料，請勾選「覆蓋已存在的資料」');
    }
    
    // 準備複製的資料（更新 lang 欄位）
    $sourceData['lang'] = $targetLang;
    
    // 更新或插入資料
    if ($targetExists) {
        // 更新現有資料
        $updateFields = [];
        $updateParams = [];
        
        foreach ($sourceData as $key => $value) {
            if ($key !== $primaryKey && $key !== 'lang') {
                $updateFields[] = "`{$key}` = :{$key}";
                $updateParams[":{$key}"] = $value;
            }
        }
        
        $updateParams[':pk'] = $sourceId;
        $updateParams[':lang'] = $targetLang;
        
        $updateQuery = "UPDATE `{$tableName}` SET " . implode(', ', $updateFields) . 
                      " WHERE `{$primaryKey}` = :pk AND `lang` = :lang";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute($updateParams);
        
        echo json_encode(['success' => true, 'message' => '資料已更新到目標語系']);
        
    } else {
        // 插入新資料
        $insertFields = array_keys($sourceData);
        $insertPlaceholders = array_map(function($field) { return ":{$field}"; }, $insertFields);
        
        $insertQuery = "INSERT INTO `{$tableName}` (`" . implode('`, `', $insertFields) . "`) " .
                      "VALUES (" . implode(', ', $insertPlaceholders) . ")";
        $insertStmt = $conn->prepare($insertQuery);
        
        $insertParams = [];
        foreach ($sourceData as $key => $value) {
            $insertParams[":{$key}"] = $value;
        }
        
        $insertStmt->execute($insertParams);
        
        echo json_encode(['success' => true, 'message' => '資料已複製到目標語系']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

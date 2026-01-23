<?php
/**
 * 本機測試用的 API 驗證端點
 * 模擬外部 API 驗證 IP
 */

header('Content-Type: application/json');

// 讀取 POST 資料
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 記錄請求
error_log("API 驗證請求：" . print_r($data, true));

// 驗證參數
if (!isset($data['ip']) || !isset($data['secret'])) {
    http_response_code(400);
    echo json_encode([
        'verified' => false,
        'error' => '缺少必要參數'
    ]);
    exit;
}

// 驗證 secret key
$expectedSecret = 'test-secret-key-12345';  // 測試用的 secret key
if ($data['secret'] !== $expectedSecret) {
    http_response_code(401);
    echo json_encode([
        'verified' => false,
        'error' => 'Secret key 錯誤'
    ]);
    exit;
}

// 驗證 IP（測試環境允許所有 localhost IP）
$allowedIPs = [
    '127.0.0.1',
    '::1',
    'localhost',
    '59.126.31.214'
];

$clientIP = $data['ip'];
$isAllowed = in_array($clientIP, $allowedIPs);

// 返回驗證結果
http_response_code(200);
echo json_encode([
    'verified' => $isAllowed,
    'ip' => $clientIP,
    'message' => $isAllowed ? 'IP 驗證成功' : 'IP 未授權',
    'timestamp' => date('Y-m-d H:i:s')
]);

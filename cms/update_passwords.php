<?php
/**
 * 密碼更新工具
 * 用於將現有管理員的明文密碼轉換為加鹽哈希密碼
 * 
 * 使用方式：
 * 1. 訪問此頁面：update_passwords.php
 * 2. 系統會自動更新所有管理員的密碼
 * 3. 更新完成後請刪除此檔案以確保安全
 */

require_once('../Connections/connect2data.php');

// 防止重複執行（檢查是否已經有 salt）
$checkQuery = "SELECT user_id, user_name, user_password, user_salt FROM admin LIMIT 1";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->execute();
$sampleUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($sampleUser && !empty($sampleUser['user_salt'])) {
    echo "<h2>⚠️ 警告</h2>";
    echo "<p>看起來密碼已經被更新過了（已有 salt）。</p>";
    echo "<p>如果您確定要重新更新所有密碼，請先清空所有 user_salt 欄位。</p>";
    exit;
}

echo "<h1>密碼更新工具</h1>";
echo "<p>正在更新管理員密碼...</p>";

try {
    // 查詢所有管理員
    $query = "SELECT user_id, user_name, user_password FROM admin";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $conn->beginTransaction();
    
    $updateStmt = $conn->prepare("UPDATE admin SET user_password = :hashed_password, user_salt = :salt WHERE user_id = :user_id");
    
    $updatedCount = 0;
    
    foreach ($users as $user) {
        // 假設目前的 user_password 是明文密碼
        $plainPassword = $user['user_password'];
        
        // 生成隨機 salt（32 字元）
        $salt = bin2hex(random_bytes(16));
        
        // 使用 SHA256 + salt 加密密碼
        $hashedPassword = hash('sha256', $plainPassword . $salt);
        
        // 更新資料庫
        $updateStmt->execute([
            ':hashed_password' => $hashedPassword,
            ':salt' => $salt,
            ':user_id' => $user['user_id']
        ]);
        
        $updatedCount++;
        
        echo "<p>✓ 已更新：{$user['user_name']} (ID: {$user['user_id']})</p>";
    }
    
    $conn->commit();
    
    echo "<hr>";
    echo "<h2>✅ 完成！</h2>";
    echo "<p>成功更新 {$updatedCount} 個管理員帳號的密碼。</p>";
    echo "<p><strong>重要：</strong></p>";
    echo "<ul>";
    echo "<li>所有管理員的密碼已經加密，原始密碼保持不變</li>";
    echo "<li>請使用原本的密碼登入測試</li>";
    echo "<li>測試成功後，<strong style='color: red;'>請立即刪除此檔案（update_passwords.php）</strong></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "<h2>❌ 錯誤</h2>";
    echo "<p>更新失敗：" . $e->getMessage() . "</p>";
}
?>

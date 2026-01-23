<?php
/**
 * 管理員密碼重設工具
 * 使用後請刪除此檔案！
 */

require_once('../Connections/connect2data.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $newPassword = $_POST['password'];
    
    // 生成新的 salt
    $salt = bin2hex(random_bytes(16));
    
    // 加密密碼
    $hashedPassword = hash('sha256', $newPassword . $salt);
    
    // 更新資料庫
    $stmt = $conn->prepare("UPDATE admin SET user_password = :password, user_salt = :salt WHERE user_name = :username");
    $stmt->execute([
        ':password' => $hashedPassword,
        ':salt' => $salt,
        ':username' => $username
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h1 style='color: green;'>✅ 密碼重設成功！</h1>";
        echo "<p>帳號：{$username}</p>";
        echo "<p>新密碼：{$newPassword}</p>";
        echo "<p><strong>請立即刪除此檔案（reset_admin_password.php）！</strong></p>";
        echo "<p><a href='login.php'>前往登入</a></p>";
    } else {
        echo "<h1 style='color: red;'>❌ 找不到該帳號</h1>";
    }
    exit;
}

// 列出所有管理員
$stmt = $conn->query("SELECT user_id, user_name FROM admin");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理員密碼重設</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        form { background: #f5f5f5; padding: 20px; border-radius: 5px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin-top: 15px; }
        button:hover { background: #0056b3; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 3px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🔐 管理員密碼重設工具</h1>
    
    <div class="warning">
        <strong>⚠️ 警告：</strong>使用完畢後請立即刪除此檔案！
    </div>
    
    <form method="POST">
        <label>選擇管理員：</label>
        <select name="username" required>
            <option value="">請選擇...</option>
            <?php foreach ($admins as $admin): ?>
                <option value="<?= htmlspecialchars($admin['user_name']) ?>">
                    <?= htmlspecialchars($admin['user_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label>新密碼：</label>
        <input type="text" name="password" required placeholder="輸入新密碼">
        
        <button type="submit">重設密碼</button>
    </form>
    
    <h3>現有管理員列表：</h3>
    <ul>
        <?php foreach ($admins as $admin): ?>
            <li><?= htmlspecialchars($admin['user_name']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>

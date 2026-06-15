<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

// Verify token
$database = Database::getInstance();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT id, full_name FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Link reset tidak valid atau sudah kadaluarsa.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $password_confirm) {
        $error = 'Password tidak cocok';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        
        $success = 'Password berhasil direset! Silakan <a href="login.php">login</a>.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kayumanies</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #FFF8F0, #FFF0E0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(139,69,19,0.15);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        h2 { text-align: center; color: #8B4513; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #8B4513;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background: #FFF0F0; color: #D32F2F; border-left: 4px solid #D32F2F; }
        .alert-success { background: #F0FFF0; color: #2E7D32; border-left: 4px solid #2E7D32; }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>🔒 Reset Password</h2>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif ($user): ?>
        <form method="POST">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirm" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn-submit">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
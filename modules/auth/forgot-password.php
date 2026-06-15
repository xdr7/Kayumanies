<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Masukkan email Anda';
    } else {
        try {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);
                
                // In production, send email here
                // For development, show the link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/kayumanies/modules/auth/reset-password.php?token=" . $token;
                
                $success = "Link reset password telah dikirim ke <strong>{$email}</strong>.<br>
                           <small style='color:#666;'>Development: <a href='{$reset_link}'>{$reset_link}</a></small>";
            } else {
                $success = "Jika email terdaftar, link reset akan dikirimkan.";
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(139,69,19,0.15);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        .forgot-container h2 {
            text-align: center;
            color: #8B4513;
            margin-bottom: 15px;
        }
        .forgot-container p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input {
            width: 100%;
            padding: 14px 20px;
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
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger { background: #FFF0F0; color: #D32F2F; border-left: 4px solid #D32F2F; }
        .alert-success { background: #F0FFF0; color: #2E7D32; border-left: 4px solid #2E7D32; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #8B4513; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>🔑 Lupa Password</h2>
        <p>Masukkan email Anda untuk menerima link reset password.</p>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Masukkan email Anda" required>
            </div>
            <button type="submit" class="btn-submit">Kirim Link Reset</button>
        </form>
        
        <div class="back-link">
            <a href="login.php">← Kembali ke Login</a>
        </div>
    </div>
</body>
</html>
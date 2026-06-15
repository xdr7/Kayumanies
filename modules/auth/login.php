<?php
/**
 * Kayumanies - Login Page
 */
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($_SESSION['role'] == 'kasir') {
        header('Location: ../kasir/pos.php');
    } else {
        header('Location: ../../index.php');
    }
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        try {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            // Check user by email OR username
            $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['address'] = $user['address'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Add notification
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Login Berhasil', ?, 'system')");
                $stmt->execute([$user['id'], "Selamat datang kembali, {$user['full_name']}!"]);
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header('Location: ../admin/dashboard.php');
                } elseif ($user['role'] == 'kasir') {
                    header('Location: ../kasir/dashboard.php');
                } else {
                    // Redirect to previous page or home
                    $redirect = $_SESSION['redirect_after_login'] ?? '../../index.php';
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirect);
                }
                exit;
            } else {
                $error = 'Email/Username atau password salah!';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Save redirect URL if coming from another page
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login.php') === false) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kayumanies Cake Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<!-- PWA -->
			<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #FFF8F0 0%, #FFF0E0 50%, #FFE4C4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(139, 69, 19, 0.15);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header .logo {
            font-size: 50px;
            margin-bottom: 10px;
        }
        
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px 35px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #FFF0F0;
            color: #D32F2F;
            border-left: 4px solid #D32F2F;
        }
        
        .alert-success {
            background: #F0FFF0;
            color: #2E7D32;
            border-left: 4px solid #2E7D32;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: #FAFAFA;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #8B4513;
            box-shadow: 0 0 0 4px rgba(139, 69, 19, 0.1);
            background: white;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
            font-size: 16px;
        }
        
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-row a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 600;
        }
        
        .remember-row a:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(139, 69, 19, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 700;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
            font-size: 13px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E0E0E0;
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .demo-accounts {
            background: #F5F5F5;
            padding: 15px;
            border-radius: 10px;
            font-size: 12px;
            color: #666;
            margin-top: 15px;
        }
        
        .demo-accounts strong {
            color: #333;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 15px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .btn-back:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <a href="../../index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
            <div class="logo">🎂</div>
            <h1>Selamat Datang</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email atau Username</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="text" name="email" class="form-control" 
                               placeholder="Masukkan email atau username"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="remember-row">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                        <input type="checkbox" name="remember" style="width: 16px; height: 16px;">
                        Ingat saya
                    </label>
                    <a href="forgot-password.php">Lupa Password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>
            
            <div class="divider">
                <span>atau</span>
            </div>
            
            <p class="register-link">
                Belum punya akun? <a href="register.php">Daftar Sekarang</a>
            </p>
            
            <div class="demo-accounts">
                <strong>🔑 Akun Demo:</strong><br>
                Admin: <strong>admin@kayumanies.com</strong> / <strong>admin123</strong><br>
                Kasir: <strong>kasir1@kayumanies.com</strong> / <strong>admin123</strong><br>
                Pembeli: <strong>pembeli1@gmail.com</strong> / <strong>admin123</strong>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var icon = document.getElementById('toggleIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
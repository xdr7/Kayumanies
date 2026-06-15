<?php
/**
 * Kayumanies - Register Page
 */
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Nama lengkap harus diisi';
    }
    
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($phone) || strlen($phone) < 10) {
        $errors[] = 'Nomor telepon tidak valid';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (empty($errors)) {
        try {
            $database = Database::getInstance();
            $db = $database->getConnection();
            
            // Check if username exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username sudah digunakan';
            }
            
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah terdaftar';
            }
            
            if (empty($errors)) {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password, full_name, phone, address, role, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pembeli', 1)
                ");
                $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address]);
                
                $user_id = $db->lastInsertId();
                
                // Add notification
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Pendaftaran Berhasil', ?, 'system')");
                $stmt->execute([$user_id, "Selamat datang di Kayumanies, {$full_name}! Silakan mulai berbelanja."]);
                
                $success = 'Pendaftaran berhasil! Silakan <a href="login.php" style="color: #2E7D32; font-weight: 700;">login di sini</a>';
                
                // Clear form
                $_POST = [];
            }
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Kayumanies Cake Shop</title>
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
        
        .register-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(139, 69, 19, 0.15);
            width: 100%;
            max-width: 520px;
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            padding: 35px 30px;
            text-align: center;
            color: white;
        }
        
        .register-header .logo {
            font-size: 45px;
            margin-bottom: 8px;
        }
        
        .register-header h1 {
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .register-header p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .register-body {
            padding: 35px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.6;
        }
        
        .alert i {
            margin-top: 2px;
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
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 600;
            font-size: 13px;
        }
        
        .form-group label .required {
            color: #f44336;
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
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 2px solid #E0E0E0;
            border-radius: 10px;
            font-size: 14px;
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
        
        textarea.form-control {
            padding-left: 45px;
            resize: vertical;
            min-height: 80px;
        }
        
        textarea.form-control + i {
            top: 20px;
            transform: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-register {
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
            margin-top: 10px;
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.3);
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(139, 69, 19, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .login-link a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 700;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 12px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .btn-back:hover {
            opacity: 1;
        }
        
        .terms {
            font-size: 12px;
            color: #666;
            margin-top: 15px;
            text-align: center;
        }
        
        .terms a {
            color: #8B4513;
            text-decoration: none;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .register-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <a href="../../index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
            <div class="logo">🎂</div>
            <h1>Buat Akun Baru</h1>
            <p>Daftar dan mulai berbelanja kue favoritmu</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php else: ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" class="form-control" 
                               placeholder="Masukkan nama lengkap"
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-user-circle"></i>
                            <input type="text" name="username" class="form-control" 
                                   placeholder="Username unik"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required minlength="3">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="email@example.com"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>No. Telepon <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" class="form-control" 
                               placeholder="0812-3456-7890"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <div class="input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea name="address" class="form-control" 
                                  placeholder="Alamat lengkap (opsional)"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Minimal 6 karakter" required minlength="6">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Password <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password_confirm" class="form-control" 
                                   placeholder="Ulangi password" required minlength="6">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>
                
                <div class="terms">
                    Dengan mendaftar, Anda menyetujui <a href="#">Syarat & Ketentuan</a> dan <a href="#">Kebijakan Privasi</a> kami.
                </div>
            </form>
            
            <?php endif; ?>
            
            <p class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
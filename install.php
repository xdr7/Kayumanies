<?php
/**
 * Kayumanies Cake Shop - Installation Wizard
 * Auto-create folders, database, and config
 * PHP 7.3+ Compatible
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Cek jika sudah terinstall
if (file_exists(__DIR__ . '/config/installed.lock') && $step != 4) {
    header('Location: index.php');
    exit;
}

// ==========================================
// AUTO-CREATE REQUIRED DIRECTORIES
// ==========================================
// ========== CREATE DIRECTORIES ==========
$all_dirs = array(
    'assets',
    'assets/css',
    'assets/js',
    'assets/images',
    'assets/uploads',
    'assets/uploads/products',
    'assets/uploads/payments',
    'config',
    'database',
    'includes',
    'modules',
    'modules/admin',
    'modules/auth',
    'modules/kasir',
    'modules/pembeli',
    'api'
);

foreach ($all_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            outputLog("[ERROR] Gagal membuat folder: {$dir}", 'error');
        }
        @chmod($path, 0755);
    }
}
outputLog('[OK] ' . count($all_dirs) . ' folder berhasil dibuat/diperiksa', 'success');

// ==========================================
// FUNCTIONS
// ==========================================
function getBaseUrl() {
    $protocol = 'http://';
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) {
        $protocol = 'https://';
    }
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . $host . $script, '/');
}

function testDBConnection($host, $user, $pass) {
    try {
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        return array('success' => true, 'pdo' => $pdo);
    } catch (PDOException $e) {
        return array('success' => false, 'message' => $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Kayumanies Cake Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #8B4513;
            --primary-dark: #6B3410;
            --success: #4CAF50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196F3;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            max-width: 680px;
            width: 100%;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            padding: 35px 30px;
            text-align: center;
            color: white;
        }
        
        .install-header .icon {
            font-size: 50px;
            margin-bottom: 12px;
            display: block;
        }
        
        .install-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .install-header p {
            font-size: 13px;
            opacity: 0.85;
        }
        
        .install-body {
            padding: 30px;
        }
        
        /* Steps */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 15%;
            right: 15%;
            height: 3px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 0 5px rgba(139,69,19,0.15);
        }
        
        .step.completed .step-circle {
            background: var(--success);
            color: white;
        }
        
        .step p {
            font-size: 11px;
            color: #999;
            margin-top: 6px;
            font-weight: 600;
        }
        
        .step.active p,
        .step.completed p {
            color: #333;
        }
        
        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.6;
        }
        
        .alert i { margin-top: 2px; font-size: 16px; flex-shrink: 0; }
        .alert-info { background: #E3F2FD; color: #1565C0; border-left: 4px solid var(--info); }
        .alert-success { background: #E8F5E9; color: #2E7D32; border-left: 4px solid var(--success); }
        .alert-danger { background: #FFEBEE; color: #C62828; border-left: 4px solid var(--danger); }
        .alert-warning { background: #FFF3E0; color: #E65100; border-left: 4px solid var(--warning); }
        
        /* Form */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: #444;
        }
        
        .form-group label i {
            margin-right: 6px;
            color: var(--primary);
            width: 16px;
            text-align: center;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139,69,19,0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(139,69,19,0.3);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #43A047;
        }
        
        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Requirements */
        .req-list {
            list-style: none;
        }
        
        .req-list li {
            padding: 10px 14px;
            margin-bottom: 6px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
        
        .req-list .pass { color: var(--success); font-weight: 700; }
        .req-list .fail { color: var(--danger); font-weight: 700; }
        
        /* Progress */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #66BB6A);
            transition: width 0.5s ease;
            border-radius: 4px;
        }
        
        /* Log */
        .log-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 10px;
            max-height: 250px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .log-success { color: #4ec9b0; }
        .log-error { color: #f48771; }
        .log-warning { color: #dcdcaa; }
        .log-info { color: #569cd6; }
        .log-highlight { color: #ffd700; font-weight: bold; }
        
        /* Success */
        .success-icon {
            font-size: 70px;
            color: var(--success);
            text-align: center;
            margin: 10px 0;
            animation: bounce 1s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-box p {
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .info-box strong {
            color: var(--primary);
        }
        
        @media (max-width: 600px) {
            .install-body { padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
            .btn-group { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="install-container">
        
        <?php if ($step == 1): ?>
        <!-- ========== STEP 1: WELCOME ========== -->
        <div class="install-header">
            <span class="icon">🎂</span>
            <h1>Kayumanies Cake Shop</h1>
            <p>Installation Wizard v2.0</p>
        </div>
        
        <div class="install-body">
            <div class="steps">
                <div class="step active">
                    <div class="step-circle">1</div>
                    <p>Welcome</p>
                </div>
                <div class="step">
                    <div class="step-circle">2</div>
                    <p>Database</p>
                </div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <p>Install</p>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <p>Selesai</p>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Selamat Datang!</strong><br>
                    Wizard ini akan menginstall Kayumanies Cake Shop secara otomatis.<br>
                    Folder dan database akan dibuat otomatis.
                </div>
            </div>
            
            <div class="info-box">
                <p><strong>🛠️ Yang Akan Dilakukan:</strong></p>
                <p>✅ Membuat semua folder yang diperlukan</p>
                <p>✅ Membuat database <strong>kayumanies1</strong></p>
                <p>✅ Membuat semua tabel (users, products, orders, dll)</p>
                <p>✅ Insert data default (admin, kategori, produk, promo)</p>
                <p>✅ Membuat file konfigurasi</p>
            </div>
            
            <div class="info-box">
                <p><strong>📋 Persyaratan:</strong></p>
                <p>✅ PHP 7.3+ (Anda: <strong><?php echo PHP_VERSION; ?></strong>)</p>
                <p>✅ MySQL / MariaDB</p>
                <p>✅ PDO Extension</p>
            </div>
            
            <a href="install.php?step=2" class="btn btn-primary">
                <i class="fas fa-arrow-right"></i> Mulai Installasi
            </a>
        </div>
        
        <?php elseif ($step == 2): ?>
        <!-- ========== STEP 2: DATABASE CONFIG ========== -->
        <div class="install-header">
            <span class="icon">🗄️</span>
            <h1>Konfigurasi Database</h1>
        </div>
        
        <div class="install-body">
            <div class="steps">
                <div class="step completed">
                    <div class="step-circle"><i class="fas fa-check"></i></div>
                    <p>Welcome</p>
                </div>
                <div class="step active">
                    <div class="step-circle">2</div>
                    <p>Database</p>
                </div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <p>Install</p>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <p>Selesai</p>
                </div>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?php echo nl2br(htmlspecialchars($_GET['error'])); ?></div>
            </div>
            <?php endif; ?>
            
            <form action="install.php?step=3" method="POST">
                <div class="form-group">
                    <label><i class="fas fa-server"></i> Database Host</label>
                    <input type="text" name="db_host" value="localhost" placeholder="localhost" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-database"></i> Nama Database</label>
                    <input type="text" name="db_name" value="kayumanies1" placeholder="kayumanies1" required>
                    <small style="color:#999;font-size:11px;">Database akan dibuat otomatis jika belum ada</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="db_pass" value="ASD#$3KjpPd3">
                    </div>
                </div>
                
                <hr style="border:1px solid #eee;margin:20px 0;">
                
                <h3 style="margin-bottom:15px;color:#8B4513;">🔑 Akun Administrator</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Nama Lengkap</label>
                        <input type="text" name="admin_name" value="Administrator" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="admin_user" value="admin" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="admin_email" value="admin@kayumanies.com" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="admin_pass" value="admin123" required minlength="6">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Install Sekarang
                </button>
            </form>
        </div>
        
        <?php elseif ($step == 3): ?>
        <!-- ========== STEP 3: INSTALLATION PROCESS ========== -->
        <div class="install-header">
            <span class="icon">⚙️</span>
            <h1>Proses Installasi</h1>
        </div>
        
        <div class="install-body">
            <div class="steps">
                <div class="step completed">
                    <div class="step-circle"><i class="fas fa-check"></i></div>
                    <p>Welcome</p>
                </div>
                <div class="step completed">
                    <div class="step-circle"><i class="fas fa-check"></i></div>
                    <p>Database</p>
                </div>
                <div class="step active">
                    <div class="step-circle">3</div>
                    <p>Install</p>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <p>Selesai</p>
                </div>
            </div>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $db_host = trim($_POST['db_host']);
                $db_name = trim($_POST['db_name']);
                $db_user = trim($_POST['db_user']);
                $db_pass = $_POST['db_pass'];
                
                $admin_name = trim($_POST['admin_name']);
                $admin_user = trim($_POST['admin_user']);
                $admin_email = trim($_POST['admin_email']);
                $admin_pass = $_POST['admin_pass'];
                
                echo '<div class="log-box" id="installLog">';
                
                function outputLog($msg, $type = 'info') {
                    echo '<div class="log-' . $type . '">' . $msg . '</div>';
                    ob_flush(); flush();
                }
                
                try {
                    // ========== 1. CREATE DIRECTORIES ==========
                    outputLog('[INFO] Membuat struktur folder...', 'info');
                    
                    $all_dirs = array(
                        'assets/css', 'assets/js', 'assets/images',
                        'assets/uploads/products', 'assets/uploads/payments', 'assets/uploads/avatars',
                        'config', 'database', 'includes', 'logs',
                        'modules/admin', 'modules/auth', 'modules/kasir', 'modules/pembeli',
                        'api', 'invoice'
                    );
                    
                    foreach ($all_dirs as $dir) {
                        $path = __DIR__ . '/' . $dir;
                        if (!is_dir($path)) {
                            mkdir($path, 0755, true);
                            chmod($path, 0755);
                        }
                    }
                    outputLog('[OK] Struktur folder berhasil dibuat', 'success');
                    
                    // ========== 2. CREATE DEFAULT FILES ==========
                    outputLog('[INFO] Membuat file default...', 'info');
                    
                    // .htaccess
                    if (!file_exists(__DIR__ . '/.htaccess')) {
                        $htaccess = "RewriteEngine On\nRewriteBase /\nDirectoryIndex index.php\n";
                        file_put_contents(__DIR__ . '/.htaccess', $htaccess);
                    }
                    
                    // Default images
                    $placeholder_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect fill="#FFF5E6" width="400" height="400" rx="20"/><text fill="#8B4513" font-size="120" text-anchor="middle" x="200" y="220">🎂</text></svg>';
                    if (!file_exists(__DIR__ . '/assets/images/default-cake.jpg')) {
                        file_put_contents(__DIR__ . '/assets/images/default-cake.svg', $placeholder_svg);
                    }
                    
                    outputLog('[OK] File default berhasil dibuat', 'success');
                    
                    // ========== 3. CONNECT TO DATABASE ==========
                    outputLog('[INFO] Menghubungkan ke MySQL...', 'info');
                    
                    $dsn = "mysql:host={$db_host};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_user, $db_pass, array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ));
                    
                    outputLog('[OK] Koneksi MySQL berhasil', 'success');
                    
                    // ========== 4. CREATE DATABASE ==========
                    outputLog("[INFO] Membuat database '{$db_name}'...", 'info');
                    
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$db_name}`");
                    
                    outputLog("[OK] Database '{$db_name}' siap digunakan", 'success');
                    
                    // ========== 5. CREATE TABLES ==========
                    outputLog('[INFO] Membuat tabel...', 'info');
                    
                    $tables = array(
                        // Users
                        "CREATE TABLE IF NOT EXISTS `users` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `username` varchar(50) NOT NULL,
                            `email` varchar(100) NOT NULL,
                            `password` varchar(255) NOT NULL,
                            `full_name` varchar(100) DEFAULT NULL,
                            `phone` varchar(20) DEFAULT NULL,
                            `address` text DEFAULT NULL,
                            `role` enum('admin','kasir','pembeli') DEFAULT 'pembeli',
                            `avatar` varchar(255) DEFAULT 'default.jpg',
                            `is_active` tinyint(1) DEFAULT 1,
                            `last_login` datetime DEFAULT NULL,
                            `reset_token` varchar(100) DEFAULT NULL,
                            `reset_expires` datetime DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `username` (`username`),
                            UNIQUE KEY `email` (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Categories
                        "CREATE TABLE IF NOT EXISTS `categories` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `name` varchar(100) NOT NULL,
                            `slug` varchar(100) NOT NULL,
                            `description` text DEFAULT NULL,
                            `image` varchar(255) DEFAULT NULL,
                            `is_active` tinyint(1) DEFAULT 1,
                            `sort_order` int(11) DEFAULT 0,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `slug` (`slug`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Products
                        "CREATE TABLE IF NOT EXISTS `products` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `category_id` int(11) DEFAULT NULL,
                            `name` varchar(200) NOT NULL,
                            `slug` varchar(200) NOT NULL,
                            `description` text DEFAULT NULL,
                            `price` decimal(15,2) NOT NULL,
                            `discount_price` decimal(15,2) DEFAULT NULL,
                            `stock` int(11) DEFAULT 0,
                            `weight` varchar(50) DEFAULT NULL,
                            `image` varchar(255) DEFAULT 'default-cake.jpg',
                            `is_featured` tinyint(1) DEFAULT 0,
                            `is_active` tinyint(1) DEFAULT 1,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `slug` (`slug`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Cart
                        "CREATE TABLE IF NOT EXISTS `cart` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `user_id` int(11) NOT NULL,
                            `product_id` int(11) NOT NULL,
                            `quantity` int(11) DEFAULT 1,
                            `notes` text DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `unique_cart` (`user_id`,`product_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Orders
                        "CREATE TABLE IF NOT EXISTS `orders` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `order_number` varchar(20) NOT NULL,
                            `user_id` int(11) NOT NULL,
                            `cashier_id` int(11) DEFAULT NULL,
                            `total_amount` decimal(15,2) NOT NULL,
                            `discount_amount` decimal(15,2) DEFAULT 0.00,
                            `promo_code` varchar(50) DEFAULT NULL,
                            `final_amount` decimal(15,2) NOT NULL,
                            `payment_method` enum('cash','transfer','qris','ewallet') DEFAULT 'cash',
                            `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
                            `order_status` enum('pending','processing','ready','completed','cancelled') DEFAULT 'pending',
                            `pickup_date` date DEFAULT NULL,
                            `pickup_time` time DEFAULT NULL,
                            `notes` text DEFAULT NULL,
                            `customer_name` varchar(100) DEFAULT NULL,
                            `customer_phone` varchar(20) DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `order_number` (`order_number`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Order Details
                        "CREATE TABLE IF NOT EXISTS `order_details` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `order_id` int(11) NOT NULL,
                            `product_id` int(11) DEFAULT NULL,
                            `product_name` varchar(200) NOT NULL,
                            `price` decimal(15,2) NOT NULL,
                            `quantity` int(11) NOT NULL,
                            `subtotal` decimal(15,2) NOT NULL,
                            `notes` text DEFAULT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Payments
                        "CREATE TABLE IF NOT EXISTS `payments` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `order_id` int(11) NOT NULL,
                            `amount` decimal(15,2) NOT NULL,
                            `payment_method` enum('cash','transfer','qris','ewallet') NOT NULL,
                            `payment_proof` varchar(255) DEFAULT NULL,
                            `payment_status` enum('pending','verified','rejected') DEFAULT 'pending',
                            `verified_by` int(11) DEFAULT NULL,
                            `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `verified_at` timestamp NULL DEFAULT NULL,
                            `notes` text DEFAULT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Promos
                        "CREATE TABLE IF NOT EXISTS `promos` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `code` varchar(50) NOT NULL,
                            `name` varchar(100) NOT NULL,
                            `description` text DEFAULT NULL,
                            `discount_type` enum('percentage','fixed') NOT NULL,
                            `discount_value` decimal(15,2) NOT NULL,
                            `min_purchase` decimal(15,2) DEFAULT 0.00,
                            `start_date` datetime NOT NULL,
                            `end_date` datetime NOT NULL,
                            `usage_limit` int(11) DEFAULT NULL,
                            `usage_count` int(11) DEFAULT 0,
                            `is_active` tinyint(1) DEFAULT 1,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `code` (`code`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Reviews
                        "CREATE TABLE IF NOT EXISTS `reviews` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `user_id` int(11) NOT NULL,
                            `product_id` int(11) NOT NULL,
                            `order_id` int(11) DEFAULT NULL,
                            `rating` tinyint(4) NOT NULL,
                            `comment` text DEFAULT NULL,
                            `is_approved` tinyint(1) DEFAULT 0,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Notifications
                        "CREATE TABLE IF NOT EXISTS `notifications` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `user_id` int(11) NOT NULL,
                            `title` varchar(200) NOT NULL,
                            `message` text NOT NULL,
                            `type` enum('order','payment','system','promo') DEFAULT 'system',
                            `is_read` tinyint(1) DEFAULT 0,
                            `link` varchar(255) DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Settings
                        "CREATE TABLE IF NOT EXISTS `settings` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `setting_key` varchar(100) NOT NULL,
                            `setting_value` text DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `setting_key` (`setting_key`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Payment Methods
                        "CREATE TABLE IF NOT EXISTS `payment_methods` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `name` varchar(100) NOT NULL,
                            `type` enum('bank','qris','cash','ewallet') DEFAULT 'bank',
                            `account_number` varchar(50) DEFAULT NULL,
                            `account_name` varchar(100) DEFAULT NULL,
                            `bank_name` varchar(100) DEFAULT NULL,
                            `qris_image` varchar(255) DEFAULT NULL,
                            `instructions` text DEFAULT NULL,
                            `is_active` tinyint(1) DEFAULT 1,
                            `sort_order` int(11) DEFAULT 0,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                        
                        // Activity Log
                        "CREATE TABLE IF NOT EXISTS `activity_log` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `user_id` int(11) DEFAULT NULL,
                            `action` varchar(100) NOT NULL,
                            `description` text DEFAULT NULL,
                            `ip_address` varchar(45) DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                    
                    $table_count = 0;
                    foreach ($tables as $sql) {
                        $pdo->exec($sql);
                        $table_count++;
                    }
                    outputLog("[OK] {$table_count} tabel berhasil dibuat", 'success');
                    
                    // ========== 6. INSERT DEFAULT DATA ==========
                    outputLog('[INFO] Memasukkan data default...', 'info');
                    
                    // Admin user
                    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES (?, ?, ?, ?, 'admin')")->execute([$admin_user, $admin_email, $hashed_password, $admin_name]);
                    outputLog("[OK] Admin '{$admin_user}' berhasil dibuat", 'success');
                    
                    // Settings
                    $settings = array(
                        ['store_name', 'Kayumanies Cake Shop'],
                        ['store_phone', '08123456789'],
                        ['store_email', 'info@kayumanies.com'],
                        ['store_address', 'Jl. Kayu Manis No. 123, Jakarta'],
                        ['store_description', 'Toko Kue Premium'],
                        ['tax_percentage', '10'],
                        ['currency', 'IDR'],
                        ['opening_hours', '08:00 - 21:00'],
                        ['min_order', '50000']
                    );
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    foreach ($settings as $s) $stmt->execute($s);
                    outputLog('[OK] Settings default berhasil dibuat', 'success');
                    
                    // Categories
                    $cats = array(
                        ['Birthday Cake', 'birthday-cake', 'Kue ulang tahun spesial', 1],
                        ['Wedding Cake', 'wedding-cake', 'Kue pernikahan elegan', 2],
                        ['Cupcake', 'cupcake', 'Cupcake mini berbagai rasa', 3],
                        ['Traditional Cake', 'traditional-cake', 'Kue tradisional Indonesia', 4],
                        ['Pastry', 'pastry', 'Aneka pastry dan roti', 5],
                        ['Dessert Box', 'dessert-box', 'Dessert box premium', 6]
                    );
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)");
                    foreach ($cats as $c) $stmt->execute($c);
                    outputLog('[OK] 6 kategori berhasil dibuat', 'success');
                    
                    // Sample products
                    $products = array(
                        [1, 'Chocolate Birthday Cake', 'chocolate-birthday-cake', 'Kue ulang tahun coklat premium', 350000, 299000, 10, '1.5 kg'],
                        [1, 'Vanilla Birthday Cake', 'vanilla-birthday-cake', 'Kue ulang tahun vanilla klasik', 275000, null, 15, '1.5 kg'],
                        [2, 'Elegant Wedding Cake', 'elegant-wedding-cake', 'Kue pernikahan 3 tingkat', 3500000, 2999000, 5, '5 kg'],
                        [3, 'Red Velvet Cupcake', 'red-velvet-cupcake', 'Cupcake red velvet premium', 25000, null, 50, '100g'],
                        [3, 'Chocolate Cupcake', 'chocolate-cupcake', 'Cupcake coklat Belgia', 20000, 18000, 40, '100g'],
                        [4, 'Lapis Legit', 'lapis-legit', 'Kue lapis legit rempah pilihan', 150000, null, 20, '500g'],
                        [5, 'Croissant Butter', 'croissant-butter', 'Croissant butter import', 35000, 30000, 30, '100g'],
                        [6, 'Dessert Box Chocolate', 'dessert-box-chocolate', 'Dessert box coklat premium', 85000, null, 25, '500g']
                    );
                    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, price, discount_price, stock, weight, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
                    foreach ($products as $p) $stmt->execute($p);
                    outputLog('[OK] 8 produk sample berhasil dibuat', 'success');
                    
                    // Promos
                    $promos = array(
                        ['WELCOME10', 'Welcome 10%', 'Diskon 10% untuk pembelian pertama', 'percentage', 10, 100000, '2024-01-01 00:00:00', '2025-12-31 23:59:59'],
                        ['FLAT50', 'Flat 50rb', 'Potongan Rp 50.000', 'fixed', 50000, 300000, '2024-01-01 00:00:00', '2025-12-31 23:59:59']
                    );
                    $stmt = $pdo->prepare("INSERT INTO promos (code, name, description, discount_type, discount_value, min_purchase, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($promos as $pr) $stmt->execute($pr);
                    outputLog('[OK] 2 promo berhasil dibuat', 'success');
                    
                    // Payment Methods
                    $pay_methods = array(
                        ['BCA', 'bank', '1234567890', 'Kayumanies Cake Shop', 'BCA', 'Transfer ke BCA', 1],
                        ['Mandiri', 'bank', '0987654321', 'Kayumanies Cake Shop', 'Mandiri', 'Transfer ke Mandiri', 2],
                        ['QRIS', 'qris', null, null, null, 'Scan QRIS', 3],
                        ['Cash', 'cash', null, null, null, 'Bayar di toko', 4]
                    );
                    $stmt = $pdo->prepare("INSERT INTO payment_methods (name, type, account_number, account_name, bank_name, instructions, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($pay_methods as $pm) $stmt->execute($pm);
                    outputLog('[OK] 4 metode pembayaran berhasil dibuat', 'success');
                    
                    // ========== 7. CREATE CONFIG FILE ==========
                    outputLog('[INFO] Membuat file konfigurasi...', 'info');
                    
                    $base_url = getBaseUrl();
                    
                    $config_content = "<?php
/**
 * Kayumanies Cake Shop - Database Configuration
 * Auto-generated by installer on " . date('Y-m-d H:i:s') . "
 */

define('APP_RUNNING', true);
define('DB_HOST', '" . addslashes($db_host) . "');
define('DB_NAME', '" . addslashes($db_name) . "');
define('DB_USER', '" . addslashes($db_user) . "');
define('DB_PASS', '" . addslashes($db_pass) . "');
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', '" . addslashes($base_url) . "');
define('APP_NAME', 'Kayumanies Cake Shop');
define('APP_VERSION', '2.0.0');
define('TIMEZONE', 'Asia/Jakarta');

date_default_timezone_set(TIMEZONE);

class Database {
    private \$host;
    private \$db_name;
    private \$username;
    private \$password;
    private \$charset;
    public \$conn;
    private static \$instance = null;

    public function __construct() {
        \$this->host = DB_HOST;
        \$this->db_name = DB_NAME;
        \$this->username = DB_USER;
        \$this->password = DB_PASS;
        \$this->charset = DB_CHARSET;
    }

    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    public function getConnection() {
        if (\$this->conn !== null) return \$this->conn;
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=\" . \$this->charset;
            \$options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            );
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password, \$options);
            return \$this->conn;
        } catch(PDOException \$e) {
            error_log(\"DB Error: \" . \$e->getMessage());
            throw new Exception(\"Database connection failed\");
        }
    }

    public function prepare(\$sql) { return \$this->getConnection()->prepare(\$sql); }
    public function query(\$sql) { return \$this->getConnection()->query(\$sql); }
    public function lastInsertId() { return \$this->getConnection()->lastInsertId(); }
    public function beginTransaction() { return \$this->getConnection()->beginTransaction(); }
    public function commit() { return \$this->getConnection()->commit(); }
    public function rollback() { return \$this->getConnection()->rollBack(); }
}
?>";
                    
                    file_put_contents(__DIR__ . '/config/database.php', $config_content);
                    outputLog('[OK] config/database.php berhasil dibuat', 'success');
                    
                    // Create installed lock
                    $lock_data = array(
                        'version' => '2.0.0',
                        'installed_at' => date('Y-m-d H:i:s'),
                        'php_version' => PHP_VERSION,
                        'db_name' => $db_name
                    );
                    file_put_contents(__DIR__ . '/config/installed.lock', json_encode($lock_data, JSON_PRETTY_PRINT));
                    outputLog('[OK] Installation lock file created', 'success');
                    
                    echo '</div>';
                    
                    // ========== SUCCESS ==========
                    echo '<div class="success-icon">✅</div>';
                    echo '<div class="alert alert-success">';
                    echo '<i class="fas fa-check-circle"></i>';
                    echo '<div><strong>Installasi Berhasil!</strong><br>Kayumanies Cake Shop telah terinstall.</div>';
                    echo '</div>';
                    
                    echo '<div class="info-box">';
                    echo '<p><strong>🔑 Login Admin:</strong></p>';
                    echo '<p>URL: <strong>' . htmlspecialchars($base_url) . '/modules/auth/login.php</strong></p>';
                    echo '<p>Username: <strong>' . htmlspecialchars($admin_user) . '</strong></p>';
                    echo '<p>Password: <strong>' . htmlspecialchars($admin_pass) . '</strong></p>';
                    echo '<p style="color:#f44336;margin-top:8px;">⚠️ Simpan informasi ini! Hapus install.php untuk keamanan.</p>';
                    echo '</div>';
                    
                    echo '<div class="btn-group">';
                    echo '<a href="index.php" class="btn btn-success"><i class="fas fa-home"></i> Halaman Utama</a>';
                    echo '<a href="modules/auth/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login Admin</a>';
                    echo '</div>';
                    
                } catch (PDOException $e) {
                    echo '</div>';
                    echo '<div class="alert alert-danger">';
                    echo '<i class="fas fa-times-circle"></i>';
                    echo '<div><strong>Database Error:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '</div>';
                    echo '<a href="install.php?step=2&error=' . urlencode($e->getMessage()) . '" class="btn btn-primary">';
                    echo '<i class="fas fa-arrow-left"></i> Kembali & Perbaiki';
                    echo '</a>';
                }
            }
            ?>
        </div>
        
        <?php endif; ?>
    </div>
    
    <script>
        // Scroll log to bottom
        var logBox = document.getElementById('installLog');
        if (logBox) {
            logBox.scrollTop = logBox.scrollHeight;
            var observer = new MutationObserver(function() {
                logBox.scrollTop = logBox.scrollHeight;
            });
            observer.observe(logBox, { childList: true, subtree: true });
        }
    </script>
</body>
</html>
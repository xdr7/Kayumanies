<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header('Location: ../auth/login.php'); exit; }

$database = Database::getInstance();
$db = $database->getConnection();

// Handle reset
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    $defaults = [
        'theme_primary' => '#8B4513',
        'theme_primary_dark' => '#6B3410',
        'theme_primary_light' => '#A0522D',
        'theme_gold' => '#FFD700',
        'theme_accent' => '#FF6B6B',
        'theme_bg_warm' => '#FFF8F0',
        'theme_bg_cream' => '#FFF5E6',
        'theme_text_dark' => '#2C1810',
        'theme_text_gray' => '#666',
        'theme_footer_bg' => '#2C1810',
        'theme_footer_text' => '#ffffff',
        'theme_footer_link' => 'rgba(255,255,255,0.7)',
        'theme_footer_social_bg' => 'rgba(255,255,255,0.1)',
        'theme_footer_border' => 'rgba(255,255,255,0.1)',
        'theme_footer_copyright' => 'rgba(255,255,255,0.5)',
        'theme_radius' => '16',
        'theme_font' => 'Segoe UI',
    ];
    
    foreach ($defaults as $key => $value) {
        $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        } else {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        }
        $stmt->execute([$value, $key]);
    }
    
    $_SESSION['flash_msg'] = '✅ Tema berhasil direset ke default!';
    $_SESSION['flash_type'] = 'success';
    header('Location: theme-settings.php');
    exit;
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'theme_') === 0) {
            $setting_key = $key;
            $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $stmt->execute([$setting_key]);
            if ($stmt->fetch()) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            } else {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            }
            $stmt->execute([$value, $setting_key]);
        }
    }
    $success = "✅ Tema berhasil disimpan!";
}

// Flash message
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Get current theme
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'theme_%'");
$theme = [];
foreach ($stmt->fetchAll() as $row) {
    $key = str_replace('theme_', '', $row['setting_key']);
    $theme[$key] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tema Warna - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>🎨 Pengaturan Tema</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Tema</div>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($flash_msg): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $flash_msg; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            
            <!-- WARNA UTAMA -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-palette"></i> Warna Utama</div>
                </div>
                <div class="card-body">
                    <div class="settings-grid">
                        <div class="form-group">
                            <label class="form-label">Warna Primer</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_primary" value="<?php echo $theme['primary'] ?? '#8B4513'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['primary'] ?? '#8B4513'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Primer Gelap</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_primary_dark" value="<?php echo $theme['primary_dark'] ?? '#6B3410'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['primary_dark'] ?? '#6B3410'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Emas/Aksen</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_gold" value="<?php echo $theme['gold'] ?? '#FFD700'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['gold'] ?? '#FFD700'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Accent</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_accent" value="<?php echo $theme['accent'] ?? '#FF6B6B'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['accent'] ?? '#FF6B6B'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Background Warm</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_bg_warm" value="<?php echo $theme['bg_warm'] ?? '#FFF8F0'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['bg_warm'] ?? '#FFF8F0'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Background Cream</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_bg_cream" value="<?php echo $theme['bg_cream'] ?? '#FFF5E6'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['bg_cream'] ?? '#FFF5E6'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Teks Gelap</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_text_dark" value="<?php echo $theme['text_dark'] ?? '#2C1810'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['text_dark'] ?? '#2C1810'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Teks Abu</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_text_gray" value="<?php echo $theme['text_gray'] ?? '#666'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['text_gray'] ?? '#666'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Border Radius (px)</label>
                            <input type="number" name="theme_radius" class="form-control" value="<?php echo $theme['radius'] ?? '16'; ?>" min="0" max="50">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Font Family</label>
                            <select name="theme_font" class="form-control">
                                <option value="Segoe UI" <?php echo ($theme['font']??'')=='Segoe UI'?'selected':''; ?>>Segoe UI</option>
                                <option value="Poppins" <?php echo ($theme['font']??'')=='Poppins'?'selected':''; ?>>Poppins</option>
                                <option value="Roboto" <?php echo ($theme['font']??'')=='Roboto'?'selected':''; ?>>Roboto</option>
                                <option value="Inter" <?php echo ($theme['font']??'')=='Inter'?'selected':''; ?>>Inter</option>
                                <option value="Montserrat" <?php echo ($theme['font']??'')=='Montserrat'?'selected':''; ?>>Montserrat</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- WARNA FOOTER -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-window-maximize"></i> Warna Footer</div>
                </div>
                <div class="card-body">
                    <div class="settings-grid">
                        <div class="form-group">
                            <label class="form-label">Background Footer</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_footer_bg" value="<?php echo $theme['footer_bg'] ?? '#2C1810'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['footer_bg'] ?? '#2C1810'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Teks Footer</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_footer_text" value="<?php echo $theme['footer_text'] ?? '#ffffff'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['footer_text'] ?? '#ffffff'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Link Footer</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_footer_link" value="<?php echo $theme['footer_link'] ?? '#cccccc'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['footer_link'] ?? '#cccccc'; ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Warna Copyright</label>
                            <div class="d-flex gap-1 align-center">
                                <input type="color" name="theme_footer_copyright" value="<?php echo $theme['footer_copyright'] ?? '#888888'; ?>" style="width:50px;height:38px;border:none;cursor:pointer;">
                                <input type="text" class="form-control" value="<?php echo $theme['footer_copyright'] ?? '#888888'; ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TOMBOL -->
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    <i class="fas fa-save"></i> Simpan Tema
                </button>
                <a href="theme-settings.php?reset=1" class="btn btn-warning" style="flex:1;" onclick="return confirm('Reset tema ke default?')">
                    <i class="fas fa-undo"></i> Reset Default
                </a>
            </div>
            
        </form>
        
    </main>
    
    <script>
    setTimeout(function() {
        var a = document.querySelector('.alert');
        if (a) { a.style.opacity = '0'; setTimeout(function() { if (a.parentNode) a.parentNode.removeChild(a); }, 500); }
    }, 5000);
    </script>
</body>
</html>
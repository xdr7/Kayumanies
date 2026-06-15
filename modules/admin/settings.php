<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Handle save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simpan text settings
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            
            // Cek apakah setting_key sudah ada
            $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $stmt->execute([$setting_key]);
            
            if ($stmt->fetch()) {
                // Update
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([trim($value), $setting_key]);
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$setting_key, trim($value)]);
            }
        }
    }
    
    // Upload logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../assets/images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $logo_name = 'logo.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_name);
            
            $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = 'store_logo'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'store_logo'");
            } else {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('store_logo', ?)");
            }
            $stmt->execute([$logo_name]);
        }
    }
    
    // Upload favicon
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../assets/images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['ico', 'png', 'jpg', 'jpeg'])) {
            $favicon_name = 'favicon.' . $ext;
            move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_dir . $favicon_name);
            
            $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = 'store_favicon'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'store_favicon'");
            } else {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('store_favicon', ?)");
            }
            $stmt->execute([$favicon_name]);
        }
    }
	
	// Upload hero image
	if (isset($_FILES['hero_image_file']) && $_FILES['hero_image_file']['error'] == 0) {
		$upload_dir = __DIR__ . '/../../assets/images/';
		if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
		
		$ext = strtolower(pathinfo($_FILES['hero_image_file']['name'], PATHINFO_EXTENSION));
		if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
			$hero_img = 'hero.' . $ext;
			move_uploaded_file($_FILES['hero_image_file']['tmp_name'], $upload_dir . $hero_img);
			
			$stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = 'hero_image'");
			$stmt->execute();
			if ($stmt->fetch()) {
				$stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'hero_image'");
			} else {
				$stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('hero_image', ?)");
			}
			$stmt->execute([$hero_img]);
		}
	}
    
    $success = "✅ Pengaturan berhasil disimpan!";
}

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY id ASC");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Logo & favicon
$logo = $settings['store_logo'] ?? 'logo.png';
$logo_path = '../../assets/images/' . $logo;
$logo_exists = file_exists(__DIR__ . '/../../assets/images/' . $logo);

$favicon = $settings['store_favicon'] ?? 'favicon.ico';
$favicon_path = '../../assets/images/' . $favicon;
$favicon_exists = file_exists(__DIR__ . '/../../assets/images/' . $favicon);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>⚙️ Pengaturan Toko</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Pengaturan</div>
            </div>
            <div class="top-bar-right">
                <span class="text-sm text-muted">Konfigurasi sistem</span>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo $success; ?></span></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            
            <!-- LOGO -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-image"></i> Logo Aplikasi</div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 align-center flex-wrap">
                        <div>
                            <?php if ($logo_exists): ?>
                            <img src="<?php echo $logo_path; ?>?v=<?php echo time(); ?>" style="max-width:150px;max-height:80px;border-radius:8px;border:2px solid #eee;">
                            <?php else: ?>
                            <div style="width:150px;height:80px;background:#FFF3E0;border-radius:8px;border:2px solid #eee;display:flex;align-items:center;justify-content:center;font-size:40px;">🎂</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Upload Logo Baru</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="form-hint">Format: JPG/PNG/GIF. Logo akan muncul di navbar & footer.</small>
                        </div>
                    </div>
                </div>
				<!-- HERO SETTINGS -->
					<div class="card">
						<div class="card-header">
							<div class="card-title"><i class="fas fa-home"></i> Pengaturan Hero (Beranda)</div>
						</div>
						<div class="card-body">
							<div class="settings-grid">
								<div class="form-group">
									<label class="form-label">Badge Text</label>
									<input type="text" name="setting_hero_badge" class="form-control" value="<?php echo htmlspecialchars($settings['hero_badge'] ?? '✨ Premium Quality Cake'); ?>">
								</div>
								<div class="form-group">
									<label class="form-label">Judul Hero (HTML allowed)</label>
									<input type="text" name="setting_hero_title" class="form-control" value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Kue Lezat untuk <span class=highlight>Momen Istimewa</span>'); ?>">
								</div>
								<div class="form-group full-width">
									<label class="form-label">Deskripsi Hero</label>
									<textarea name="setting_hero_desc" class="form-control" rows="2"><?php echo htmlspecialchars($settings['hero_desc'] ?? 'Nikmati kelezatan kue premium...'); ?></textarea>
								</div>
								<div class="form-group">
									<label class="form-label">Upload Hero Image</label>
									<input type="file" name="hero_image_file" class="form-control" accept="image/*">
									<small class="form-hint">Kosongkan jika tidak ingin mengubah</small>
								</div>
								<div class="form-group"><label class="form-label">Stat 1 - Angka</label><input type="text" name="setting_hero_stat1_num" class="form-control" value="<?php echo htmlspecialchars($settings['hero_stat1_num'] ?? '1000+'); ?>"></div>
								<div class="form-group"><label class="form-label">Stat 1 - Label</label><input type="text" name="setting_hero_stat1_label" class="form-control" value="<?php echo htmlspecialchars($settings['hero_stat1_label'] ?? 'Pelanggan Puas'); ?>"></div>
								<div class="form-group"><label class="form-label">Stat 2 - Angka</label><input type="text" name="setting_hero_stat2_num" class="form-control" value="<?php echo htmlspecialchars($settings['hero_stat2_num'] ?? '50+'); ?>"></div>
								<div class="form-group"><label class="form-label">Stat 2 - Label</label><input type="text" name="setting_hero_stat2_label" class="form-control" value="<?php echo htmlspecialchars($settings['hero_stat2_label'] ?? 'Varian Kue'); ?>"></div>
								<div class="form-group"><label class="form-label">Stat 3 - Angka</label><input type="text" name="setting_hero_stat3_num" class="form-control" value="<?php echo htmlspecialchars($settings['hero_stat3_num'] ?? '⭐4.9'); ?>"></div>
								<div class="form-group"><label class="form-label">Stat 3 - Label</label><input type="text" name="setting_hero_stat3_label" class="form-control" value="<?php echo htmlspecialchars($settings['hero_stat3_label'] ?? 'Rating'); ?>"></div>
							</div>
						</div>
					</div>
            </div>
            
            <!-- FAVICON -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-star"></i> Favicon Website</div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 align-center flex-wrap">
                        <div>
                            <?php if ($favicon_exists): ?>
                            <img src="<?php echo $favicon_path; ?>?v=<?php echo time(); ?>" style="width:32px;height:32px;border-radius:4px;">
                            <?php else: ?>
                            <div style="width:32px;height:32px;background:#FFF3E0;border-radius:4px;display:flex;align-items:center;justify-content:center;">🎂</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Upload Favicon Baru</label>
                            <input type="file" name="favicon" class="form-control" accept="image/*,.ico">
                            <small class="form-hint">Format: ICO/PNG/JPG. Rekomendasi: 32x32px.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- INFORMASI TOKO -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-store"></i> Informasi Toko</div>
                </div>
                <div class="card-body">
                    <div class="settings-grid">
                        <div class="form-group">
                            <label class="form-label">Nama Toko <span class="required">*</span></label>
                            <input type="text" name="setting_store_name" class="form-control" value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>" placeholder="Nama toko Anda" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Toko</label>
                            <input type="email" name="setting_store_email" class="form-control" value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>" placeholder="email@tokokue.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="setting_store_phone" class="form-control" value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>" placeholder="0812-3456-7890">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Operasional</label>
                            <input type="text" name="setting_opening_hours" class="form-control" value="<?php echo htmlspecialchars($settings['opening_hours'] ?? '08:00 - 21:00'); ?>" placeholder="08:00 - 21:00">
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="setting_store_address" class="form-control" rows="2" placeholder="Jl. Kayu Manis No. 123, Jakarta"><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Deskripsi Toko</label>
                            <textarea name="setting_store_description" class="form-control" rows="3" placeholder="Deskripsi singkat tentang toko kue Anda"><?php echo htmlspecialchars($settings['store_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER SETTINGS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-window-maximize"></i> Pengaturan Footer</div>
                </div>
                <div class="card-body">
                    <div class="settings-grid">
                        <div class="form-group">
                            <label class="form-label">Copyright Text</label>
                            <input type="text" name="setting_footer_copyright" class="form-control" value="<?php echo htmlspecialchars($settings['footer_copyright'] ?? 'Made with ❤️'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Instagram URL</label>
                            <input type="text" name="setting_social_instagram" class="form-control" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Facebook URL</label>
                            <input type="text" name="setting_social_facebook" class="form-control" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="setting_social_whatsapp" class="form-control" value="<?php echo htmlspecialchars($settings['social_whatsapp'] ?? ''); ?>" placeholder="6281234567890">
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Footer Description</label>
                            <textarea name="setting_footer_description" class="form-control" rows="2"><?php echo htmlspecialchars($settings['footer_description'] ?? 'Toko kue premium...'); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PENGATURAN BISNIS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-cogs"></i> Pengaturan Bisnis</div>
                </div>
                <div class="card-body">
                    <div class="settings-grid">
                        <div class="form-group">
                            <label class="form-label">Mata Uang</label>
                            <select name="setting_currency" class="form-control">
                                <option value="IDR" <?php echo ($settings['currency'] ?? '') == 'IDR' ? 'selected' : ''; ?>>🇮🇩 IDR</option>
                                <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>🇺🇸 USD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Persentase Pajak (%)</label>
                            <input type="number" name="setting_tax_percentage" class="form-control" value="<?php echo htmlspecialchars($settings['tax_percentage'] ?? '10'); ?>" step="0.1" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Minimal Order (Rp)</label>
                            <input type="number" name="setting_min_order" class="form-control" value="<?php echo htmlspecialchars($settings['min_order'] ?? '50000'); ?>" step="10000" min="0">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Semua Pengaturan</button>
            
        </form>
        
    </main>
    
    <script>
    setTimeout(function() {
        var a = document.querySelector('.alert');
        if (a) { a.style.opacity = '0'; setTimeout(function() { if (a.parentNode) a.parentNode.removeChild(a); }, 500); }
    }, 5000);
    
    var changed = false;
    document.querySelector('form').querySelectorAll('input,textarea,select').forEach(function(el) {
        el.addEventListener('change', function() { changed = true; });
    });
    document.querySelector('form').addEventListener('submit', function() { changed = false; });
    window.addEventListener('beforeunload', function(e) { if (changed) { e.preventDefault(); e.returnValue = ''; return ''; } });
    </script>
    
</body>
</html>
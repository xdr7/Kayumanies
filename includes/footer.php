<?php
$store_name = 'Kayumanies Cake Shop';
$store_address = 'Jl. Kayu Manis No. 123';
$store_phone = '08123456789';
$store_email = 'info@kayumanies.com';
$footer_desc = 'Toko kue premium dengan berbagai pilihan kue lezat untuk segala kesempatan spesial Anda.';
$footer_copy = 'Made with ❤️';
$social_ig = '#';
$social_fb = '#';
$social_wa = '#';
$logo = '';

try {
    require_once __DIR__ . '/../config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $store_name = $settings['store_name'] ?? $store_name;
    $store_address = $settings['store_address'] ?? $store_address;
    $store_phone = $settings['store_phone'] ?? $store_phone;
    $store_email = $settings['store_email'] ?? $store_email;
    $footer_desc = $settings['footer_description'] ?? $footer_desc;
    $footer_copy = $settings['footer_copyright'] ?? $footer_copy;
    $social_ig = $settings['social_instagram'] ?? '#';
    $social_fb = $settings['social_facebook'] ?? '#';
    $social_wa = $settings['social_whatsapp'] ?? '#';
    $logo = $settings['store_logo'] ?? '';
    $opening_hours = $settings['opening_hours'] ?? '08:00 - 21:00';
} catch (Exception $e) {}

$logo_url = '';
if (!empty($logo)) {
    $logo_path = $base_path . 'assets/images/' . $logo;
    if (file_exists(__DIR__ . '/../assets/images/' . $logo)) {
        $logo_url = $logo_path;
    }
}
?>

<footer class="footer" id="contact">
    <div class="container">
        <div class="footer-grid">
            <div>
                <h4>
                    <?php if ($logo_url): ?>
                    <img src="<?php echo $logo_url; ?>" alt="<?php echo htmlspecialchars($store_name); ?>" style="max-height:30px;vertical-align:middle;margin-right:8px;">
                    <?php else: ?>
                    🎂
                    <?php endif; ?>
                    <?php echo htmlspecialchars($store_name); ?>
                </h4>
                <p><?php echo htmlspecialchars($footer_desc); ?></p>
                <div class="social-links">
                    <?php if ($social_ig != '#' && !empty($social_ig)): ?>
                    <a href="<?php echo htmlspecialchars($social_ig); ?>" class="social-link" target="_blank" rel="noopener">📱</a>
                    <?php endif; ?>
                    <?php if ($social_fb != '#' && !empty($social_fb)): ?>
                    <a href="<?php echo htmlspecialchars($social_fb); ?>" class="social-link" target="_blank" rel="noopener">📘</a>
                    <?php endif; ?>
                    <?php if ($social_wa != '#' && !empty($social_wa)): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $social_wa); ?>" class="social-link" target="_blank" rel="noopener">💬</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <h4>Menu</h4>
                <a href="<?php echo $base_path ?? ''; ?>index.php">Beranda</a><br>
                <a href="<?php echo $base_path ?? ''; ?>modules/pembeli/products.php">Produk</a><br>
                <a href="<?php echo $base_path ?? ''; ?>index.php#categories">Kategori</a><br>
                <a href="#">Promo</a>
            </div>
            
            <div>
				<h4>Bantuan</h4>
				<a href="<?php echo $base_path ?? ''; ?>modules/pembeli/help.php?page=order">Cara Pesan</a><br>
				<a href="<?php echo $base_path ?? ''; ?>modules/pembeli/help.php?page=delivery">Pengiriman</a><br>
				<a href="<?php echo $base_path ?? ''; ?>modules/pembeli/help.php?page=payment">Pembayaran</a><br>
				<a href="<?php echo $base_path ?? ''; ?>modules/pembeli/help.php?page=faq">FAQ</a>
			</div>
            
            <div>
                <h4>Kontak</h4>
                <p>📍 <?php echo htmlspecialchars($store_address); ?></p>
                <p>📞 <?php echo htmlspecialchars($store_phone); ?></p>
                <p>📧 <?php echo htmlspecialchars($store_email); ?></p>
                <p>🕐 <?php echo htmlspecialchars($opening_hours); ?></p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($store_name); ?>. <?php echo htmlspecialchars($footer_copy); ?></p>
        </div>
    </div>
</footer>
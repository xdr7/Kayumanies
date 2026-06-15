<?php
$theme = [
    'primary' => '#8B4513',
    'primary_dark' => '#6B3410',
    'primary_light' => '#A0522D',
    'gold' => '#FFD700',
    'accent' => '#FF6B6B',
    'bg_warm' => '#FFF8F0',
    'bg_cream' => '#FFF5E6',
    'text_dark' => '#2C1810',
    'text_gray' => '#666',
    'footer_bg' => '#2C1810',
    'footer_text' => '#ffffff',
    'footer_link' => 'rgba(255,255,255,0.7)',
    'footer_social_bg' => 'rgba(255,255,255,0.1)',
    'footer_border' => 'rgba(255,255,255,0.1)',
    'footer_copyright' => 'rgba(255,255,255,0.5)',
    'radius' => '16',
    'font' => 'Segoe UI'
];

try {
    require_once __DIR__ . '/../config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'theme_%'");
    foreach ($stmt->fetchAll() as $row) {
        $key = str_replace('theme_', '', $row['setting_key']);
        $theme[$key] = $row['setting_value'];
    }
} catch (Exception $e) {}
?>
<style>
:root {
    --primary: <?php echo $theme['primary']; ?>;
    --primary-dark: <?php echo $theme['primary_dark']; ?>;
    --primary-light: <?php echo $theme['primary_light']; ?>;
    --gold: <?php echo $theme['gold']; ?>;
    --accent: <?php echo $theme['accent']; ?>;
    --bg-warm: <?php echo $theme['bg_warm']; ?>;
    --bg-cream: <?php echo $theme['bg_cream']; ?>;
    --text-dark: <?php echo $theme['text_dark']; ?>;
    --text-gray: <?php echo $theme['text_gray']; ?>;
    --footer-bg: <?php echo $theme['footer_bg']; ?>;
    --footer-text: <?php echo $theme['footer_text']; ?>;
    --footer-link: <?php echo $theme['footer_link']; ?>;
    --footer-social-bg: <?php echo $theme['footer_social_bg']; ?>;
    --footer-border: <?php echo $theme['footer_border']; ?>;
    --footer-copyright: <?php echo $theme['footer_copyright']; ?>;
    --radius: <?php echo $theme['radius']; ?>px;
    --font: <?php echo $theme['font']; ?>;
}
</style>
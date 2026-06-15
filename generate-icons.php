<?php
/**
 * Generate PWA Icons - Jalankan SEKALI lalu hapus file ini
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$dir = __DIR__ . '/assets/images/';

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ($sizes as $size) {
    $icon_path = $dir . "icon-{$size}x{$size}.png";
    
    // Create a simple colored square with cake emoji
    $img = imagecreatetruecolor($size, $size);
    
    // Enable alpha
    imagealphablending($img, true);
    imagesavealpha($img, true);
    
    // Background color (#8B4513)
    $bg = imagecolorallocate($img, 139, 69, 19);
    imagefilledrectangle($img, 0, 0, $size, $size, $bg);
    
    // Rounded corners effect
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    
    // White circle in center
    $white = imagecolorallocate($img, 255, 255, 255);
    $center = $size / 2;
    $radius = $size * 0.3;
    imagefilledellipse($img, $center, $center, $radius * 2, $radius * 2, $white);
    
    // Save
    imagepng($img, $icon_path);
    imagedestroy($img);
    
    echo "Created: icon-{$size}x{$size}.png<br>";
}

// Also create maskable version (with padding)
$maskable = $dir . "icon-512x512-maskable.png";
$img = imagecreatetruecolor(512, 512);
imagealphablending($img, true);
imagesavealpha($img, true);

$bg = imagecolorallocate($img, 139, 69, 19);
imagefilledrectangle($img, 0, 0, 512, 512, $bg);

// Safe zone (80% of 512 = 410px, centered)
$safe_bg = imagecolorallocate($img, 255, 248, 240);
imagefilledrectangle($img, 51, 51, 461, 461, $safe_bg);

$white = imagecolorallocate($img, 139, 69, 19);
imagefilledellipse($img, 256, 256, 200, 200, $white);

imagepng($img, $maskable);
imagedestroy($img);

echo "Created: icon-512x512-maskable.png<br>";
echo "<h3>DONE! Semua icon berhasil dibuat.</h3>";
echo "<p style='color:red;'>HAPUS file generate-icons.php ini untuk keamanan!</p>";
?>
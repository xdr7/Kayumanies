<?php
/**
 * .htaccess Generator for Kayumanies
 * JALANKAN SEKALI LALU HAPUS!
 * http://192.168.35.50:7777/kayumanies/generate-htaccess.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_path = __DIR__;
$base_url = 'http://192.168.35.50:7777/kayumanies'; // Ganti sesuai server kamu

echo "<!DOCTYPE html><html><head><title>Generate .htaccess</title>
<style>
body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;line-height:1.6}
h1{color:#ffd700;text-align:center}
.section{background:#16213e;border-radius:10px;padding:15px;margin:10px 0}
.pass{color:#4caf50;font-weight:bold}
.fail{color:#f44336;font-weight:bold}
.warn{color:#ff9800;font-weight:bold}
code{background:#0f3460;padding:2px 8px;border-radius:4px;display:block;white-space:pre-wrap;margin:5px 0;font-size:12px;color:#00ff00}
.btn{display:inline-block;padding:10px 20px;background:#e94560;color:white;text-decoration:none;border-radius:5px;margin:5px}
</style></head><body>
<h1>🔧 .htaccess Generator - Kayumanies</h1>";

// ==========================================
// 1. ROOT .HTACCESS
// ==========================================
$root_htaccess = '# ============================================
# KAYUMANIES - ROOT .htaccess
# Generated: ' . date('Y-m-d H:i:s') . '
# ============================================

# Enable Rewrite Engine
RewriteEngine On
RewriteBase /kayumanies/

# ============================================
# PHP SETTINGS (Development)
# ============================================
<IfModule mod_php7.c>
    php_flag display_errors Off
    php_flag display_startup_errors Off
    php_value error_reporting E_ALL & ~E_DEPRECATED & ~E_STRICT
    php_value upload_max_filesize 20M
    php_value post_max_size 25M
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 256M
</IfModule>

# ============================================
# URL ROUTING
# ============================================

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^([^\.]+)$ $1.php [NC,L]

# Handle API requests
RewriteRule ^api/(.*)$ api/$1 [QSA,L]

# ============================================
# SECURITY HEADERS
# ============================================
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    
    # CORS (Sesuaikan dengan domain production)
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>

# ============================================
# DIRECTORY SETTINGS
# ============================================
DirectoryIndex index.php index.html
Options -Indexes

# ============================================
# CHARACTER SET
# ============================================
AddDefaultCharset UTF-8
AddCharset UTF-8 .php .html .css .js .json

# ============================================
# GZIP COMPRESSION
# ============================================
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript
    AddOutputFilterByType DEFLATE application/javascript application/x-javascript application/json
    AddOutputFilterByType DEFLATE application/xml application/xhtml+xml application/rss+xml
    AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>

# ============================================
# CACHE CONTROL
# ============================================
<FilesMatch "\.(css|js|ico|svg|woff2|ttf|eot)$">
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresDefault "access plus 1 month"
    </IfModule>
    Header set Cache-Control "public, max-age=2592000"
</FilesMatch>

<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
    </IfModule>
    Header set Cache-Control "public, max-age=31536000"
</FilesMatch>

<FilesMatch "\.(php|html|htm)$">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires 0
</FilesMatch>

# ============================================
# BLOCK SENSITIVE FILES
# ============================================
<FilesMatch "\.(env|sql|md|log|lock|bak|zip|tar|gz)$">
    Order deny,allow
    Deny from all
</FilesMatch>

<FilesMatch "^(install\.php|generate-icons\.php|generate-htaccess\.php|setup_db\.php|security-test\.php)$">
    Order deny,allow
    Deny from all
    Allow from 127.0.0.1
    Allow from ::1
    Allow from 192.168.0.0/16
    Allow from 10.0.0.0/8
    Allow from 172.16.0.0/12
</FilesMatch>

# Block access to hidden files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Allow .htaccess itself
<FilesMatch "^\.htaccess$">
    Order allow,deny
    Allow from all
</FilesMatch>

# ============================================
# ERROR PAGES
# ============================================
ErrorDocument 400 /kayumanies/error.php?code=400
ErrorDocument 401 /kayumanies/error.php?code=401
ErrorDocument 403 /kayumanies/error.php?code=403
ErrorDocument 404 /kayumanies/error.php?code=404
ErrorDocument 500 /kayumanies/error.php?code=500

# ============================================
# BLOCK BAD BOTS
# ============================================
<IfModule mod_rewrite.c>
    RewriteCond %{HTTP_USER_AGENT} (badbot|scraper|spider|crawler) [NC,OR]
    RewriteCond %{HTTP_USER_AGENT} (curl|wget|python|perl|ruby|java) [NC]
    RewriteRule .* - [F,L]
</IfModule>

# ============================================
# PREVENT IMAGE HOTLINKING
# ============================================
<IfModule mod_rewrite.c>
    RewriteCond %{HTTP_REFERER} !^$
    RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?192\.168\.35\.50 [NC]
    RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?103\.130\.5\.26 [NC]
    RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?localhost [NC]
    RewriteRule \.(jpg|jpeg|png|gif|webp)$ - [NC,F,L]
</IfModule>';

// ==========================================
// 2. CONFIG .HTACCESS (Proteksi database.php)
// ==========================================
$config_htaccess = '# ============================================
# KAYUMANIES - Config Protection
# ============================================

# Deny all access
Order deny,allow
Deny from all

# Allow only local
Allow from 127.0.0.1
Allow from ::1
Allow from 192.168.0.0/16
Allow from 10.0.0.0/8
Allow from 172.16.0.0/12

# Protect database file
<FilesMatch "database\.php$">
    Order deny,allow
    Deny from all
    Allow from 127.0.0.1
    Allow from ::1
    Allow from localhost
</FilesMatch>

<FilesMatch "installed\.lock$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Disable PHP execution
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>';

// ==========================================
// 3. UPLOADS .HTACCESS (Blokir eksekusi PHP)
// ==========================================
$uploads_htaccess = '# ============================================
# KAYUMANIES - Uploads Protection
# ============================================

# Disable PHP execution
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>

# Allow image files only
<FilesMatch "\.(jpg|jpeg|png|gif|webp|svg|pdf)$">
    Order allow,deny
    Allow from all
</FilesMatch>

# Block all other file types
<FilesMatch "\.(php|php7|phtml|pht|phar|shtml|cgi|pl|py|sh|exe|bat|cmd|dll)$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Remove PHP handler
RemoveHandler .php .php7 .phtml .pht .phar
RemoveType .php .php7 .phtml .pht .phar';

// ==========================================
// 4. ASSETS .HTACCESS
// ==========================================
$assets_htaccess = '# ============================================
# KAYUMANIES - Assets Protection
# ============================================

# Disable PHP execution
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>

# Allow static files
<FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2|ttf|json|xml)$">
    Order allow,deny
    Allow from all
</FilesMatch>

# Block scripts
<FilesMatch "\.(php|php7|phtml|pht|phar)$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

Options -Indexes';

// ==========================================
// 5. API .HTACCESS
// ==========================================
$api_htaccess = '# ============================================
# KAYUMANIES - API Protection
# ============================================

# Only allow POST and GET
<LimitExcept GET POST>
    Order deny,allow
    Deny from all
</LimitExcept>

# Set JSON content type
<IfModule mod_headers.c>
    Header set Content-Type "application/json; charset=utf-8"
</IfModule>

# Prevent directory listing
Options -Indexes';

// ==========================================
// 6. DATABASE .HTACCESS
// ==========================================
$database_htaccess = '# ============================================
# KAYUMANIES - Database Folder Protection
# ============================================

# Deny all
Order deny,allow
Deny from all

# Only allow .sql files for import
<FilesMatch "\.sql$">
    Order allow,deny
    Allow from 127.0.0.1
    Allow from ::1
</FilesMatch>

Options -Indexes';

// ==========================================
// 7. INCLUDES .HTACCESS
// ==========================================
$includes_htaccess = '# ============================================
# KAYUMANIES - Includes Protection
# ============================================

# Deny direct access
Order deny,allow
Deny from all

# Allow only from local
Allow from 127.0.0.1
Allow from ::1

Options -Indexes';

// ==========================================
// 8. LOGS .HTACCESS
// ==========================================
$logs_htaccess = '# ============================================
# KAYUMANIES - Logs Protection
# ============================================

Order deny,allow
Deny from all

Options -Indexes';

// ==========================================
// PROSES PEMBUATAN
// ==========================================
$files_to_create = [
    '/' => $root_htaccess,
    '/config' => $config_htaccess,
    '/assets' => $assets_htaccess,
    '/assets/uploads' => $uploads_htaccess,
    '/assets/uploads/products' => $uploads_htaccess,
    '/assets/uploads/payments' => $uploads_htaccess,
    '/api' => $api_htaccess,
    '/database' => $database_htaccess,
    '/includes' => $includes_htaccess,
    '/logs' => $logs_htaccess
];

echo '<div class="section"><h2>📂 Membuat .htaccess Files</h2>';

foreach ($files_to_create as $path => $content) {
    $full_path = $base_path . $path . '/.htaccess';
    $dir = dirname($full_path);
    
    // Buat folder jika belum ada
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "<p class='warn'>📁 Folder dibuat: {$path}</p>";
    }
    
    // Tulis file
    if (file_put_contents($full_path, $content)) {
        chmod($full_path, 0644);
        echo "<p class='pass'>✅ {$path}/.htaccess - Berhasil dibuat</p>";
        echo "<code>" . substr($content, 0, 200) . "...</code>";
    } else {
        echo "<p class='fail'>❌ {$path}/.htaccess - GAGAL dibuat! Cek permission folder.</p>";
    }
}

echo "</div>";

// ==========================================
// CEK FILE YANG PERLU DIHAPUS
// ==========================================
echo '<div class="section"><h2>⚠️ File yang Perlu Dihapus (Keamanan)</h2>';
$dangerous_files = [
    'install.php',
    'generate-icons.php',
    'generate-htaccess.php',
    'setup_db.php',
    'security-test.php'
];

foreach ($dangerous_files as $file) {
    if (file_exists($base_path . '/' . $file)) {
        echo "<p class='warn'>⚠️ {$file} - MASIH ADA! Hapus setelah digunakan.</p>";
    } else {
        echo "<p class='pass'>✅ {$file} - Sudah dihapus.</p>";
    }
}
echo "</div>";

// ==========================================
// SUMMARY
// ==========================================
echo '<div class="section" style="text-align:center;">';
echo '<h2>🎉 SELESAI!</h2>';
echo '<p>Semua .htaccess berhasil dibuat. File ini melindungi:</p>';
echo '<ul style="text-align:left;display:inline-block;">';
echo '<li>✅ Root - Security headers, URL routing, cache</li>';
echo '<li>✅ Config - Blokir akses database.php</li>';
echo '<li>✅ Uploads - Blokir eksekusi PHP</li>';
echo '<li>✅ Assets - Cache & blokir script</li>';
echo '<li>✅ API - Batasi method HTTP</li>';
echo '<li>✅ Database - Blokir akses folder</li>';
echo '<li>✅ Includes - Blokir akses langsung</li>';
echo '</ul>';
echo "<br><a href='{$base_url}' class='btn'>🏠 Ke Website</a>";
echo "<p style='color:#e94560;margin-top:20px;'>⚠️ HAPUS FILE generate-htaccess.php INI SEKARANG!</p>";
echo '</div>';

echo "</body></html>";
?>
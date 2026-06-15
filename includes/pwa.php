<?php
// Tentukan base path
$pwa_base = $pwa_base ?? '';
?>
<!-- PWA Meta Tags -->
<meta name="theme-color" content="#8B4513">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Kayumanies">
<link rel="apple-touch-icon" href="<?php echo $pwa_base; ?>assets/images/icon-192x192.png">
<link rel="manifest" href="<?php echo $pwa_base; ?>manifest.json">
<?php
$favicon = 'favicon.ico';
try {
    require_once __DIR__ . '/../config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'store_favicon' LIMIT 1");
    $result = $stmt->fetch();
    if ($result) $favicon = $result['setting_value'];
} catch (Exception $e) {}
?>
<link rel="icon" type="image/x-icon" href="<?php echo $pwa_base; ?>assets/images/<?php echo $favicon; ?>">
<link rel="shortcut icon" href="<?php echo $pwa_base; ?>assets/images/<?php echo $favicon; ?>">
<script>
// ==========================================
// PWA - Service Worker + Install Prompt
// ==========================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?php echo $pwa_base; ?>service-worker.js', { scope: '/' })
            .then(function(reg) {
                console.log('PWA registered');
                
                // Update detection
                reg.addEventListener('updatefound', function() {
                    var newWorker = reg.installing;
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            if (confirm('Update tersedia! Muat ulang?')) {
                                window.location.reload();
                            }
                        }
                    });
                });
            })
            .catch(function(err) {
                console.log('PWA failed:', err);
            });
    });
    
    // Auto reload when new SW activates
    var refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', function() {
        if (!refreshing) {
            refreshing = true;
            window.location.reload();
        }
    });
}

// Install Prompt (Add to Home Screen)
var deferredPrompt;
window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    
    var installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.style.display = 'block';
        installBtn.addEventListener('click', function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(result) {
                    deferredPrompt = null;
                });
            }
        });
    }
});

// Detect standalone mode
if (window.matchMedia('(display-mode: standalone)').matches) {
    document.body.classList.add('pwa-mode');
}
</script>
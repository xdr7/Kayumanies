<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Mark as read
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: notifications.php?msg=read');
    exit;
}

// Mark all as read
if (isset($_GET['mark_all'])) {
    $stmt = $db->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    header('Location: notifications.php?msg=all_read');
    exit;
}

// Delete notification
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: notifications.php?msg=deleted');
    exit;
}

// Flash messages
$msg = $_GET['msg'] ?? '';
$flash_msg = '';
$flash_type = '';

if ($msg == 'read') {
    $flash_msg = 'Notifikasi ditandai sudah dibaca!';
    $flash_type = 'success';
} elseif ($msg == 'all_read') {
    $flash_msg = 'Semua notifikasi ditandai sudah dibaca!';
    $flash_type = 'success';
} elseif ($msg == 'deleted') {
    $flash_msg = 'Notifikasi berhasil dihapus!';
    $flash_type = 'warning';
}

// ==========================================
// PAGINATION
// ==========================================
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Count total
$stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Count unread
$stmt = $db->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
$unread_count = $stmt->fetch()['total'];

// Get notifications (paginated)
$stmt = $db->prepare("SELECT n.*, u.full_name as user_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>🔔 Notifikasi</h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Home</a> / Notifikasi
                    <?php if ($unread_count > 0): ?>
                    <span style="color:#f44336;margin-left:10px;">
                        (<span class="unread-dot"></span> <?php echo $unread_count; ?> belum dibaca)
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="top-bar-right">
                <?php if ($unread_count > 0): ?>
                <a href="?mark_all=1" class="btn btn-sm" style="background:#4CAF50;color:white;text-decoration:none;">
                    <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($flash_msg): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <span><?php echo $flash_msg; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-bell"></i> Daftar Notifikasi
                </div>
                <span class="text-sm text-muted">
                    <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> dari <?php echo $total; ?>
                </span>
            </div>
            
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>Tidak Ada Notifikasi</h3>
                    <p>Semua notifikasi akan muncul di sini</p>
                </div>
                <?php else: ?>
                    
                <?php foreach ($notifications as $notif): 
                    $icon_class = 'icon-system';
                    $icon_emoji = '🔔';
                    
                    if ($notif['type'] == 'order') { $icon_class = 'icon-order'; $icon_emoji = '🛒'; }
                    elseif ($notif['type'] == 'payment') { $icon_class = 'icon-payment'; $icon_emoji = '💰'; }
                    elseif ($notif['type'] == 'promo') { $icon_class = 'icon-promo'; $icon_emoji = '🎫'; }
                ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                    <div class="notif-icon <?php echo $icon_class; ?>"><?php echo $icon_emoji; ?></div>
                    
                    <div class="notif-content">
                        <h4>
                            <?php if (!$notif['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </h4>
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <div class="notif-meta">
                            <span><i class="far fa-clock"></i> <?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo strtoupper($notif['type']); ?></span>
                            <?php if ($notif['user_name']): ?>
                            <span><i class="far fa-user"></i> <?php echo htmlspecialchars($notif['user_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="notif-actions">
                        <?php if (!$notif['is_read']): ?>
                        <a href="?mark_read=<?php echo $notif['id']; ?>&page=<?php echo $page; ?>" class="btn-mark-read" title="Tandai sudah dibaca">
                            <i class="fas fa-check"></i> Read
                        </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $notif['id']; ?>&page=<?php echo $page; ?>" class="btn-delete-notif" 
                           onclick="return confirm('Hapus notifikasi ini?');" title="Hapus notifikasi">
                            <i class="fas fa-trash-alt"></i> Hapus
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </main>
    
    <script>
    setTimeout(function() {
        var alert = document.querySelector('.alert');
        if (alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { if (alert.parentNode) alert.parentNode.removeChild(alert); }, 500);
        }
    }, 4000);
    </script>
    
</body>
</html>
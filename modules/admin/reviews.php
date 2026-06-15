<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Handle approve/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $review_id = intval($_POST['review_id']);
    $action = $_POST['action'] ?? '';
    
    if ($action == 'approve') {
        $stmt = $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $msg = 'Review berhasil disetujui!';
    } elseif ($action == 'unapprove') {
        $stmt = $db->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?");
        $stmt->execute([$review_id]);
        $msg = 'Review ditandai pending!';
    } elseif ($action == 'delete') {
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $msg = 'Review berhasil dihapus!';
    }
    
    $_SESSION['flash_msg'] = $msg ?? 'Operasi berhasil!';
    $_SESSION['flash_type'] = $action == 'delete' ? 'warning' : 'success';
    header('Location: reviews.php');
    exit;
}

// Get flash message
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Get reviews
$status = $_GET['status'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($status == 'pending') {
    $where .= " AND r.is_approved = 0";
} elseif ($status == 'approved') {
    $where .= " AND r.is_approved = 1";
}

$sql = "SELECT r.*, u.full_name as user_name, p.name as product_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        JOIN products p ON r.product_id = p.id 
        {$where} 
        ORDER BY r.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Stats
$stmt = $db->query("SELECT COUNT(*) as total FROM reviews");
$total_reviews = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM reviews WHERE is_approved = 0");
$pending_reviews = $stmt->fetch()['total'];

$stmt = $db->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE is_approved = 1");
$avg_rating = round($stmt->fetch()['avg_rating'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Review - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">    
     
	 <!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <main class="admin-main">
        
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>⭐ Manajemen Review</h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Home</a> / Review
                </div>
            </div>
            <div class="top-bar-right">
                <span class="text-sm text-muted">Total: <?php echo $total_reviews; ?> review</span>
            </div>
        </div>
        
        <!-- FLASH MESSAGE -->
        <?php if ($flash_msg): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <span><?php echo htmlspecialchars($flash_msg); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $total_reviews; ?></div>
                    <div class="stat-label">Total Review</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $pending_reviews; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-success">
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">⭐ <?php echo $avg_rating; ?></div>
                    <div class="stat-label">Rata-rata Rating</div>
                </div>
            </div>
        </div>
        
        <!-- FILTER & REVIEW LIST -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-comments"></i> Daftar Review
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="reviews.php" class="filter-tab <?php echo empty($status) ? 'active' : ''; ?>">
                    Semua
                    <span class="count"><?php echo $total_reviews; ?></span>
                </a>
                <a href="?status=pending" class="filter-tab <?php echo $status == 'pending' ? 'active' : ''; ?>">
                    ⏳ Pending
                    <span class="count"><?php echo $pending_reviews; ?></span>
                </a>
                <a href="?status=approved" class="filter-tab <?php echo $status == 'approved' ? 'active' : ''; ?>">
                    ✅ Approved
                    <span class="count"><?php echo $total_reviews - $pending_reviews; ?></span>
                </a>
            </div>
            
            <!-- Reviews -->
            <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>Tidak ada review</h3>
                <p>Belum ada review <?php echo $status == 'pending' ? 'yang perlu disetujui' : ''; ?></p>
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-item">
                <!-- Avatar -->
                <div class="review-avatar">
                    <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                </div>
                
                <!-- Content -->
                <div class="review-content">
                    <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                    
                    <div class="review-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?php echo $i <= $review['rating'] ? 'filled' : 'empty'; ?>">
                                <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                            </span>
                        <?php endfor; ?>
                    </div>
                    
                    <p class="review-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    
                    <div class="review-meta">
                        <span>🛍️ Produk: <strong><?php echo htmlspecialchars($review['product_name']); ?></strong></span>
                        <span>🕐 <?php echo date('d M Y H:i', strtotime($review['created_at'])); ?></span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="review-actions">
                    <?php if ($review['is_approved']): ?>
                    <span class="badge badge-success">
                        <i class="fas fa-check-circle"></i> Approved
                    </span>
                    <?php else: ?>
                    <span class="badge badge-warning">
                        <i class="fas fa-clock"></i> Pending
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!$review['is_approved']): ?>
                    <form method="POST">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-check"></i> Setujui
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="action" value="unapprove">
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-undo"></i> Batalkan
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" onsubmit="return confirm('Hapus review ini? Tindakan ini tidak dapat dibatalkan.');">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </main>
    
    <script>
        // Auto-hide flash message
        setTimeout(function() {
            var alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    </script>
    
</body>
</html>
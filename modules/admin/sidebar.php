<?php
/**
 * Admin Sidebar - Include di semua halaman admin
 * Usage: require_once __DIR__ . '/sidebar.php';
 */

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil stats untuk badge notifikasi
$unread_notif = 0;
$pending_orders = 0;

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    try {
        require_once __DIR__ . '/../../config/database.php';
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        // Unread notifications
        $stmt = $db->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
        $unread_notif = $stmt->fetch()['total'];
        
        // Pending orders
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'");
        $pending_orders = $stmt->fetch()['total'];
    } catch (Exception $e) {
        // Jika database error, biarkan 0
    }
}

// Tentukan halaman aktif berdasarkan URL
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<aside class="admin-sidebar">
    <?php require_once __DIR__ . '/../../includes/brand.php'; ?>

		<div class="sidebar-brand">
			<div class="sidebar-logo">
				<?php if (!empty($brand_logo)): ?>
				<img src="../../assets/images/<?php echo $brand_logo; ?>" alt="<?php echo htmlspecialchars($brand_name); ?>" style="width:100%;height:100%;object-fit:contain;border-radius:10px;">
				<?php else: ?>
				<i class="fas fa-cake"></i>
				<?php endif; ?>
			</div>
			<div class="sidebar-brand-text">
				<h2><?php echo htmlspecialchars($brand_name); ?></h2>
				<small>Admin Panel</small>
			</div>
		</div>
    
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?>
        </div>
        <div class="sidebar-user-info">
            <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></strong>
            <span class="badge badge-<?php echo $_SESSION['role'] ?? 'admin'; ?>">
                <?php echo strtoupper($_SESSION['role'] ?? 'ADMIN'); ?>
            </span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">MAIN MENU</div>
            
            <a href="<?php echo $base_url ?? ''; ?>dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?php echo $base_url ?? ''; ?>orders.php" class="nav-link <?php echo $current_page == 'orders.php' || $current_page == 'order-detail.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Pesanan</span>
                <?php if ($pending_orders > 0): ?>
                <span class="nav-badge"><?php echo $pending_orders; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="<?php echo $base_url ?? ''; ?>products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span>Produk</span>
            </a>
            
            <a href="<?php echo $base_url ?? ''; ?>categories.php" class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-list-alt"></i>
                <span>Kategori</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">MANAGEMENT</div>
			
			<a href="payment-methods.php" class="nav-link <?php echo $current_page == 'payment-methods.php' ? 'active' : ''; ?>">
				<i class="fas fa-credit-card"></i>
				<span>Pembayaran</span>
			</a>
            
            <a href="<?php echo $base_url ?? ''; ?>users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            
            <a href="<?php echo $base_url ?? ''; ?>promos.php" class="nav-link <?php echo $current_page == 'promos.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Promo</span>
            </a>
            
            <a href="<?php echo $base_url ?? ''; ?>reviews.php" class="nav-link <?php echo $current_page == 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Review</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">SYSTEM</div>
			<a href="hpp-calculator.php" class="nav-link <?php echo $current_page == 'hpp-calculator.php' ? 'active' : ''; ?>">
					<i class="fas fa-calculator"></i>
					<span>Kalkulator HPP</span>
				</a>
			<a href="<?php echo $base_url ?? ''; ?>chat.php" class="nav-link" class="nav-link <?php echo $current_page == 'chat.php' ? 'active' : ''; ?>">
				<i class="fas fa-comments"></i><span>Chat</span>
			</a>
            
            <a href="<?php echo $base_url ?? ''; ?>reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Laporan</span>
            </a>
			
            <a href="theme-settings.php" class="nav-link <?php echo $current_page == 'theme-settings.php' ? 'active' : ''; ?>">
				<i class="fas fa-palette"></i>
				<span>Tema Warna</span>
			</a>
			
			<a href="<?php echo $base_url ?? ''; ?>settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Pengaturan</span>
            </a>
            
            <a href="<?php echo $base_url ?? ''; ?>notifications.php" class="nav-link <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i>
                <span>Notifikasi</span>
                <?php if ($unread_notif > 0): ?>
                <span class="nav-badge nav-badge-danger"><?php echo $unread_notif; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">QUICK LINKS</div>
            
            <a href="../../index.php" target="_blank" class="nav-link">
                <i class="fas fa-eye"></i>
                <span>Lihat Website</span>
            </a>
                       
            
			<a href="documentation.php" class="nav-link <?php echo $current_page == 'documentation.php' ? 'active' : ''; ?>">
				<i class="fas fa-book"></i>
				<span>Dokumentasi</span>
			</a>
			
            <a href="../auth/logout.php" class="nav-link nav-link-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>
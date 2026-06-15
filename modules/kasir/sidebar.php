<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pending_payment = 0;
$pending_process = 0;
$ready_pickup = 0;

if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'kasir'])) {
    try {
        require_once __DIR__ . '/../../config/database.php';
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'pending'");
        $pending_payment = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'paid' AND order_status = 'pending'");
        $pending_process = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'ready'");
        $ready_pickup = $stmt->fetch()['total'];
    } catch (Exception $e) {}
}

$current_page = basename($_SERVER['PHP_SELF']);
$total_badge = $pending_payment + $pending_process + $ready_pickup;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<aside class="kasir-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">💳</div>
        <div class="sidebar-brand-text">
            <h2>Kasir</h2>
            <small>Order Processing</small>
        </div>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'K', 0, 1)); ?>
        </div>
        <div class="sidebar-user-info">
            <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Kasir'); ?></strong>
            <span class="badge badge-kasir">KASIR</span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">MENU UTAMA</div>
            
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' && !isset($_GET['payment']) && !isset($_GET['status']) ? 'active' : ''; ?>">
                <i class="fas fa-list-alt"></i>
                <span>Semua Pesanan</span>
                <?php if ($total_badge > 0): ?>
                <span class="nav-badge"><?php echo $total_badge; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="orders.php?payment=pending" class="nav-link <?php echo isset($_GET['payment']) && $_GET['payment'] == 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd"></i>
                <span>Verifikasi Bayar</span>
                <?php if ($pending_payment > 0): ?>
                <span class="nav-badge"><?php echo $pending_payment; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="orders.php?status=ready" class="nav-link <?php echo isset($_GET['status']) && $_GET['status'] == 'ready' ? 'active' : ''; ?>">
                <i class="fas fa-box-open"></i>
                <span>Siap Diambil</span>
                <?php if ($ready_pickup > 0): ?>
                <span class="nav-badge nav-badge-success"><?php echo $ready_pickup; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">STATUS</div>
            <div class="sidebar-stats">
                <div class="sidebar-stat-item">
                    <div class="stat-label">Menunggu Bayar</div>
                    <div class="stat-value" style="color:#ff9800;"><?php echo $pending_payment; ?></div>
                </div>
                <div class="sidebar-stat-item">
                    <div class="stat-label">Perlu Diproses</div>
                    <div class="stat-value" style="color:#2196F3;"><?php echo $pending_process; ?></div>
                </div>
                <div class="sidebar-stat-item">
                    <div class="stat-label">Siap Diambil</div>
                    <div class="stat-value" style="color:#4CAF50;"><?php echo $ready_pickup; ?></div>
                </div>
            </div>
        </div>
        
       <!-- Tambah di nav-section LAINNYA -->
			<div class="nav-section">
				<div class="nav-section-title">LAINNYA</div>
				<a href="chat.php" class="nav-link <?php echo $current_page == 'chat.php' ? 'active' : ''; ?>">
				<i class="fas fa-comments"></i><span>Chat</span></a>
				
				<!-- TAMBAH: Lihat berdasarkan tanggal -->
				<a href="orders.php?date=<?php echo date('Y-m-d'); ?>" class="nav-link">
					<i class="fas fa-calendar-day"></i>
					<span>Pesanan Hari Ini</span>
				</a>
				
				<a href="../../index.php" target="_blank" class="nav-link">
					<i class="fas fa-store"></i>
					<span>Lihat Toko</span>
				</a>
				
				<a href="documentation.php" class="nav-link <?php echo $current_page == 'documentation.php' ? 'active' : ''; ?>">
					<i class="fas fa-question-circle"></i>
					<span>Bantuan</span>
				</a>
				
				<a href="../auth/logout.php" class="nav-link nav-link-danger">
					<i class="fas fa-sign-out-alt"></i>
					<span>Logout</span>
				</a>
			</div>
    </nav>
</aside>
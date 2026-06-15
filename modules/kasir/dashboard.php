<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Filter tanggal (default hari ini)
$filter_date = $_GET['date'] ?? date('Y-m-d');
$is_today = ($filter_date == date('Y-m-d'));

// Statistik berdasarkan filter tanggal
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_pay,
    COUNT(CASE WHEN payment_status = 'paid' AND order_status = 'pending' THEN 1 END) as need_process,
    COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing_count,
    COUNT(CASE WHEN order_status = 'ready' THEN 1 END) as ready_count,
    COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_count,
    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END), 0) as revenue
    FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$filter_date]);
$today = $stmt->fetch();

// Menunggu pembayaran
$stmt = $db->prepare("SELECT o.*, u.full_name as customer, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.payment_status = 'pending' AND DATE(o.created_at) = ? ORDER BY o.created_at ASC LIMIT 5");
$stmt->execute([$filter_date]);
$pending = $stmt->fetchAll();

// Perlu diproses (sudah bayar tapi status masih pending)
$stmt = $db->prepare("SELECT o.*, u.full_name as customer FROM orders o JOIN users u ON o.user_id = u.id WHERE o.payment_status = 'paid' AND o.order_status = 'pending' AND DATE(o.created_at) = ? ORDER BY o.pickup_date ASC LIMIT 5");
$stmt->execute([$filter_date]);
$need = $stmt->fetchAll();

// Sedang diproses
$stmt = $db->prepare("SELECT o.*, u.full_name as customer FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_status = 'processing' AND DATE(o.created_at) = ? ORDER BY o.updated_at ASC LIMIT 5");
$stmt->execute([$filter_date]);
$processing = $stmt->fetchAll();

// Siap diambil
$stmt = $db->prepare("SELECT o.*, u.full_name as customer, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_status = 'ready' AND DATE(o.created_at) = ? ORDER BY o.pickup_date ASC, o.pickup_time ASC LIMIT 5");
$stmt->execute([$filter_date]);
$ready = $stmt->fetchAll();

// Flash message
$flash = $_SESSION['kasir_flash'] ?? '';
$flash_type = $_SESSION['kasir_flash_type'] ?? '';
unset($_SESSION['kasir_flash'], $_SESSION['kasir_flash_type']);

// Format tanggal untuk tampilan
$date_formatted = date('l, d F Y', strtotime($filter_date));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/kasir.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="kasir-main">
        
        <!-- TOP BAR DENGAN FILTER TANGGAL -->
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📊 Dashboard Kasir</h1>
                <div class="date">
                    <?php echo $date_formatted; ?>
                    <?php if (!$is_today): ?>
                    <span style="color:#ff9800;font-size:11px;">(Riwayat)</span>
                    <?php endif; ?>
                </div>
                <!-- Filter Tanggal -->
                <form method="GET" class="d-flex gap-1 align-center" style="margin-top:8px;">
                    <input type="date" name="date" value="<?php echo $filter_date; ?>" 
                           onchange="this.form.submit()" 
                           style="padding:7px 10px;border:2px solid #ddd;border-radius:8px;font-size:13px;font-family:inherit;">
                    <?php if (!$is_today): ?>
                    <a href="dashboard.php" class="btn btn-xs" style="background:#4CAF50;color:white;text-decoration:none;">Hari Ini</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="top-bar-right">
                <span class="text-sm text-gray">👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="orders.php?date=<?php echo $filter_date; ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-list"></i> Lihat Semua
                </a>
            </div>
        </div>
        
        <!-- FLASH MESSAGE -->
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash_type == 'success' ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $flash; ?>
        </div>
        <?php endif; ?>
        
        <!-- STATS ROW -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-orange"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $today['pending_pay']; ?></div>
                    <div class="stat-label">Menunggu Bayar</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i class="fas fa-tasks"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $today['need_process']; ?></div>
                    <div class="stat-label">Perlu Diproses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple"><i class="fas fa-spinner"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $today['processing_count']; ?></div>
                    <div class="stat-label">Sedang Diproses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i class="fas fa-box-open"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $today['ready_count']; ?></div>
                    <div class="stat-label">Siap Diambil</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-gray"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $today['completed_count']; ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <div class="stat-value" style="font-size:16px;">Rp <?php echo number_format($today['revenue'],0,',','.'); ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
        </div>
        
        <!-- 4 KOLOM PESANAN -->
        <div class="detail-grid">
            
            <!-- ========== 1. MENUNGGU PEMBAYARAN ========== -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-hand-holding-usd" style="color:#ff9800;"></i> Verifikasi Pembayaran
                    </div>
                    <span class="badge badge-warning"><?php echo count($pending); ?></span>
                </div>
                <div class="order-list-scroll">
                    <?php if (empty($pending)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color:#4CAF50;"></i>
                        <p>Semua pesanan sudah dibayar</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($pending as $o): ?>
                    <div class="order-mini border-orange" onclick="location.href='order-detail.php?id=<?php echo $o['id']; ?>'">
                        <div class="order-mini-header">
                            <span class="order-num">#<?php echo $o['order_number']; ?></span>
                            <span class="order-time"><?php echo date('H:i', strtotime($o['created_at'])); ?></span>
                        </div>
                        <div class="order-customer">👤 <?php echo htmlspecialchars($o['customer']); ?></div>
                        <?php if ($o['phone']): ?>
                        <div class="order-customer">📞 <?php echo htmlspecialchars($o['phone']); ?></div>
                        <?php endif; ?>
                        <div class="order-meta">
                            <span class="order-total">Rp <?php echo number_format($o['final_amount'],0,',','.'); ?></span>
                            <span class="text-xs text-gray"><?php echo strtoupper($o['payment_method']); ?></span>
                        </div>
                        <form method="POST" action="process.php" style="margin-top:8px;" onclick="event.stopPropagation();">
                            <input type="hidden" name="action" value="confirm_payment">
                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                            <input type="hidden" name="payment_method" value="cash">
                            <button type="submit" class="btn-xs btn-xs-green w-full">💵 Konfirmasi Sudah Bayar</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ========== 2. PERLU DIPROSES ========== -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-tasks" style="color:#2196F3;"></i> Perlu Diproses
                    </div>
                    <span class="badge badge-info"><?php echo count($need); ?></span>
                </div>
                <div class="order-list-scroll">
                    <?php if (empty($need)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color:#4CAF50;"></i>
                        <p>Tidak ada antrian proses</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($need as $o): ?>
                    <div class="order-mini border-blue" onclick="location.href='order-detail.php?id=<?php echo $o['id']; ?>'">
                        <div class="order-mini-header">
                            <span class="order-num">#<?php echo $o['order_number']; ?></span>
                            <span class="order-time"><?php echo htmlspecialchars($o['customer']); ?></span>
                        </div>
                        <div class="order-customer">
                            📅 Pengambilan: <?php echo date('d/m/Y', strtotime($o['pickup_date'])); ?> <?php echo $o['pickup_time']; ?>
                        </div>
                        <div class="order-meta">
                            <span class="order-total">Rp <?php echo number_format($o['final_amount'],0,',','.'); ?></span>
                            <form method="POST" action="process.php" onclick="event.stopPropagation();">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <input type="hidden" name="new_status" value="processing">
                                <button type="submit" class="btn-xs btn-xs-blue">▶ Mulai Proses</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ========== 3. SEDANG DIPROSES ========== -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-spinner" style="color:#9C27B0;"></i> Sedang Diproses
                    </div>
                    <span class="badge badge-purple"><?php echo count($processing); ?></span>
                </div>
                <div class="order-list-scroll">
                    <?php if (empty($processing)): ?>
                    <div class="empty-state">
                        <i class="fas fa-spinner"></i>
                        <p>Belum ada yang diproses</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($processing as $o): ?>
                    <div class="order-mini border-purple" onclick="location.href='order-detail.php?id=<?php echo $o['id']; ?>'">
                        <div class="order-mini-header">
                            <span class="order-num">#<?php echo $o['order_number']; ?></span>
                            <span class="order-time"><?php echo htmlspecialchars($o['customer']); ?></span>
                        </div>
                        <div class="order-meta">
                            <span class="order-total">Rp <?php echo number_format($o['final_amount'],0,',','.'); ?></span>
                            <form method="POST" action="process.php" onclick="event.stopPropagation();">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <input type="hidden" name="new_status" value="ready">
                                <button type="submit" class="btn-xs btn-xs-green">✅ Siap Diambil</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ========== 4. SIAP DIAMBIL ========== -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-box-open" style="color:#4CAF50;"></i> Siap Diambil
                    </div>
                    <span class="badge badge-success"><?php echo count($ready); ?></span>
                </div>
                <div class="order-list-scroll">
                    <?php if (empty($ready)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>Tidak ada pesanan siap diambil</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($ready as $o): ?>
                    <div class="order-mini border-green" onclick="location.href='order-detail.php?id=<?php echo $o['id']; ?>'">
                        <div class="order-mini-header">
                            <span class="order-num">#<?php echo $o['order_number']; ?></span>
                            <span class="order-time">📞 <?php echo htmlspecialchars($o['phone'] ?? '-'); ?></span>
                        </div>
                        <div class="order-customer">👤 <?php echo htmlspecialchars($o['customer']); ?></div>
                        <div class="order-customer">📅 <?php echo date('d/m/Y', strtotime($o['pickup_date'])); ?> <?php echo $o['pickup_time']; ?></div>
                        <div class="order-meta">
                            <span class="order-total">Rp <?php echo number_format($o['final_amount'],0,',','.'); ?></span>
                            <form method="POST" action="process.php" onclick="event.stopPropagation();">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <input type="hidden" name="new_status" value="completed">
                                <button type="submit" class="btn-xs btn-xs-orange">🏁 Selesaikan</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
    </main>
    
    <script>
        // Auto refresh setiap 60 detik (hanya jika hari ini)
        <?php if ($is_today): ?>
        setInterval(function(){ location.reload(); }, 60000);
        <?php endif; ?>
    </script>
</body>
</html>
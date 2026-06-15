<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Filter
$status_filter = $_GET['status'] ?? '';
$order_id = $_GET['id'] ?? '';

// Build query
$where = "WHERE o.user_id = ?";
$params = [$_SESSION['user_id']];

if (!empty($status_filter)) {
    $where .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

if (!empty($order_id)) {
    $where .= " AND o.id = ?";
    $params[] = $order_id;
}

// Get orders
$stmt = $db->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id {$where} ORDER BY o.created_at DESC LIMIT 50");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; ?>
    <?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main style="padding-top:100px;padding-bottom:60px;min-height:100vh;">
        <div class="container">
            
            <!-- Header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:22px;">📦 Pesanan Saya</h1>
                    <p style="color:#666;font-size:13px;">Total: <?php echo count($orders); ?> pesanan</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="orders.php" style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:12px;font-weight:600;background:<?php echo empty($status_filter) ? '#8B4513' : '#f0f0f0'; ?>;color:<?php echo empty($status_filter) ? 'white' : '#666'; ?>;">Semua</a>
                    <a href="orders.php?status=pending" style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:12px;font-weight:600;background:<?php echo $status_filter == 'pending' ? '#ff9800' : '#f0f0f0'; ?>;color:<?php echo $status_filter == 'pending' ? 'white' : '#666'; ?>;">Pending</a>
                    <a href="orders.php?status=processing" style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:12px;font-weight:600;background:<?php echo $status_filter == 'processing' ? '#2196F3' : '#f0f0f0'; ?>;color:<?php echo $status_filter == 'processing' ? 'white' : '#666'; ?>;">Diproses</a>
                    <a href="orders.php?status=ready" style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:12px;font-weight:600;background:<?php echo $status_filter == 'ready' ? '#4CAF50' : '#f0f0f0'; ?>;color:<?php echo $status_filter == 'ready' ? 'white' : '#666'; ?>;">Siap Ambil</a>
                    <a href="orders.php?status=completed" style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:12px;font-weight:600;background:<?php echo $status_filter == 'completed' ? '#607D8B' : '#f0f0f0'; ?>;color:<?php echo $status_filter == 'completed' ? 'white' : '#666'; ?>;">Selesai</a>
                </div>
            </div>
            
            <?php if (empty($orders)): ?>
            <div style="background:white;padding:60px 20px;border-radius:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                <i class="fas fa-box-open" style="font-size:60px;color:#ddd;display:block;margin-bottom:15px;"></i>
                <h3>Belum Ada Pesanan</h3>
                <p style="color:#666;">Yuk, pesan kue favoritmu sekarang!</p>
                <a href="products.php" style="display:inline-block;margin-top:12px;padding:10px 25px;background:#8B4513;color:white;text-decoration:none;border-radius:8px;font-weight:600;">Lihat Produk</a>
            </div>
            <?php else: ?>
            
            <?php foreach ($orders as $order): 
                $status_colors = [
                    'pending' => ['bg' => '#FFF3E0', 'color' => '#E65100'],
                    'processing' => ['bg' => '#E3F2FD', 'color' => '#1565C0'],
                    'ready' => ['bg' => '#E8F5E9', 'color' => '#2E7D32'],
                    'completed' => ['bg' => '#ECEFF1', 'color' => '#455A64'],
                    'cancelled' => ['bg' => '#FFEBEE', 'color' => '#C62828']
                ];
                $sc = $status_colors[$order['order_status']] ?? ['bg' => '#f0f0f0', 'color' => '#666'];
                $pay_color = $order['payment_status'] == 'paid' ? ['bg' => '#E8F5E9', 'color' => '#2E7D32'] : ['bg' => '#FFF3E0', 'color' => '#E65100'];
            ?>
            <div style="background:white;border-radius:14px;padding:18px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                
                <!-- Header -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #eee;">
                    <div>
                        <strong style="font-size:16px;">#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                        <br><small style="color:#999;"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></small>
                    </div>
                    <span style="padding:4px 12px;border-radius:15px;font-size:11px;font-weight:700;background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
                        <?php echo strtoupper($order['order_status']); ?>
                    </span>
                </div>
                
                <!-- Info -->
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                    <div>
                        <span style="padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700;background:<?php echo $pay_color['bg']; ?>;color:<?php echo $pay_color['color']; ?>;">
                            <?php echo strtoupper($order['payment_status']); ?>
                        </span>
                        <span style="margin-left:10px;font-size:12px;color:#666;">
                            <?php echo strtoupper($order['payment_method']); ?>
                        </span>
                        <?php if ($order['pickup_date']): ?>
                        <span style="margin-left:10px;font-size:12px;color:#666;">
                            📅 <?php echo date('d M', strtotime($order['pickup_date'])); ?> <?php echo $order['pickup_time']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <strong style="font-size:18px;color:#8B4513;">Rp <?php echo number_format($order['final_amount'], 0, ',', '.'); ?></strong>
                </div>
                
                <!-- Action -->
                <div style="margin-top:12px;display:flex;gap:8px;">
                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" style="padding:6px 15px;background:#8B4513;color:white;text-decoration:none;border-radius:6px;font-size:12px;font-weight:600;">Lihat Detail</a>
                    <?php if ($order['order_status'] == 'ready'): ?>
                    <span style="padding:6px 15px;background:#4CAF50;color:white;border-radius:6px;font-size:12px;font-weight:600;">🎉 Siap Diambil!</span>
                    <?php endif; ?>
                </div>
                
            </div>
            <?php endforeach; ?>
            
            <?php endif; ?>
            
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Kayumanies Cake Shop. Made with ❤️</p>
            </div>
        </div>
    </footer>
    
</body>
</html>
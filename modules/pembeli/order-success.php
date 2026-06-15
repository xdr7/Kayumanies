<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();
$order_id = intval($_GET['id'] ?? 0);

// Get order
$stmt = $db->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_details WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; ?>
    <?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main style="padding-top:100px;padding-bottom:60px;min-height:100vh;">
        <div class="container">
            <div style="max-width:550px;margin:0 auto;background:white;border-radius:20px;padding:35px 25px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
                
                <div style="font-size:60px;color:#4CAF50;margin-bottom:10px;">✅</div>
                <h1 style="color:#2E7D32;font-size:22px;margin-bottom:5px;">Pesanan Berhasil!</h1>
                <p style="color:#666;">Terima kasih telah berbelanja di Kayumanies</p>
                
                <div style="display:inline-block;font-size:18px;font-weight:900;color:#8B4513;background:#FFF3E0;padding:8px 18px;border-radius:10px;margin:15px 0;">
                    #<?php echo $order['order_number']; ?>
                </div>
                
                <div style="text-align:left;background:#fafafa;padding:18px;border-radius:12px;margin:15px 0;">
                    <?php foreach ($items as $item): ?>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px;">
                        <span><?php echo htmlspecialchars($item['product_name']); ?> (<?php echo $item['quantity']; ?>x)</span>
                        <span>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px;color:#f44336;">
                        <span>Diskon</span>
                        <span>-Rp <?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div style="display:flex;justify-content:space-between;padding-top:10px;margin-top:8px;border-top:2px solid #eee;font-weight:700;font-size:16px;color:#8B4513;">
                        <span>Total</span>
                        <span>Rp <?php echo number_format($order['final_amount'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <div style="background:#FFF8E1;padding:12px;border-radius:8px;font-size:12px;text-align:left;margin:15px 0;border-left:4px solid #FFD700;">
                    <strong>📋 Info Pengambilan:</strong><br>
                    📅 <?php echo date('d M Y', strtotime($order['pickup_date'])); ?> pukul <?php echo $order['pickup_time']; ?><br>
                    📍 Jl. Kayu Manis No. 123, Jakarta<br>
                    <?php if ($order['payment_method'] != 'cash'): ?>
                    💳 Pembayaran: <?php echo strtoupper($order['payment_method']); ?> - Tunjukkan bukti saat ambil
                    <?php else: ?>
                    💵 Pembayaran: Tunai saat pengambilan
                    <?php endif; ?>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:20px;">
                    <a href="orders.php" style="display:block;padding:12px;background:#8B4513;color:white;text-decoration:none;border-radius:10px;font-weight:600;font-size:14px;">📋 Pesanan Saya</a>
                    <a href="products.php" style="display:block;padding:12px;background:white;color:#8B4513;text-decoration:none;border:2px solid #8B4513;border-radius:10px;font-weight:600;font-size:14px;">🛍️ Belanja Lagi</a>
                </div>
                
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container"><div class="footer-bottom"><p>&copy; <?php echo date('Y'); ?> Kayumanies.</p></div></div>
    </footer>
</body>
</html>
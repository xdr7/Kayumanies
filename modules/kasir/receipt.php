<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();
$order_id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT o.*, u.full_name as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) { header('Location: orders.php'); exit; }

$stmt = $db->prepare("SELECT * FROM order_details WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?php echo $order['order_number']; ?></title>
    <link rel="stylesheet" href="../../assets/css/kasir.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <main class="kasir-main" style="margin-left:0;width:100%;">
        <div class="top-bar">
            <div class="top-bar-left"><h1>🧾 Struk Pembayaran</h1></div>
            <div class="top-bar-right">
                <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">Kembali</a>
            </div>
        </div>
        
        <div class="receipt-wrapper">
            <div class="receipt">
                <div class="receipt-header">
                    <h3>🧁 Kayumanies Cake Shop</h3>
                    <p>Jl. Kayu Manis No. 123</p>
                    <p style="margin-top:8px;">
                        <strong>#<?php echo $order['order_number']; ?></strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
                        Kasir: <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </p>
                </div>
                
                <div style="margin-bottom:10px;">
                    <strong>Pelanggan:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                    <strong>Status:</strong> <?php echo strtoupper($order['payment_status']); ?>
                </div>
                
                <div class="receipt-divider"></div>
                
                <?php foreach ($items as $item): ?>
                <div class="receipt-item">
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span><?php echo $item['quantity']; ?>x</span>
                    <span>Rp <?php echo number_format($item['subtotal'],0,',','.'); ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="receipt-divider"></div>
                
                <div class="receipt-item"><span>Subtotal</span><span>Rp <?php echo number_format($order['total_amount'],0,',','.'); ?></span></div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="receipt-item"><span>Diskon</span><span>-Rp <?php echo number_format($order['discount_amount'],0,',','.'); ?></span></div>
                <?php endif; ?>
                <div class="receipt-total">TOTAL: Rp <?php echo number_format($order['final_amount'],0,',','.'); ?></div>
                <div class="receipt-item" style="margin-top:5px;"><span>Metode</span><span><?php echo strtoupper($order['payment_method']); ?></span></div>
                
                <div class="receipt-footer">
                    <p>Terima kasih telah berbelanja!</p>
                    <p>Barang yang sudah dibeli tidak dapat dikembalikan</p>
                </div>
            </div>
        </div>
    </main>
    
    <script>window.onload=function(){window.print();};</script>
</body>
</html>
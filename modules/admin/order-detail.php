<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();
$order_id = intval($_GET['id'] ?? 0);

// Get order
$stmt = $db->prepare("SELECT o.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) { header('Location: orders.php'); exit; }

// Get order items
$stmt = $db->prepare("SELECT * FROM order_details WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Get payment
$stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order['order_number']; ?> - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📄 Detail Pesanan #<?php echo $order['order_number']; ?></h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Home</a> / <a href="orders.php">Pesanan</a> / Detail
                </div>
            </div>
            <div class="top-bar-right">
                <a href="orders.php" class="btn btn-sm btn-outline-primary">← Kembali</a>
            </div>
        </div>
        
        <div class="content-grid content-grid-2">
            
            <!-- INFO PESANAN -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-info-circle"></i> Informasi Pesanan</div>
                </div>
                <div class="info-row"><span class="label">Status</span><span class="value"><span class="badge badge-<?php echo $order['order_status']; ?>"><?php echo strtoupper($order['order_status']); ?></span></span></div>
                <div class="info-row"><span class="label">Pembayaran</span><span class="value"><span class="badge <?php echo $order['payment_status']=='paid'?'badge-success':'badge-warning'; ?>"><?php echo strtoupper($order['payment_status']); ?></span></span></div>
                <div class="info-row"><span class="label">Metode</span><span class="value"><?php echo strtoupper($order['payment_method']); ?></span></div>
                <div class="info-row"><span class="label">Tanggal Pesan</span><span class="value"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></span></div>
                <div class="info-row"><span class="label">Pengambilan</span><span class="value"><?php echo date('d M Y', strtotime($order['pickup_date'])); ?> - <?php echo $order['pickup_time']; ?></span></div>
                <?php if ($order['promo_code']): ?>
                <div class="info-row"><span class="label">Promo</span><span class="value text-warning"><?php echo $order['promo_code']; ?></span></div>
                <?php endif; ?>
                <?php if ($order['notes']): ?>
                <div class="info-row"><span class="label">Catatan</span><span class="value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span></div>
                <?php endif; ?>
            </div>
            
            <!-- DATA PELANGGAN -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-user"></i> Data Pelanggan</div>
                </div>
                <div class="info-row"><span class="label">Nama</span><span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
                <div class="info-row"><span class="label">Telepon</span><span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span></div>
                <?php if ($order['user_email']): ?>
                <div class="info-row"><span class="label">Email</span><span class="value"><?php echo htmlspecialchars($order['user_email']); ?></span></div>
                <?php endif; ?>
                <?php if ($payment && $payment['verified_at']): ?>
                <div class="info-row"><span class="label">Verifikasi</span><span class="value"><?php echo date('d/m/Y H:i', strtotime($payment['verified_at'])); ?></span></div>
                <?php endif; ?>
                
                <!-- Bukti Pembayaran -->
                <?php if ($payment && $payment['payment_proof']): ?>
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #ddd;">
                    <span class="text-sm text-gray">📸 Bukti Pembayaran</span><br>
                    <img src="../../assets/uploads/payments/<?php echo $payment['payment_proof']; ?>" 
                         style="width:100px;height:100px;object-fit:cover;border-radius:6px;cursor:pointer;margin-top:5px;border:1px solid #ddd;" 
                         onclick="openImageModal('../../assets/uploads/payments/<?php echo $payment['payment_proof']; ?>')"
                         onerror="this.style.display='none';">
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ITEM PESANAN -->
            <div class="card" style="grid-column: 1 / -1;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-boxes"></i> Item Pesanan</div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <tr><td colspan="3" class="text-right text-danger">Diskon</td><td class="text-danger">-Rp <?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></td></tr>
                            <?php endif; ?>
                            <tr><td colspan="3" style="font-size:18px;font-weight:900;text-align:right;">Total</td><td style="font-size:18px;font-weight:900;color:#8B4513;">Rp <?php echo number_format($order['final_amount'], 0, ',', '.'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            
        </div>
        
    </main>
    
    <!-- MODAL GAMBAR -->
    <div class="image-modal" id="imageModal" onclick="this.classList.remove('show')">
        <img id="modalImage" src="" alt="Bukti Pembayaran">
    </div>
    
    <script>
    function openImageModal(src) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModal').classList.add('show');
    }
    </script>
</body>
</html>
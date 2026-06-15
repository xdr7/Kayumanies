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

$stmt = $db->prepare("SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) { header('Location: orders.php'); exit; }

$stmt = $db->prepare("SELECT * FROM order_details WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

$flash = $_SESSION['kasir_flash'] ?? '';
$flash_type = $_SESSION['kasir_flash_type'] ?? '';
unset($_SESSION['kasir_flash'], $_SESSION['kasir_flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail #<?php echo $order['order_number']; ?> - Kasir</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/kasir.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="kasir-main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📄 Detail #<?php echo $order['order_number']; ?></h1>
                <div class="date"><?php echo date('d F Y H:i', strtotime($order['created_at'])); ?></div>
            </div>
            <div class="top-bar-right">
                <a href="orders.php" class="btn btn-sm btn-outline">← Kembali</a>
                <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-print"></i> Struk</a>
            </div>
        </div>
        
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash_type == 'success' ? 'success' : 'error'; ?>"><?php echo $flash; ?></div>
        <?php endif; ?>
        
        <div class="detail-grid">
            
            <!-- INFO PESANAN -->
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Informasi Pesanan</div></div>
                <div class="info-row"><span class="label">Status</span><span class="value"><span class="status-dot dot-<?php echo ['pending'=>'warning','processing'=>'purple','ready'=>'success','completed'=>'gray','cancelled'=>'red'][$order['order_status']]; ?>"></span><?php echo strtoupper($order['order_status']); ?></span></div>
                <div class="info-row"><span class="label">Pembayaran</span><span class="value"><span class="badge <?php echo $order['payment_status']=='paid'?'badge-success':'badge-warning'; ?>"><?php echo $order['payment_status']=='paid'?'Lunas':'Belum Bayar'; ?></span></span></div>
                <div class="info-row"><span class="label">Metode</span><span class="value"><?php echo strtoupper($order['payment_method']); ?></span></div>
                <div class="info-row"><span class="label">Pengambilan</span><span class="value"><?php echo date('d/m/Y', strtotime($order['pickup_date'])); ?> <?php echo $order['pickup_time']; ?></span></div>
                <?php if ($order['promo_code']): ?>
                <div class="info-row"><span class="label">Promo</span><span class="value text-warning"><?php echo $order['promo_code']; ?></span></div>
                <?php endif; ?>
                <?php if ($order['notes']): ?>
                <div class="info-row"><span class="label">Catatan</span><span class="value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span></div>
                <?php endif; ?>
            </div>
            
            <!-- DATA PELANGGAN + BUKTI -->
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Pelanggan</div></div>
                <div class="info-row"><span class="label">Nama</span><span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
                <div class="info-row"><span class="label">Telepon</span><span class="value"><?php echo htmlspecialchars($order['customer_phone'] ?? '-'); ?></span></div>
                <div class="info-row"><span class="label">Email</span><span class="value"><?php echo htmlspecialchars($order['customer_email'] ?? '-'); ?></span></div>
                <?php if ($payment && $payment['verified_at']): ?>
                <div class="info-row"><span class="label">Verifikasi</span><span class="value"><?php echo date('d/m/Y H:i', strtotime($payment['verified_at'])); ?></span></div>
                <?php endif; ?>
                
                <!-- Bukti Pembayaran (thumbnail kecil) -->
                <?php if ($payment && $payment['payment_proof']): ?>
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #ddd;">
                    <span style="font-size:11px;color:#999;">📸 Bukti Pembayaran</span><br>
                    <img src="../../assets/uploads/payments/<?php echo $payment['payment_proof']; ?>" 
                         style="width:100px;height:100px;object-fit:cover;border-radius:6px;cursor:pointer;margin-top:5px;border:1px solid #ddd;" 
                         onclick="openImageModal('../../assets/uploads/payments/<?php echo $payment['payment_proof']; ?>')"
                         onerror="this.style.display='none';">
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ITEMS -->
            <div class="card full-width">
                <div class="card-header"><div class="card-title"><i class="fas fa-boxes"></i> Item Pesanan</div></div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>Rp <?php echo number_format($item['price'],0,',','.'); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>Rp <?php echo number_format($item['subtotal'],0,',','.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <tr><td colspan="3" class="text-right text-danger">Diskon</td><td class="text-danger">-Rp <?php echo number_format($order['discount_amount'],0,',','.'); ?></td></tr>
                            <?php endif; ?>
                            <tr><td colspan="3" class="total-display">Total</td><td class="total-display">Rp <?php echo number_format($order['final_amount'],0,',','.'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- AKSI -->
            <?php if (!in_array($order['order_status'], ['completed', 'cancelled'])): ?>
            <div class="card full-width">
                <div class="card-header"><div class="card-title"><i class="fas fa-cogs"></i> Aksi Kasir</div></div>
                <div class="action-bar">
                    <?php if ($order['payment_status'] == 'pending'): ?>
                    <form method="POST" action="process.php">
                        <input type="hidden" name="action" value="confirm_payment">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="action-btn action-btn-success">💵 Konfirmasi Pembayaran</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($order['order_status'] == 'pending' && $order['payment_status'] == 'paid'): ?>
                    <form method="POST" action="process.php">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="processing">
                        <button type="submit" class="action-btn action-btn-info">▶ Mulai Proses</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($order['order_status'] == 'processing'): ?>
                    <form method="POST" action="process.php">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="ready">
                        <button type="submit" class="action-btn action-btn-success">✅ Siap Diambil</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($order['order_status'] == 'ready'): ?>
                    <form method="POST" action="process.php">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="completed">
                        <button type="submit" class="action-btn action-btn-warning">🏁 Selesaikan</button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" action="process.php" onsubmit="return confirm('Batalkan pesanan ini?')">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="new_status" value="cancelled">
                        <button type="submit" class="action-btn action-btn-danger">❌ Batalkan</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
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
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

// Get order (HANYA milik user ini)
$stmt = $db->prepare("SELECT o.*, u.full_name, u.phone, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
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

// Get payment info
$stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order['order_number']; ?> - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; ?>
    <?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main style="padding-top:100px;padding-bottom:60px;min-height:100vh;">
        <div class="container">
            
            <!-- Back -->
            <a href="orders.php" style="color:#8B4513;text-decoration:none;font-weight:600;display:inline-block;margin-bottom:15px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
            </a>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                
                <!-- INFO PESANAN -->
                <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <h3 style="margin-bottom:15px;color:#8B4513;">📄 Informasi Pesanan</h3>
                    
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Nomor Pesanan</span>
                        <strong>#<?php echo $order['order_number']; ?></strong>
                    </div>
                    
                    <?php
                    $status_colors = [
                        'pending' => ['bg' => '#FFF3E0', 'color' => '#E65100'],
                        'processing' => ['bg' => '#E3F2FD', 'color' => '#1565C0'],
                        'ready' => ['bg' => '#E8F5E9', 'color' => '#2E7D32'],
                        'completed' => ['bg' => '#ECEFF1', 'color' => '#455A64'],
                        'cancelled' => ['bg' => '#FFEBEE', 'color' => '#C62828']
                    ];
                    $sc = $status_colors[$order['order_status']] ?? ['bg' => '#f0f0f0', 'color' => '#666'];
                    ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Status</span>
                        <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
                            <?php echo strtoupper($order['order_status']); ?>
                        </span>
                    </div>
                    
                    <?php $pc = $order['payment_status'] == 'paid' ? ['bg' => '#E8F5E9', 'color' => '#2E7D32'] : ['bg' => '#FFF3E0', 'color' => '#E65100']; ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Pembayaran</span>
                        <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;background:<?php echo $pc['bg']; ?>;color:<?php echo $pc['color']; ?>;">
                            <?php echo $order['payment_status'] == 'paid' ? 'LUNAS' : 'BELUM BAYAR'; ?>
                        </span>
                    </div>
                    
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Metode</span>
                        <strong><?php echo strtoupper($order['payment_method']); ?></strong>
                    </div>
                    
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Tanggal Pesan</span>
                        <span><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Pengambilan</span>
                        <strong><?php echo date('d M Y', strtotime($order['pickup_date'])); ?> pukul <?php echo $order['pickup_time']; ?></strong>
                    </div>
                    
                    <?php if ($order['promo_code']): ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Kode Promo</span>
                        <span style="color:#ff9800;font-weight:700;"><?php echo $order['promo_code']; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order['notes']): ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                        <span style="color:#666;">Catatan</span>
                        <span><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- DATA PELANGGAN -->
                <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <h3 style="margin-bottom:15px;color:#8B4513;">👤 Data Pemesan</h3>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Nama</span><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">
                        <span style="color:#666;">Telepon</span><strong><?php echo htmlspecialchars($order['customer_phone']); ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                        <span style="color:#666;">Email</span><span><?php echo htmlspecialchars($order['email'] ?? '-'); ?></span>
                    </div>
                    
                    <?php if ($payment): ?>
                    <h3 style="margin-top:20px;margin-bottom:10px;color:#8B4513;">💳 Verifikasi Pembayaran</h3>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                        <span style="color:#666;">Status</span>
                        <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;background:<?php echo $payment['payment_status'] == 'verified' ? '#E8F5E9' : '#FFF3E0'; ?>;color:<?php echo $payment['payment_status'] == 'verified' ? '#2E7D32' : '#E65100'; ?>;">
                            <?php echo strtoupper($payment['payment_status']); ?>
                        </span>
                    </div>
                    <?php if ($payment['verified_at']): ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                        <span style="color:#666;">Diverifikasi</span><span><?php echo date('d M Y H:i', strtotime($payment['verified_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- ITEM PESANAN -->
                <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);grid-column:1/-1;">
                    <h3 style="margin-bottom:15px;color:#8B4513;">🛍️ Item Pesanan</h3>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#fafafa;">
                                    <th style="padding:10px;text-align:left;font-size:12px;color:#666;">Produk</th>
                                    <th style="padding:10px;text-align:right;font-size:12px;color:#666;">Harga</th>
                                    <th style="padding:10px;text-align:center;font-size:12px;color:#666;">Qty</th>
                                    <th style="padding:10px;text-align:right;font-size:12px;color:#666;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="padding:10px;font-size:13px;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td style="padding:10px;text-align:right;font-size:13px;">Rp <?php echo number_format($item['price'],0,',','.'); ?></td>
                                    <td style="padding:10px;text-align:center;font-size:13px;"><?php echo $item['quantity']; ?></td>
                                    <td style="padding:10px;text-align:right;font-size:13px;">Rp <?php echo number_format($item['subtotal'],0,',','.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if ($order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" style="padding:10px;text-align:right;color:#f44336;font-size:13px;">Diskon</td>
                                    <td style="padding:10px;text-align:right;color:#f44336;font-size:13px;">-Rp <?php echo number_format($order['discount_amount'],0,',','.'); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <tr>
                                    <td colspan="3" style="padding:10px;text-align:right;font-weight:700;font-size:15px;">Total</td>
                                    <td style="padding:10px;text-align:right;font-weight:700;font-size:16px;color:#8B4513;">Rp <?php echo number_format($order['final_amount'],0,',','.'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>            
											
            </div>
            
        </div>
    </main>
    
    <footer class="footer">
        <div class="container"><div class="footer-bottom"><p>&copy; <?php echo date('Y'); ?> Kayumanies.</p></div></div>
    </footer>
    
</body>
</html>
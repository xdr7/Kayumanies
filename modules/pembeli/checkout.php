<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) { header('Location: cart.php'); exit; }

$subtotal = 0;
foreach ($cart_items as $item) {
    $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}
$tax = $subtotal * 0.1;
$total = $subtotal + $tax;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        $order_number = 'KYM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $pickup_date = trim($_POST['pickup_date']);
        $pickup_time = trim($_POST['pickup_time']);
        $payment_type = trim($_POST['payment_method']);
        $notes = trim($_POST['notes']);
        $promo_code = trim($_POST['promo_code']);
        
        if (empty($customer_name) || empty($customer_phone) || empty($pickup_date) || empty($pickup_time)) {
            throw new Exception("Semua field wajib diisi!");
        }
        
        $payment_map = ['bank' => 'transfer', 'qris' => 'qris', 'cash' => 'cash', 'ewallet' => 'transfer'];
        $payment_method = $payment_map[$payment_type] ?? 'cash';
        
        $discount = 0;
        if (!empty($promo_code)) {
            $stmt = $db->prepare("SELECT * FROM promos WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
            $stmt->execute([$promo_code]);
            $promo = $stmt->fetch();
            if ($promo && $subtotal >= $promo['min_purchase']) {
                $discount = $promo['discount_type'] == 'percentage' ? $subtotal * ($promo['discount_value'] / 100) : $promo['discount_value'];
                $stmt = $db->prepare("UPDATE promos SET usage_count = usage_count + 1 WHERE id = ?");
                $stmt->execute([$promo['id']]);
            }
        }
        
        $final_amount = max(0, $total - $discount);
        
        $stmt = $db->prepare("INSERT INTO orders (order_number, user_id, total_amount, discount_amount, final_amount, payment_method, payment_status, order_status, pickup_date, pickup_time, notes, customer_name, customer_phone, promo_code) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_number, $_SESSION['user_id'], $total, $discount, $final_amount, $payment_method, $pickup_date, $pickup_time, $notes, $customer_name, $customer_phone, $promo_code]);
        $order_id = $db->lastInsertId();
        
        foreach ($cart_items as $item) {
            $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
            $stmt = $db->prepare("INSERT INTO order_details (order_id, product_id, product_name, price, quantity, subtotal, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['name'], $price, $item['quantity'], $price * $item['quantity'], $item['notes']]);
        }
        
        if ($payment_type != 'cash' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
            $upload_dir = __DIR__ . '/../../assets/uploads/payments/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file = $_FILES['payment_proof'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif']) && $file['size'] <= 5000000) {
                $filename = 'proof-' . $order_id . '-' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $upload_dir . $filename);
                $stmt = $db->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_proof, payment_status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$order_id, $final_amount, $payment_method, $filename]);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $msg = "Pesanan #{$order_number} berhasil. Total: Rp " . number_format($final_amount, 0, ',', '.');
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, '✅ Pesanan Berhasil', ?, 'order', ?)");
        $stmt->execute([$_SESSION['user_id'], $msg, "orders.php?id={$order_id}"]);
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, '🛒 Pesanan Baru', ?, 'order', ?)");
        $stmt->execute([1, "#{$order_number} dari {$customer_name}", "../admin/orders.php"]);
        
        $db->commit();
        header("Location: order-success.php?id={$order_id}");
        exit;
    } catch (Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}

$min_date = date('Y-m-d', strtotime('+1 day'));
$stmt = $db->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC");
$payment_methods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main class="checkout-page">
        <div class="container">
            
            <div class="checkout-header">
                <h1>📋 Checkout</h1>
                <a href="cart.php"><i class="fas fa-arrow-left"></i> Kembali ke Keranjang</a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="checkoutForm" enctype="multipart/form-data">
                <div class="checkout-grid">
                    
                    <!-- FORM KIRI -->
                    <div class="checkout-form-card">
                        
                        <!-- Data Pemesan -->
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-user"></i> Data Pemesan</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nama Lengkap <span class="required">*</span></label>
                                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>No. Telepon <span class="required">*</span></label>
                                    <input type="tel" name="customer_phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Waktu Pengambilan -->
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-calendar-alt"></i> Waktu Pengambilan</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Tanggal <span class="required">*</span></label>
                                    <input type="date" name="pickup_date" required min="<?php echo $min_date; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Jam <span class="required">*</span></label>
                                    <select name="pickup_time" required>
                                        <option value="">Pilih Jam</option>
                                        <?php for ($h = 8; $h <= 20; $h++): 
                                            $t = sprintf('%02d:00', $h);
                                            echo "<option value=\"{$t}\">{$t}</option>";
                                            if ($h < 20) { $t30 = sprintf('%02d:30', $h); echo "<option value=\"{$t30}\">{$t30}</option>"; }
                                        endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Metode Pembayaran -->
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-credit-card"></i> Metode Pembayaran</div>
                            
                            <div class="payment-options">
                                <?php foreach ($payment_methods as $pm): ?>
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" value="<?php echo $pm['type']; ?>" id="pay-<?php echo $pm['id']; ?>" <?php echo $pm['type'] == 'cash' ? 'checked' : ''; ?>>
                                    <label for="pay-<?php echo $pm['id']; ?>">
                                        <i class="fas fa-<?php echo $pm['type']=='bank'?'university':($pm['type']=='qris'?'qrcode':($pm['type']=='cash'?'money-bill-wave':'wallet')); ?>"></i>
                                        <?php echo htmlspecialchars($pm['name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Detail Pembayaran -->
                            <?php foreach ($payment_methods as $pm): ?>
                            <div id="payment-detail-<?php echo $pm['id']; ?>" class="payment-detail-box" style="display:<?php echo $pm['type']=='cash'?'block':'none'; ?>;">
                                <?php if ($pm['type'] == 'bank'): ?>
                                    <p><strong>🏦 <?php echo htmlspecialchars($pm['bank_name']); ?></strong></p>
                                    <p>No. Rek: <strong><?php echo htmlspecialchars($pm['account_number']); ?></strong></p>
                                    <p>A/N: <strong><?php echo htmlspecialchars($pm['account_name']); ?></strong></p>
                                <?php elseif ($pm['type'] == 'qris'): ?>
                                    <p><strong>📱 Scan QRIS</strong></p>
                                    <?php if ($pm['qris_image']): ?>
                                    <img src="../../assets/uploads/payments/<?php echo $pm['qris_image']; ?>" style="max-width:200px;display:block;margin:10px auto;">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>💵 Bayar langsung di toko saat pengambilan.</p>
                                <?php endif; ?>
                                <?php if ($pm['instructions']): ?>
                                <p class="payment-instructions"><?php echo nl2br(htmlspecialchars($pm['instructions'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Upload Bukti -->
                            <div id="uploadProofSection" class="upload-proof-section" style="display:none;">
                                <label>📤 Upload Bukti Pembayaran</label>
                                <input type="file" name="payment_proof" accept="image/*">
                                <small>JPG/PNG/GIF, Maks 5MB</small>
                            </div>
                        </div>
                        
                        <!-- Promo & Catatan -->
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-tags"></i> Promo & Catatan</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Kode Promo</label>
                                    <input type="text" name="promo_code" placeholder="Opsional">
                                </div>
                                <div class="form-group">
                                    <label>Catatan</label>
                                    <textarea name="notes" rows="2" placeholder="Opsional"></textarea>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- SUMMARY -->
                    <aside class="checkout-summary-card">
                        <h3>Ringkasan Pesanan</h3>
                        
                        <?php foreach ($cart_items as $item): 
                            $ip = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                        ?>
                        <div class="summary-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?> <span class="item-qty">x<?php echo $item['quantity']; ?></span></span>
                            <span>Rp <?php echo number_format($ip * $item['quantity'], 0, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="summary-item"><span>Subtotal</span><span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span></div>
                        <div class="summary-item"><span>Pajak (10%)</span><span>Rp <?php echo number_format($tax, 0, ',', '.'); ?></span></div>
                        <?php if (isset($discount) && $discount > 0): ?>
                        <div class="summary-item"><span>Diskon</span><span class="summary-discount">-Rp <?php echo number_format($discount, 0, ',', '.'); ?></span></div>
                        <?php endif; ?>
                        
                        <div class="summary-total"><span>Total</span><span>Rp <?php echo number_format($final_amount ?? $total, 0, ',', '.'); ?></span></div>
                        
                        <button type="submit" class="btn-submit-order"><i class="fas fa-lock"></i> Buat Pesanan</button>
                    </aside>
                    
                </div>
            </form>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container"><div class="footer-bottom"><p>&copy; <?php echo date('Y'); ?> Kayumanies.</p></div></div>
    </footer>
    
    <!-- Tambahan style untuk payment detail & upload -->
    <style>
        .payment-detail-box {
            background: #f9f9f9; padding: 14px; border-radius: 10px;
            font-size: 12px; margin-top: 12px;
        }
        .payment-instructions { color: #666; margin-top: 8px; font-size: 11px; }
        .upload-proof-section {
            margin-top: 12px; background: #FFF8E1; padding: 14px;
            border-radius: 10px; border-left: 4px solid #FFD700;
        }
        .upload-proof-section label { font-weight: 600; font-size: 12px; display: block; margin-bottom: 6px; }
        .upload-proof-section input { width: 100%; padding: 8px; margin-bottom: 4px; }
        .upload-proof-section small { color: #666; font-size: 10px; }
    </style>
    
    <script>
    document.querySelectorAll('input[name="payment_method"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.querySelectorAll('.payment-detail-box').forEach(function(d) { d.style.display = 'none'; });
            var detail = document.getElementById('payment-detail-' + this.id.replace('pay-', ''));
            if (detail) detail.style.display = 'block';
            document.getElementById('uploadProofSection').style.display = this.value === 'cash' ? 'none' : 'block';
        });
    });
    
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        if (!confirm('Buat pesanan ini?')) e.preventDefault();
    });
    </script>
</body>
</html>
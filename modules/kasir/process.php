<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') { header('Location: dashboard.php'); exit; }

$database = Database::getInstance();
$db = $database->getConnection();
$action = $_POST['action'] ?? '';
$order_id = intval($_POST['order_id'] ?? 0);

function logActivity($db, $user_id, $action, $description) {
    try {
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch (Exception $e) {}
}

try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) throw new Exception("Pesanan tidak ditemukan!");

    // ========== KONFIRMASI PEMBAYARAN ==========
    if ($action == 'confirm_payment') {
        if ($order['payment_status'] != 'pending') throw new Exception("Pesanan ini sudah dibayar!");
        
        // Validasi stok produk
        $stmt = $db->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            $stmt = $db->prepare("SELECT stock, name FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();
            
            if (!$product) throw new Exception("Produk tidak ditemukan!");
            if ($product['stock'] < $item['quantity']) {
                throw new Exception("Stok {$product['name']} tidak mencukupi! Tersedia: {$product['stock']}, Dibutuhkan: {$item['quantity']}");
            }
        }
        
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $stmt = $db->prepare("UPDATE orders SET payment_status='paid', payment_method=?, order_status='processing', cashier_id=? WHERE id=?");
        $stmt->execute([$payment_method, $_SESSION['user_id'], $order_id]);
        
        $stmt = $db->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_status, verified_by, verified_at) VALUES (?,?,?,'verified',?,NOW())");
        $stmt->execute([$order_id, $order['final_amount'], $payment_method, $_SESSION['user_id']]);
        
        // Kurangi stok
        foreach ($items as $item) {
            $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
        }
        
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Pembayaran Dikonfirmasi ✅', ?, 'payment', ?)");
        $stmt->execute([$order['user_id'], "Pembayaran #{$order['order_number']} sebesar Rp ".number_format($order['final_amount'],0,',','.')." dikonfirmasi. Pesanan mulai diproses.", "../pembeli/orders.php?id={$order_id}"]);
        
        logActivity($db, $_SESSION['user_id'], 'confirm_payment', "Konfirmasi pembayaran #{$order['order_number']} - Rp ".number_format($order['final_amount'],0,',','.'));
        
        $msg = "✅ Pembayaran #{$order['order_number']} berhasil dikonfirmasi!";
        $type = 'success';
    }
    
    // ========== UPDATE STATUS ==========
    elseif ($action == 'update_status') {
        $new_status = $_POST['new_status'] ?? '';
        $allowed = ['pending','processing','ready','completed','cancelled'];
        if (!in_array($new_status, $allowed)) throw new Exception("Status tidak valid!");
        
        // Validasi flow
        $current = $order['order_status'];
        $valid_next = [
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['ready', 'cancelled'],
            'ready'      => ['completed', 'cancelled'],
            'completed'  => [],
            'cancelled'  => []
        ];
        
        if (!in_array($new_status, $valid_next[$current])) {
            throw new Exception("Tidak bisa mengubah status dari '{$current}' ke '{$new_status}'!");
        }
        
        // Validasi: tidak bisa proses kalau belum bayar
        if ($new_status != 'cancelled' && $order['payment_status'] != 'paid') {
            throw new Exception("Pesanan belum dibayar! Konfirmasi pembayaran dulu.");
        }
        
        $stmt = $db->prepare("UPDATE orders SET order_status=?, cashier_id=? WHERE id=?");
        $stmt->execute([$new_status, $_SESSION['user_id'], $order_id]);
        
        // Jika dibatalkan, kembalikan stok
        if ($new_status == 'cancelled') {
            $stmt = $db->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }
        
        $msg_templates = [
            'processing' => ['Pesanan Diproses 🎂', "Pesanan #{$order['order_number']} sedang diproses. Estimasi siap sesuai jadwal."],
            'ready'      => ['Pesanan Siap Diambil! 📦', "Pesanan #{$order['order_number']} sudah siap! Silakan ambil di toko Kayumanies."],
            'completed'  => ['Pesanan Selesai ✅', "Pesanan #{$order['order_number']} telah selesai. Terima kasih telah berbelanja di Kayumanies! 🙏"],
            'cancelled'  => ['Pesanan Dibatalkan ❌', "Pesanan #{$order['order_number']} telah dibatalkan. Silakan hubungi kami jika ada pertanyaan."]
        ];
        
        if (isset($msg_templates[$new_status])) {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?,?,?,'order',?)");
            $stmt->execute([$order['user_id'], $msg_templates[$new_status][0], $msg_templates[$new_status][1], "../pembeli/orders.php?id={$order_id}"]);
        }
        
        $labels = ['processing'=>'Diproses','ready'=>'Siap Diambil','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
        logActivity($db, $_SESSION['user_id'], 'update_status', "Update status #{$order['order_number']}: {$current} → {$new_status}");
        
        $msg = "✅ Status #{$order['order_number']}: <strong>{$labels[$new_status]}</strong>";
        $type = 'success';
    }
    else { throw new Exception("Action tidak valid!"); }
    
    $db->commit();
    $_SESSION['kasir_flash'] = $msg;
    $_SESSION['kasir_flash_type'] = $type;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit;
    
} catch (Exception $e) {
    $db->rollback();
    $error_msg = $e->getMessage();
    echo "<!DOCTYPE html><html><head><title>Error</title>
    <link rel='stylesheet' href='../../assets/css/kasir.css'>
    </head><body style='display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f0f4f0;'>
    <div style='background:white;padding:30px;border-radius:15px;text-align:center;box-shadow:0 5px 30px rgba(0,0,0,0.1);max-width:450px;'>
    <div style='font-size:50px;margin-bottom:15px;'>❌</div>
    <h3 style='color:#f44336;margin-bottom:10px;'>Gagal Memproses</h3>
    <p style='color:#666;margin-bottom:20px;'>{$error_msg}</p>
    <div style='display:flex;gap:10px;justify-content:center;'>
    <a href='dashboard.php' class='btn btn-primary'>Kembali ke Dashboard</a>
    <a href='javascript:history.back()' class='btn btn-outline'>Kembali Sebelumnya</a>
    </div></div></body></html>";
}
?>
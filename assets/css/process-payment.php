<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: dashboard.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';
$order_id = intval($_POST['order_id'] ?? 0);

try {
    $db->beginTransaction();
    
    // Get order info
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception("Pesanan tidak ditemukan!");
    }
    
    if ($action == 'confirm_payment') {
        // KONFIRMASI PEMBAYARAN
        $payment_method = $_POST['payment_method'] ?? $order['payment_method'];
        
        // Update order payment status
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', payment_method = ?, order_status = 'processing' WHERE id = ?");
        $stmt->execute([$payment_method, $order_id]);
        
        // Insert payment record
        $stmt = $db->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_status, verified_by, verified_at) VALUES (?, ?, ?, 'verified', ?, NOW())");
        $stmt->execute([$order_id, $order['final_amount'], $payment_method, $_SESSION['user_id']]);
        
        // Notify user
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Pembayaran Dikonfirmasi', ?, 'payment', ?)");
        $stmt->execute([$order['user_id'], "Pembayaran untuk pesanan #{$order['order_number']} telah dikonfirmasi oleh kasir.", "orders.php?id={$order_id}"]);
        
        $msg = "Pembayaran berhasil dikonfirmasi!";
        $type = 'success';
        
    } elseif ($action == 'update_status') {
        // UPDATE STATUS PESANAN
        $new_status = $_POST['new_status'] ?? '';
        
        $valid_statuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];
        
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception("Status tidak valid!");
        }
        
        $stmt = $db->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Notify user
        $status_labels = [
            'processing' => 'sedang diproses',
            'ready' => 'siap diambil',
            'completed' => 'selesai',
            'cancelled' => 'dibatalkan'
        ];
        
        $label = $status_labels[$new_status] ?? $new_status;
        
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Status Pesanan Diupdate', ?, 'order', ?)");
        $stmt->execute([$order['user_id'], "Pesanan #{$order['order_number']} sekarang {$label}.", "../pembeli/orders.php?id={$order_id}"]);
        
        $msg = "Status pesanan berhasil diupdate menjadi: " . strtoupper($new_status);
        $type = 'success';
        
    } else {
        throw new Exception("Action tidak valid!");
    }
    
    $db->commit();
    
    // Redirect dengan pesan
    $_SESSION['kasir_msg'] = $msg;
    $_SESSION['kasir_type'] = $type;
    header('Location: dashboard.php');
    exit;
    
} catch (Exception $e) {
    $db->rollback();
    die("Error: " . $e->getMessage() . "<br><a href='dashboard.php'>Kembali</a>");
}
?>
<?php
header('Content-Type: application/json');

// PASTIKAN INI ADA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$database = Database::getInstance();
$db = $database->getConnection();

if ($action == 'list') {
    $product_id = intval($_GET['product_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 5");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $reviews]);
    exit;
}

// Submit review
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'submit') {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Login dulu']);
        exit;
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = min(5, max(1, intval($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    
    // Cek apakah user pernah beli produk ini & completed
    $stmt = $db->prepare("SELECT o.id FROM orders o JOIN order_details od ON o.id = od.order_id WHERE o.user_id = ? AND od.product_id = ? AND o.order_status = 'completed' LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda hanya bisa review produk yang sudah dibeli']);
        exit;
    }
    
    // Cek belum review produk ini
    $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah review produk ini']);
        exit;
    }
    
    $stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$_SESSION['user_id'], $product_id, $rating, $comment]);
    
    echo json_encode(['success' => true, 'message' => 'Review berhasil!']);
    exit;
}

echo json_encode(['success' => false]);
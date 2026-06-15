<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: orders.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id'] ?? 0);
$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');

// Validate
if ($rating < 1 || $rating > 5) $rating = 5;

// Cek pesanan milik user & completed
$stmt = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND order_status = 'completed'");
$stmt->execute([$order_id, $user_id]);
if (!$stmt->fetch()) {
    die("Pesanan tidak valid atau belum selesai.");
}

// Cek belum review
$stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND order_id = ?");
$stmt->execute([$user_id, $order_id]);
if ($stmt->fetch()) {
    die("Anda sudah memberikan review untuk pesanan ini.");
}

// Insert review
$stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, ?, 1)");
$stmt->execute([$user_id, $product_id, $order_id, $rating, $comment]);

// Redirect back
header("Location: order-detail.php?id={$order_id}&review=success");
exit;
?>
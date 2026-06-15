<?php
/**
 * Cart API - Handle all cart operations
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    switch ($action) {
        case 'add':
            // Check login
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
                exit;
            }
            
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
                exit;
            }
            
            // Check product exists and active
            $stmt = $db->prepare("SELECT id, stock, is_active FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
                exit;
            }
            
            if (!$product['is_active']) {
                echo json_encode(['success' => false, 'message' => 'Produk tidak tersedia']);
                exit;
            }
            
            if ($product['stock'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
                exit;
            }
            
            // Check if product already in cart
            $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update quantity
                $new_quantity = $existing['quantity'] + $quantity;
                $stmt = $db->prepare("UPDATE cart SET quantity = ?, notes = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $notes, $existing['id']]);
            } else {
                // Insert new
                $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, notes) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $notes]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Produk ditambahkan ke keranjang']);
            break;
            
        case 'update':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Silakan login']);
                exit;
            }
            
            $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            if ($quantity <= 0) {
                // Remove item if quantity 0
                $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Item dihapus']);
            } else {
                $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Keranjang diupdate']);
            }
            break;
            
        case 'remove':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Silakan login']);
                exit;
            }
            
            $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
            
            $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Item dihapus dari keranjang']);
            break;
            
        case 'get':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'items' => [], 'total' => 0]);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT c.id as cart_id, c.quantity, c.notes,
                       p.id as product_id, p.name, p.price, p.discount_price, 
                       p.image, p.stock, c2.name as category_name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN categories c2 ON p.category_id = c2.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $items = $stmt->fetchAll();
            
            $total = 0;
            foreach ($items as &$item) {
                $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                $item['subtotal'] = $price * $item['quantity'];
                $total += $item['subtotal'];
            }
            
            echo json_encode(['success' => true, 'items' => $items, 'total' => $total]);
            break;
            
        case 'count':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['count' => 0]);
                exit;
            }
            
            $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            
            echo json_encode(['count' => $result['total'] ? intval($result['total']) : 0]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
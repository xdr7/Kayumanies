<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'list':
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_active = 1";
            $params = [];
            
            if (!empty($category)) {
                $sql .= " AND c.slug = ?";
                $params[] = $category;
            }
            
            if (!empty($search)) {
                $sql .= " AND p.name LIKE ?";
                $params[] = "%{$search}%";
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $products]);
            break;
            
        case 'detail':
            $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
            
            $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ?");
            $stmt->execute([$slug]);
            $product = $stmt->fetch();
            
            if ($product) {
                echo json_encode(['success' => true, 'data' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
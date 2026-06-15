<?php
/**
 * Product Detail Page
 */
session_start();
require_once __DIR__ . '/../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: products.php');
    exit;
}

// Get product detail
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.slug = ? AND p.is_active = 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get related products
$stmt = $db->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND is_active = 1 
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll();

// Get reviews
$stmt = $db->prepare("
    SELECT r.*, u.full_name, u.avatar 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #FFF8F0; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        
        .breadcrumb {
            padding: 15px 0;
            color: #666;
            font-size: 14px;
        }
        .breadcrumb a { color: #8B4513; text-decoration: none; }
        
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .product-image {
            background: #FFF5E6;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            font-size: 150px;
        }
        
        .product-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
        }
        
        .product-info h1 {
            font-size: 30px;
            margin-bottom: 10px;
            color: #2C1810;
        }
        
        .product-category {
            color: #8B4513;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .product-price {
            margin: 20px 0;
        }
        
        .price-current {
            font-size: 36px;
            font-weight: 900;
            color: #8B4513;
        }
        
        .price-old {
            font-size: 20px;
            color: #ccc;
            text-decoration: line-through;
            margin-left: 10px;
        }
        
        .product-meta {
            margin: 20px 0;
            padding: 20px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .meta-label { color: #666; }
        .meta-value { font-weight: 600; }
        
        .quantity-section {
            margin: 25px 0;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 40px; height: 40px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 20px;
        }
        
        .qty-input {
            width: 70px; height: 40px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
        }
        
        .btn-add-cart {
            width: 100%;
            padding: 16px;
            background: #8B4513;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
        }
        
        .btn-add-cart:hover {
            background: #6B3410;
            transform: translateY(-2px);
        }
        
        .description {
            margin-top: 25px;
            line-height: 1.8;
            color: #555;
        }
        
        @media (max-width: 768px) {
            .product-detail { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="../../index.php">Home</a> / 
            <a href="products.php">Produk</a> / 
            <a href="products.php?category=<?php echo urlencode($product['category_slug']); ?>">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </a> / 
            <?php echo htmlspecialchars($product['name']); ?>
        </div>
        
        <div class="product-detail">
            <div class="product-image">
                <?php if (!empty($product['image'])): ?>
                <img src="../../assets/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=font-size:150px;>🎂</div>';">
                <?php else: ?>
                <div>🎂</div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-price">
                    <?php if (!empty($product['discount_price'])): ?>
                    <span class="price-current">Rp <?php echo number_format($product['discount_price'], 0, ',', '.'); ?></span>
                    <span class="price-old">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                    <?php else: ?>
                    <span class="price-current">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                    <?php endif; ?>
                </div>
                
                <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="product-meta">
                    <?php if ($product['weight']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Berat</span>
                        <span class="meta-value"><?php echo htmlspecialchars($product['weight']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span class="meta-label">Stok</span>
                        <span class="meta-value" style="color: <?php echo $product['stock'] > 0 ? 'green' : 'red'; ?>">
                            <?php echo $product['stock'] > 0 ? $product['stock'] . ' tersedia' : 'Habis'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="quantity-section">
                    <label style="font-weight: 600; margin-bottom: 10px; display: block;">Jumlah:</label>
                    <div class="quantity-control">
                        <button class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="number" id="quantity" class="qty-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        <button class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>
                
                <button class="btn-add-cart" onclick="addToCart()">
                    🛒 Tambah ke Keranjang
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function changeQty(delta) {
            var input = document.getElementById('quantity');
            var newVal = parseInt(input.value) + delta;
            if (newVal >= 1 && newVal <= <?php echo $product['stock']; ?>) {
                input.value = newVal;
            }
        }
        
        function addToCart() {
            var qty = document.getElementById('quantity').value;
            <?php if (!isset($_SESSION['user_id'])): ?>
            alert('Silakan login terlebih dahulu');
            window.location.href = '../auth/login.php';
            return;
            <?php endif; ?>
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../../api/cart_api.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Produk berhasil ditambahkan ke keranjang!');
                    }
                }
            };
            xhr.send('action=add&product_id=<?php echo $product['id']; ?>&quantity=' + qty);
        }
    </script>
</body>
</html>
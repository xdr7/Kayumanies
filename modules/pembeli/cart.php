<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Get cart items
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.quantity, c.notes,
           p.id as product_id, p.name, p.slug, p.price, p.discount_price, 
           p.image, p.stock, p.weight, cat.name as category_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($cart_items as &$item) {
    $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
    $item['subtotal'] = $price * $item['quantity'];
    $subtotal += $item['subtotal'];
}
unset($item);

$tax = $subtotal * 0.1; // 10% tax
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>

</head>
<body>
    
    <!-- NAVBAR -->
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <!-- CART CONTENT -->
    <main class="cart-page">
        <div class="container">
            
            <!-- Header -->
            <div class="cart-page-header">
                <h1>🛒 Keranjang Belanja</h1>
                <a href="../../index.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Lanjut Belanja
                </a>
            </div>
            
            <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="icon">🛒</div>
                <h2>Keranjang Kosong</h2>
                <p>Yuk, pilih kue favoritmu!</p>
                <a href="products.php" class="btn-shop">
                    <i class="fas fa-shopping-bag"></i> Lihat Produk
                </a>
            </div>
            
            <?php else: ?>
            <!-- Cart Grid -->
            <div class="cart-grid">
                
                <!-- Cart Items -->
                <div class="cart-items-card">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" id="cart-item-<?php echo $item['cart_id']; ?>">
                        
                        <!-- Image -->
                        <div class="cart-item-image">
                            <?php if (!empty($item['image']) && $item['image'] != 'default-cake.jpg'): ?>
                            <img src="../../assets/uploads/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='🎂';">
                            <?php else: ?>
                            🎂
                            <?php endif; ?>
                        </div>
                        
                        <!-- Info -->
                        <div class="cart-item-info">
                            <span class="category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <?php if ($item['weight']): ?>
                            <span class="weight">⚖️ <?php echo htmlspecialchars($item['weight']); ?></span>
                            <?php endif; ?>
                            <div class="price">
                                Rp <?php echo number_format($item['discount_price'] ? $item['discount_price'] : $item['price'], 0, ',', '.'); ?>
                                <?php if ($item['discount_price']): ?>
                                <span class="old-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Quantity -->
                        <div class="qty-control">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">−</button>
                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['stock']; ?>"
                                   onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value)">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                        </div>
                        
                        <!-- Subtotal -->
                        <div class="subtotal">
                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                        </div>
                        
                        <!-- Remove -->
                        <button class="btn-remove-item" onclick="removeItem(<?php echo $item['cart_id']; ?>)" title="Hapus">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Summary -->
                <aside class="cart-summary-card">
                    <h3>Ringkasan Belanja</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Pajak (10%)</span>
                        <span>Rp <?php echo number_format($tax, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn-checkout">
                        <i class="fas fa-lock"></i> Lanjut ke Pembayaran
                    </a>
                    
                    <a href="products.php" class="btn-continue">
                        ← Pilih produk lain
                    </a>
                </aside>
                
            </div>
            <?php endif; ?>
            
        </div>
    </main>
    
    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Kayumanies Cake Shop. Made with ❤️</p>
            </div>
        </div>
    </footer>
    
    <!-- NOTIFICATION -->
    <div class="notification" id="notification"></div>
    
    <script>
        var isLoggedIn = true;
        var basePath = '../../';
        
        function updateQuantity(cartId, quantity) {
            if (quantity < 1) {
                removeItem(cartId);
                return;
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', basePath + 'api/cart_api.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    location.reload();
                }
            };
            xhr.onerror = function() {
                showNotification('Gagal terhubung ke server', 'error');
            };
            xhr.send('action=update&cart_id=' + cartId + '&quantity=' + quantity);
        }
        
        function removeItem(cartId) {
            if (!confirm('Hapus item ini dari keranjang?')) return;
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', basePath + 'api/cart_api.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    location.reload();
                }
            };
            xhr.onerror = function() {
                showNotification('Gagal terhubung ke server', 'error');
            };
            xhr.send('action=remove&cart_id=' + cartId);
        }
        
        function showNotification(message, type) {
            var notif = document.getElementById('notification');
            if (!notif) {
                notif = document.createElement('div');
                notif.id = 'notification';
                notif.className = 'notification';
                document.body.appendChild(notif);
            }
            notif.innerHTML = message;
            notif.className = 'notification ' + type + ' show';
            setTimeout(function() { notif.classList.remove('show'); }, 4000);
        }
    </script>
    
</body>
</html>
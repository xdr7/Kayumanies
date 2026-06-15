<?php
session_start();
define('APP_RUNNING', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // GET FEATURED PRODUCTS
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.created_at DESC LIMIT 8");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
    
    // GET ALL CATEGORIES
    $stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // GET PROMOS ACTIVE
    $stmt = $db->prepare("SELECT * FROM promos WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW() LIMIT 1");
    $stmt->execute();
    $active_promo = $stmt->fetch();
    
    $cart_count = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $cart_count = $result['total'] ? intval($result['total']) : 0;
    }
    
} catch (Exception $e) {
    if (!file_exists(__DIR__ . '/config/installed.lock')) {
        header('Location: install.php');
        exit;
    }
    $featured_products = [];
    $categories = [];
    $active_promo = null;
}

$products_json = json_encode($featured_products);
$base_path = '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?php echo $theme['primary'] ?? '#8B4513'; ?>">
    <meta name="description" content="Kayumanies - Toko Kue Premium">
    <title>Kayumanies - Premium Cake Shop</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<?php require_once __DIR__ . '/includes/theme.php'; ?>	
    <link rel="stylesheet" href="assets/css/pembeli.css">
    <?php $pwa_base = ''; require_once __DIR__ . '/includes/pwa.php'; ?>
</head>
<body>
    
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    
    <!-- ========== HERO ========== -->
					<?php
				// Get hero settings
				$hero_badge = '✨ Premium Quality Cake';
				$hero_title = 'Kue Lezat untuk <span class="highlight">Momen Istimewa</span>';
				$hero_desc = 'Nikmati kelezatan kue premium buatan tangan dengan bahan berkualitas terbaik. Setiap gigitan adalah kebahagiaan.';
				$hero_image = '';
				$hero_stat1_num = '1000+';
				$hero_stat1_label = 'Pelanggan Puas';
				$hero_stat2_num = '50+';
				$hero_stat2_label = 'Varian Kue';
				$hero_stat3_num = '⭐4.9';
				$hero_stat3_label = 'Rating';

				try {
					$stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'hero_%'");
					$stmt->execute();
					foreach ($stmt->fetchAll() as $row) {
						$key = $row['setting_key'];
						if ($key == 'hero_badge') $hero_badge = $row['setting_value'];
						if ($key == 'hero_title') $hero_title = $row['setting_value'];
						if ($key == 'hero_desc') $hero_desc = $row['setting_value'];
						if ($key == 'hero_image') $hero_image = $row['setting_value'];
						if ($key == 'hero_stat1_num') $hero_stat1_num = $row['setting_value'];
						if ($key == 'hero_stat1_label') $hero_stat1_label = $row['setting_value'];
						if ($key == 'hero_stat2_num') $hero_stat2_num = $row['setting_value'];
						if ($key == 'hero_stat2_label') $hero_stat2_label = $row['setting_value'];
						if ($key == 'hero_stat3_num') $hero_stat3_num = $row['setting_value'];
						if ($key == 'hero_stat3_label') $hero_stat3_label = $row['setting_value'];
					}
				} catch (Exception $e) {}
				?>

				<!-- ========== HERO ========== -->
				<section class="hero" id="home">
					<div class="container">
						<div class="hero-grid">
							<div class="hero-content">
								<div class="hero-badge"><?php echo htmlspecialchars($hero_badge); ?></div>
								<h1><?php echo $hero_title; ?></h1>
								<p class="hero-desc"><?php echo htmlspecialchars($hero_desc); ?></p>
								<div class="hero-buttons">
									<a href="modules/pembeli/products.php" class="btn-primary">🛍️ Belanja Sekarang</a>
									<a href="#categories" class="btn-outline">📋 Lihat Kategori</a>
								</div>
								<div class="hero-stats">
									<div class="stat-item"><span class="stat-number"><?php echo htmlspecialchars($hero_stat1_num); ?></span><span class="stat-label"><?php echo htmlspecialchars($hero_stat1_label); ?></span></div>
									<div class="stat-item"><span class="stat-number"><?php echo htmlspecialchars($hero_stat2_num); ?></span><span class="stat-label"><?php echo htmlspecialchars($hero_stat2_label); ?></span></div>
									<div class="stat-item"><span class="stat-number"><?php echo htmlspecialchars($hero_stat3_num); ?></span><span class="stat-label"><?php echo htmlspecialchars($hero_stat3_label); ?></span></div>
								</div>
							</div>
							<div class="hero-image">
								<?php if (!empty($hero_image) && file_exists(__DIR__ . '/assets/images/' . $hero_image)): ?>
								<img src="assets/images/<?php echo $hero_image; ?>" alt="Hero" class="hero-cake" style="max-width:450px;">
								<?php else: ?>
								<div style="font-size:250px;">🎂</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</section>
    
    <!-- ========== CATEGORIES ========== -->
    <section class="section" id="categories">
        <div class="container">
            <div class="section-header">
                <div class="section-subtitle">Koleksi</div>
                <h2 class="section-title">Kategori Pilihan</h2>
                <p class="section-desc">Pilih dari berbagai kategori kue spesial</p>
            </div>
            
            <div class="categories-wrapper">
                <button class="cat-scroll-btn cat-scroll-left" onclick="scrollCategories(-300)" aria-label="Geser kiri">‹</button>
                <div class="categories-scroll" id="categoriesScroll">
                    <?php foreach ($categories as $cat): ?>
                    <a href="modules/pembeli/products.php?category=<?php echo urlencode($cat['slug']); ?>" class="category-card">
                        <span class="cat-icon">🍰</span>
                        <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($cat['description'], 0, 50)); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <button class="cat-scroll-btn cat-scroll-right" onclick="scrollCategories(300)" aria-label="Geser kanan">›</button>
            </div>
        </div>
    </section>
    
    <!-- ========== PRODUCTS ========== -->
    <section class="section section-light" id="products">
        <div class="container">
            <div class="section-header">
                <div class="section-subtitle">Best Seller</div>
                <h2 class="section-title">Produk Unggulan</h2>
                <p class="section-desc">Pilihan terbaik yang paling disukai</p>
            </div>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-img">
                        <?php if (!empty($product['image']) && $product['image'] != 'default-cake.jpg'): ?>
                        <img src="assets/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null;this.parentElement.innerHTML='<div style=display:flex;align-items:center;justify-content:center;height:100%;font-size:80px;>🎂</div>';">
                        <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:80px;">🎂</div>
                        <?php endif; ?>
                        <?php if (!empty($product['discount_price'])): ?><span class="product-badge">DISKON</span><?php endif; ?>
                    </div>
                    <div class="product-body">
                        <span class="product-cat"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">
                            <?php if (!empty($product['discount_price'])): ?>
                                <span class="price-now">Rp <?php echo number_format($product['discount_price'],0,',','.'); ?></span>
                                <span class="price-old">Rp <?php echo number_format($product['price'],0,',','.'); ?></span>
                            <?php else: ?>
                                <span class="price-now">Rp <?php echo number_format($product['price'],0,',','.'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">🛒 Keranjang</button>
                            <button class="btn-detail" onclick="openDetailModal(<?php echo $product['id']; ?>)">👁️ Detail</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center;margin-top:40px;">
                <a href="modules/pembeli/products.php" class="btn-outline">Lihat Semua Produk →</a>
            </div>
        </div>
    </section>
    
    <!-- ========== PROMO ========== -->
    <?php if ($active_promo): ?>
    <div class="promo-banner">
        <div class="promo-content">
            <h2>🎉 <?php echo htmlspecialchars($active_promo['name']); ?></h2>
            <p><?php echo htmlspecialchars($active_promo['description']); ?></p>
            <div class="promo-code-box"><?php echo htmlspecialchars($active_promo['code']); ?></div>
            <br>
            <a href="modules/pembeli/products.php" class="btn-primary" style="background:white;color:#8B4513;display:inline-block;">Klaim Diskon</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ========== FOOTER ========== -->
    <?php $base_path = ''; require_once __DIR__ . '/includes/footer.php'; ?>
    
    <!-- ========== MODAL DETAIL PRODUK ========== -->
    <div class="modal" id="detailModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title" id="detailName">Detail Produk</h2>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="product-detail-grid">
                    <div class="detail-image" id="detailImage"><div style="font-size:120px;">🎂</div></div>
                    <div class="detail-info">
                        <span class="detail-category" id="detailCategory"></span>
                        <h2 id="detailTitle"></h2>
                        <div class="detail-price">
                            <span class="current" id="detailPrice"></span>
                            <span class="old" id="detailOldPrice" style="display:none;"></span>
                        </div>
                        <p class="detail-desc" id="detailDesc"></p>
                        <div class="detail-meta">
                            <span>📦 Stok: <strong id="detailStock"></strong></span>
                            <span>⚖️ Berat: <strong id="detailWeight"></strong></span>
                        </div>
                        
                        <!-- REVIEW SECTION -->
                        <div id="reviewSection" style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;">
                            <strong style="font-size:12px;">⭐ Review Pelanggan</strong>
                            <div id="reviewList" style="max-height:150px;overflow-y:auto;font-size:11px;"><p style="color:#999;">Memuat...</p></div>
                            <div id="reviewForm" style="margin-top:8px;display:none;">
                                <div style="font-size:20px;cursor:pointer;text-align:center;" id="starRating">
                                    <span onclick="setReviewStar(1)">★</span><span onclick="setReviewStar(2)">★</span><span onclick="setReviewStar(3)">★</span><span onclick="setReviewStar(4)">★</span><span onclick="setReviewStar(5)">★</span>
                                </div>
                                <input type="hidden" id="reviewRating" value="5">
                                <textarea id="reviewComment" style="width:100%;padding:6px;border:1px solid #ddd;border-radius:6px;font-size:11px;margin-top:5px;" rows="2" placeholder="Tulis review..."></textarea>
                                <button onclick="submitReview()" style="width:100%;padding:6px;background:#8B4513;color:white;border:none;border-radius:6px;font-size:11px;margin-top:5px;cursor:pointer;">Kirim Review</button>
                            </div>
                        </div>
                        
                        <div class="quantity-control">
                            <button class="qty-btn" onclick="changeDetailQty(-1)">−</button>
                            <input type="number" class="qty-input" id="detailQty" value="1" min="1" max="99" readonly>
                            <button class="qty-btn" onclick="changeDetailQty(1)">+</button>
                        </div>
                        <input type="hidden" id="detailId"><input type="hidden" id="detailMaxStock" value="99">
                        <button class="btn-add-to-cart" onclick="addToCartFromDetail()">🛒 Tambah ke Keranjang</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- NOTIFICATION TOAST -->
    <div class="notification" id="notification"></div>
    
    <script>
    var allProducts = <?php echo $products_json; ?>;
    var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    var basePath = '';
    
    function scrollCategories(amount) {
        document.getElementById('categoriesScroll').scrollBy({ left: amount, behavior: 'smooth' });
    }
    
    function findProduct(id) { return allProducts.find(function(p) { return p.id == id; }) || null; }
    
    function openDetailModal(productId) {
        var product = findProduct(productId);
        if (product) { showDetailModal(product); }
        else { fetchProductDetail(productId); }
    }
    
    function showDetailModal(product) {
        var modal = document.getElementById('detailModal');
        modal.style.display = 'block'; setTimeout(function() { modal.classList.add('show'); }, 10);
        document.body.style.overflow = 'hidden';
        
        document.getElementById('detailId').value = product.id;
        document.getElementById('detailName').textContent = product.name;
        document.getElementById('detailTitle').textContent = product.name;
        document.getElementById('detailCategory').textContent = product.category_name || '';
        document.getElementById('detailDesc').textContent = product.description || 'Tidak ada deskripsi.';
        document.getElementById('detailStock').textContent = (product.stock || 0) + ' tersedia';
        document.getElementById('detailWeight').textContent = product.weight || '-';
        document.getElementById('detailQty').value = 1;
        document.getElementById('detailMaxStock').value = product.stock || 99;
        document.getElementById('detailQty').max = product.stock || 99;
        
        if (product.discount_price && product.discount_price > 0) {
            document.getElementById('detailPrice').textContent = 'Rp ' + numberFormat(product.discount_price);
            document.getElementById('detailOldPrice').textContent = 'Rp ' + numberFormat(product.price);
            document.getElementById('detailOldPrice').style.display = 'inline';
        } else {
            document.getElementById('detailPrice').textContent = 'Rp ' + numberFormat(product.price);
            document.getElementById('detailOldPrice').style.display = 'none';
        }
        
        var imageDiv = document.getElementById('detailImage');
        if (product.image && product.image != 'default-cake.jpg' && product.image != '') {
            imageDiv.innerHTML = '<img src="assets/uploads/products/' + product.image + '" alt="' + product.name + '" onerror="this.onerror=null;this.parentElement.innerHTML=\'<div style=font-size:120px;>🎂</div>\';">';
        } else {
            imageDiv.innerHTML = '<div style="font-size:120px;">🎂</div>';
        }
        
        loadReviews(product.id);
        document.getElementById('reviewForm').style.display = isLoggedIn ? 'block' : 'none';
        if (isLoggedIn) setReviewStar(5);
    }
    
    function fetchProductDetail(productId) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/product_api.php?action=detail&id=' + productId, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success && resp.data) showDetailModal(resp.data);
            }
        }; xhr.send();
    }
    
    function closeDetailModal() {
        var modal = document.getElementById('detailModal');
        modal.classList.remove('show');
        setTimeout(function() { modal.style.display = 'none'; document.body.style.overflow = ''; }, 200);
    }
    
    function changeDetailQty(delta) {
        var input = document.getElementById('detailQty');
        var max = parseInt(document.getElementById('detailMaxStock').value) || 99;
        var newVal = parseInt(input.value) + delta;
        if (newVal >= 1 && newVal <= max) input.value = newVal;
    }
    
    function addToCartFromDetail() {
        if (!isLoggedIn) { showNotification('Silakan <a href="modules/auth/login.php">login</a> dulu', 'info', 5000); return; }
        var pid = document.getElementById('detailId').value;
        var qty = document.getElementById('detailQty').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/cart_api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                showNotification(resp.success ? '✅ Ditambahkan!' : '❌ ' + resp.message, resp.success ? 'success' : 'error');
                if (resp.success) { updateCartBadge(); closeDetailModal(); }
            }
        }; xhr.send('action=add&product_id=' + pid + '&quantity=' + qty);
    }
    
    function addToCart(productId) {
        if (!isLoggedIn) { showNotification('Silakan <a href="modules/auth/login.php">login</a> dulu', 'info', 5000); return; }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/cart_api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                showNotification(resp.success ? '✅ Ditambahkan!' : '❌ ' + resp.message, resp.success ? 'success' : 'error');
                if (resp.success) updateCartBadge();
            }
        }; xhr.send('action=add&product_id=' + productId + '&quantity=1');
    }
    
    function updateCartBadge() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/cart_api.php?action=count', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                document.querySelectorAll('.cart-badge, #cartBadge').forEach(function(b) {
                    b.textContent = resp.count || 0;
                    b.style.display = resp.count > 0 ? 'flex' : 'none';
                });
            }
        }; xhr.send();
    }
    
    function loadReviews(productId) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/review_api.php?action=list&product_id=' + productId, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                var html = '';
                if (resp.data && resp.data.length > 0) {
                    resp.data.forEach(function(r) {
                        html += '<div style="padding:5px 0;border-bottom:1px solid #f0f0f0;">';
                        html += '<div style="color:#FFD700;font-size:11px;">' + '★'.repeat(r.rating) + '☆'.repeat(5-r.rating) + '</div>';
                        if (r.comment) html += '<p style="font-size:10px;color:#555;margin:2px 0;">' + r.comment + '</p>';
                        html += '<small style="color:#999;">- ' + r.full_name + '</small></div>';
                    });
                } else { html = '<p style="color:#999;font-size:10px;">Belum ada review</p>'; }
                document.getElementById('reviewList').innerHTML = html;
            }
        }; xhr.send();
    }
    
    function setReviewStar(r) {
        document.getElementById('reviewRating').value = r;
        document.querySelectorAll('#starRating span').forEach(function(s, i) { s.style.color = i < r ? '#FFD700' : '#ddd'; });
    }
    
    function submitReview() {
        var pid = document.getElementById('detailId').value;
        var rating = document.getElementById('reviewRating').value;
        var comment = document.getElementById('reviewComment').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/review_api.php?action=submit', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var resp = JSON.parse(xhr.responseText);
            showNotification(resp.success ? '✅ Review terkirim!' : '❌ ' + resp.message, resp.success ? 'success' : 'error');
            if (resp.success) { loadReviews(pid); document.getElementById('reviewForm').style.display = 'none'; document.getElementById('reviewComment').value = ''; }
        };
        xhr.send('product_id=' + pid + '&rating=' + rating + '&comment=' + encodeURIComponent(comment));
    }
    
    function showNotification(message, type, duration) {
        duration = duration || 4000;
        var notif = document.getElementById('notification');
        notif.innerHTML = message; notif.className = 'notification ' + type + ' show';
        clearTimeout(notif._timeout);
        notif._timeout = setTimeout(function() { notif.classList.remove('show'); }, duration);
    }
    
    function numberFormat(num) { return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    
    window.addEventListener('click', function(e) { if (e.target === document.getElementById('detailModal')) closeDetailModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeDetailModal(); });
    
    document.querySelectorAll('a[href^="#"]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            var t = document.querySelector(this.getAttribute('href'));
            if (t) { t.scrollIntoView({ behavior: 'smooth' }); }
        });
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        if (isLoggedIn) { updateCartBadge(); setInterval(updateCartBadge, 30000); }
    });
    
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() { navigator.serviceWorker.register('service-worker.js').catch(function() {}); });
    }
    </script>
</body>
</html>
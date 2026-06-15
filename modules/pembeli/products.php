<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE p.is_active = 1";
$params = [];

if (!empty($category)) {
    $where .= " AND c.slug = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id {$where}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get products
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id {$where} ORDER BY p.created_at DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Produk - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<?php require_once __DIR__ . '/../../includes/theme.php'; ?>
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <!-- CONTENT -->
    <div style="padding-top: 100px;">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">🍰 Semua Produk</h1>
                <p class="section-desc"><?php echo $total; ?> produk ditemukan</p>
            </div>
            
            <!-- FILTER BAR -->
            <div class="filter-bar">
                <a href="products.php" class="filter-btn <?php echo empty($category) ? 'active' : ''; ?>">Semua</a>
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat['slug']); ?>" class="filter-btn <?php echo $category == $cat['slug'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php endforeach; ?>
                
                <form class="search-box" method="GET">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari kue..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <!-- PRODUCTS GRID -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-img">
                        <?php if (!empty($product['image']) && $product['image'] != 'default-cake.jpg'): ?>
                        <img src="../../assets/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=display:flex;align-items:center;justify-content:center;height:100%;font-size:80px;>🎂</div>';">
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
                                <span class="price-now">Rp <?php echo number_format($product['discount_price'], 0, ',', '.'); ?></span>
                                <span class="price-old">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                            <?php else: ?>
                                <span class="price-now">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">🛒 Keranjang</button>
                            <button class="btn-detail" onclick="openDetail(<?php echo htmlspecialchars(json_encode($product)); ?>)">👁️ Detail</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ========== MODAL DETAIL PRODUK ========== -->
    <div class="modal" id="detailModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title" id="detailName">Detail Produk</h2>
                <button class="modal-close" onclick="closeDetail()">&times;</button>
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
                            <button class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="number" class="qty-input" id="detailQty" value="1" min="1" max="99" readonly>
                            <button class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                        <input type="hidden" id="detailId">
                        <button class="btn-add-to-cart" onclick="addToCartFromDetail()">🛒 Tambah ke Keranjang</button>
                    </div>
                </div>
            </div>
        </div>
    </div><br>
    
    <!-- FOOTER DINAMIS -->
    <?php $base_path = '../../'; require_once __DIR__ . '/../../includes/footer.php'; ?>
    
    <!-- NOTIFICATION -->
    <div class="notification" id="notification"></div>
    
    <script>
    var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    
    function openDetail(product) {
        var modal = document.getElementById('detailModal');
        modal.style.display = 'block'; modal.classList.add('show');
        
        document.getElementById('detailId').value = product.id;
        document.getElementById('detailName').textContent = product.name;
        document.getElementById('detailTitle').textContent = product.name;
        document.getElementById('detailCategory').textContent = product.category_name || '';
        document.getElementById('detailDesc').textContent = product.description || 'Tidak ada deskripsi';
        document.getElementById('detailStock').textContent = (product.stock || 0) + ' tersedia';
        document.getElementById('detailWeight').textContent = product.weight || '-';
        document.getElementById('detailQty').value = 1;
        document.getElementById('detailQty').max = product.stock || 99;
        
        if (product.discount_price) {
            document.getElementById('detailPrice').textContent = 'Rp ' + numberFormat(product.discount_price);
            document.getElementById('detailOldPrice').textContent = 'Rp ' + numberFormat(product.price);
            document.getElementById('detailOldPrice').style.display = 'inline';
        } else {
            document.getElementById('detailPrice').textContent = 'Rp ' + numberFormat(product.price);
            document.getElementById('detailOldPrice').style.display = 'none';
        }
        
        var imageDiv = document.getElementById('detailImage');
        if (product.image && product.image !== 'default-cake.jpg') {
            imageDiv.innerHTML = '<img src="../../assets/uploads/products/' + product.image + '" alt="' + product.name + '" onerror="this.onerror=null;this.parentElement.innerHTML=\'<div style=font-size:120px;>🎂</div>\';">';
        } else {
            imageDiv.innerHTML = '<div style="font-size:120px;">🎂</div>';
        }
        
        loadReviews(product.id);
        document.getElementById('reviewForm').style.display = isLoggedIn ? 'block' : 'none';
        if (isLoggedIn) setReviewStar(5);
    }
    
    function closeDetail() {
        var modal = document.getElementById('detailModal');
        modal.classList.remove('show');
        setTimeout(function() { modal.style.display = 'none'; }, 200);
    }
    
    function changeQty(delta) {
        var input = document.getElementById('detailQty');
        var newVal = parseInt(input.value) + delta;
        if (newVal >= 1 && newVal <= parseInt(input.max)) input.value = newVal;
    }
    
    function addToCartFromDetail() {
        if (!isLoggedIn) { showNotification('Silakan <a href="../auth/login.php">login</a> dulu', 'info', 5000); return; }
        var pid = document.getElementById('detailId').value;
        var qty = document.getElementById('detailQty').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../api/cart_api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                showNotification(resp.success ? '✅ Ditambahkan!' : '❌ ' + resp.message, resp.success ? 'success' : 'error');
                if (resp.success) { updateCartBadge(); closeDetail(); }
            }
        }; xhr.send('action=add&product_id=' + pid + '&quantity=' + qty);
    }
    
    function addToCart(productId) {
        if (!isLoggedIn) { showNotification('Silakan <a href="../auth/login.php">login</a> dulu', 'info', 5000); return; }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../api/cart_api.php', true);
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
        xhr.open('GET', '../../api/cart_api.php?action=count', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                document.querySelectorAll('.cart-badge').forEach(function(b) {
                    b.textContent = resp.count || 0;
                    b.style.display = resp.count > 0 ? 'flex' : 'none';
                });
            }
        }; xhr.send();
    }
    
    function loadReviews(productId) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../../api/review_api.php?action=list&product_id=' + productId, true);
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
        xhr.open('POST', '../../api/review_api.php?action=submit', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var resp = JSON.parse(xhr.responseText);
            showNotification(resp.success ? '✅ Review terkirim!' : '❌ ' + resp.message, resp.success ? 'success' : 'error');
            if (resp.success) { loadReviews(pid); document.getElementById('reviewComment').value = ''; }
        };
        xhr.send('product_id=' + pid + '&rating=' + rating + '&comment=' + encodeURIComponent(comment));
    }
    
    function showNotification(message, type) {
        var notif = document.getElementById('notification');
        notif.innerHTML = message; notif.className = 'notification ' + type + ' show';
        setTimeout(function() { notif.classList.remove('show'); }, 4000);
    }
    
    function numberFormat(num) { return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    
    window.addEventListener('click', function(e) { if (e.target === document.getElementById('detailModal')) closeDetail(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeDetail(); });
    
    document.addEventListener('DOMContentLoaded', function() {
        if (isLoggedIn) updateCartBadge();
    });
    </script>
</body>
</html>
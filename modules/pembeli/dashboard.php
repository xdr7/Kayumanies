<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// ==========================================
// HANDLE SUBMIT REVIEW
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $product_id = intval($_POST['product_id']);
    $rating = min(5, max(1, intval($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    
    $stmt = $db->prepare("SELECT o.id FROM orders o JOIN order_details od ON o.id = od.order_id WHERE o.user_id = ? AND od.product_id = ? AND o.order_status = 'completed' LIMIT 1");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetch()) {
        $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $product_id, $rating, $comment]);
            header('Location: dashboard.php?review=success');
            exit;
        }
    }
}

// ==========================================
// STATISTIK
// ==========================================
$stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN order_status='pending' THEN 1 END) as pending, COUNT(CASE WHEN order_status='processing' THEN 1 END) as processing, COUNT(CASE WHEN order_status='ready' THEN 1 END) as ready, COUNT(CASE WHEN order_status='completed' THEN 1 END) as completed FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// ==========================================
// PAGINATION - PESANAN
// ==========================================
$order_page = isset($_GET['order_page']) ? max(1, intval($_GET['order_page'])) : 1;
$order_limit = 10;
$order_offset = ($order_page - 1) * $order_limit;
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['total'];
$total_order_pages = ceil($total_orders / $order_limit);
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $order_limit, $order_offset]);
$orders = $stmt->fetchAll();

// ==========================================
// PAGINATION - PRODUK SUDAH DIBELI
// ==========================================
$bought_page = isset($_GET['bought_page']) ? max(1, intval($_GET['bought_page'])) : 1;
$bought_limit = 8;
$bought_offset = ($bought_page - 1) * $bought_limit;

// Count
$stmt = $db->prepare("SELECT COUNT(DISTINCT od.product_id) as total FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.user_id = ? AND o.order_status = 'completed'");
$stmt->execute([$user_id]);
$total_bought = $stmt->fetch()['total'];
$total_bought_pages = ceil($total_bought / $bought_limit);

// Get products
$stmt = $db->prepare("SELECT p.*, c.name as category_name,
    (SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = p.id) as is_reviewed,
    (SELECT rating FROM reviews WHERE user_id = ? AND product_id = p.id LIMIT 1) as my_rating
    FROM products p
    JOIN (SELECT DISTINCT od.product_id, MAX(o.updated_at) as last_updated FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.user_id = ? AND o.order_status = 'completed' GROUP BY od.product_id) bought ON p.id = bought.product_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY bought.last_updated DESC LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $user_id, $user_id, $bought_limit, $bought_offset]);
$bought_products = $stmt->fetchAll();

$review_success = isset($_GET['review']) && $_GET['review'] == 'success';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main style="padding-top:100px;padding-bottom:60px;min-height:100vh;">
        <div class="container">
            
            <div style="background:linear-gradient(135deg,#8B4513,#A0522D);color:white;padding:25px;border-radius:16px;margin-bottom:20px;">
                <h1 style="font-size:20px;">👋 Halo, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
                <p style="opacity:0.9;font-size:14px;">Selamat datang di dashboard Anda</p>
            </div>
            
            <?php if ($review_success): ?>
            <div style="background:#E8F5E9;color:#2E7D32;padding:12px 15px;border-radius:10px;margin-bottom:15px;border-left:4px solid #4CAF50;">✅ Review berhasil dikirim! Terima kasih.</div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px;margin-bottom:20px;">
                <a href="orders.php" style="background:white;padding:14px;border-radius:12px;text-align:center;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><div style="font-size:22px;font-weight:900;"><?php echo $stats['total']; ?></div><div style="font-size:10px;color:#999;">Total</div></a>
                <a href="orders.php?status=pending" style="background:white;padding:14px;border-radius:12px;text-align:center;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><div style="font-size:22px;font-weight:900;color:#ff9800;"><?php echo $stats['pending']; ?></div><div style="font-size:10px;color:#999;">Pending</div></a>
                <a href="orders.php?status=processing" style="background:white;padding:14px;border-radius:12px;text-align:center;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><div style="font-size:22px;font-weight:900;color:#2196F3;"><?php echo $stats['processing']; ?></div><div style="font-size:10px;color:#999;">Diproses</div></a>
                <a href="orders.php?status=ready" style="background:white;padding:14px;border-radius:12px;text-align:center;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><div style="font-size:22px;font-weight:900;color:#4CAF50;"><?php echo $stats['ready']; ?></div><div style="font-size:10px;color:#999;">Siap</div></a>
                <a href="orders.php?status=completed" style="background:white;padding:14px;border-radius:12px;text-align:center;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,0.05);"><div style="font-size:22px;font-weight:900;"><?php echo $stats['completed']; ?></div><div style="font-size:10px;color:#999;">Selesai</div></a>
            </div>
            
            <!-- TABEL PESANAN -->
            <div style="background:white;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:25px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h3 style="font-size:16px;">📋 Daftar Pesanan (<?php echo $total_orders; ?>)</h3>
                    <a href="orders.php" style="color:#8B4513;font-size:13px;font-weight:600;text-decoration:none;">Lihat Semua →</a>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead><tr style="background:#fafafa;"><th style="padding:10px;text-align:left;font-size:11px;color:#666;">No. Pesanan</th><th style="padding:10px;text-align:left;font-size:11px;color:#666;">Tanggal</th><th style="padding:10px;text-align:left;font-size:11px;color:#666;">Total</th><th style="padding:10px;text-align:left;font-size:11px;color:#666;">Status</th><th style="padding:10px;text-align:left;font-size:11px;color:#666;">Bayar</th></tr></thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#999;">Belum ada pesanan</td></tr>
                            <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px;"><strong>#<?php echo $o['order_number']; ?></strong></td>
                                <td style="padding:10px;font-size:12px;"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></td>
                                <td style="padding:10px;">Rp <?php echo number_format($o['final_amount'],0,',','.'); ?></td>
                                <td style="padding:10px;"><span style="padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700;<?php echo $o['order_status']=='pending'?'background:#FFF3E0;color:#E65100;':($o['order_status']=='processing'?'background:#E3F2FD;color:#1565C0;':($o['order_status']=='ready'?'background:#E8F5E9;color:#2E7D32;':'background:#ECEFF1;color:#455A64;')); ?>"><?php echo strtoupper($o['order_status']); ?></span></td>
                                <td style="padding:10px;"><span style="padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700;<?php echo $o['payment_status']=='paid'?'background:#E8F5E9;color:#2E7D32;':'background:#FFF3E0;color:#E65100;'; ?>"><?php echo $o['payment_status']=='paid'?'Lunas':'Belum'; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_order_pages > 1): ?>
                <div style="display:flex;gap:5px;justify-content:center;margin-top:15px;">
                    <?php for($i=1;$i<=$total_order_pages;$i++): ?>
                    <a href="?order_page=<?php echo $i; ?>" style="padding:5px 10px;border:1px solid #ddd;border-radius:5px;text-decoration:none;color:#333;font-size:11px;<?php echo $order_page==$i?'background:#8B4513;color:white;border-color:#8B4513;':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- PRODUK SUDAH DIBELI -->
            <div style="background:white;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:25px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h3 style="font-size:16px;">🛍️ Produk Sudah Dibeli (<?php echo $total_bought; ?>)</h3>
                    <a href="products.php" style="color:#8B4513;font-size:13px;font-weight:600;text-decoration:none;">Belanja Lagi →</a>
                </div>
                
                <?php if (empty($bought_products)): ?>
                <div style="text-align:center;padding:40px;color:#999;">Belum ada produk yang selesai dibeli.</div>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($bought_products as $product): ?>
                    <div class="product-card">
                        <div class="product-img">
                            <?php if (!empty($product['image']) && $product['image'] != 'default-cake.jpg'): ?>
                            <img src="../../assets/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=img-placeholder>🎂</div>';">
                            <?php else: ?>
                            <div class="img-placeholder">🎂</div>
                            <?php endif; ?>
                            <?php if ($product['is_reviewed'] > 0): ?>
                            <span class="product-badge-reviewed">✅</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <span class="product-cat"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">
                                <span class="price-now">Rp <?php echo number_format($product['price'],0,',','.'); ?></span>
                            </div>
                            
                            <?php if ($product['is_reviewed'] > 0): ?>
                            <div class="product-actions">
                                <span class="btn-cart" style="background:#4CAF50;cursor:default;">✅ Direview (<?php echo $product['my_rating']; ?>⭐)</span>
                            </div>
                            <?php else: ?>
                            <div class="product-actions">
                                <button class="btn-detail" onclick="openReviewModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">⭐ Review</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_bought_pages > 1): ?>
                <div style="display:flex;gap:5px;justify-content:center;margin-top:15px;">
                    <?php for($i=1;$i<=$total_bought_pages;$i++): ?>
                    <a href="?bought_page=<?php echo $i; ?>" style="padding:5px 10px;border:1px solid #ddd;border-radius:5px;text-decoration:none;color:#333;font-size:11px;<?php echo $bought_page==$i?'background:#8B4513;color:white;border-color:#8B4513;':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
    
    <!-- MODAL REVIEW -->
    <div class="modal" id="reviewModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-header">
                <h2 class="modal-title">⭐ Review Produk</h2>
                <button class="modal-close" onclick="closeReviewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <h3 id="reviewProductName" style="text-align:center;"></h3>
                <form method="POST" action="">
                    <input type="hidden" name="submit_review" value="1">
                    <input type="hidden" name="product_id" id="reviewProductId">
                    
                    <div class="text-center mt-1 mb-1">
                        <div id="starRating" style="font-size:28px;cursor:pointer;">
                            <span onclick="setStar(1)">★</span>
                            <span onclick="setStar(2)">★</span>
                            <span onclick="setStar(3)">★</span>
                            <span onclick="setStar(4)">★</span>
                            <span onclick="setStar(5)">★</span>
                        </div>
                        <input type="hidden" name="rating" id="reviewRating" value="5">
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Tulis komentar (opsional)"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full mt-1"><i class="fas fa-paper-plane"></i> Kirim Review</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php $base_path = '../../'; require_once __DIR__ . '/../../includes/footer.php'; ?>
    
    <script>
    function openReviewModal(product) {
        document.getElementById('reviewProductId').value = product.id;
        document.getElementById('reviewProductName').textContent = product.name;
        document.getElementById('reviewModal').style.display = 'block';
        document.getElementById('reviewModal').classList.add('show');
        document.body.style.overflow = 'hidden';
        setStar(5);
    }
    
    function closeReviewModal() {
        var m = document.getElementById('reviewModal');
        m.classList.remove('show');
        setTimeout(function(){ m.style.display = 'none'; document.body.style.overflow = ''; }, 200);
    }
    
    function setStar(r) {
        document.getElementById('reviewRating').value = r;
        var stars = document.querySelectorAll('#starRating span');
        stars.forEach(function(s, i) { s.style.color = i < r ? '#FFD700' : '#ddd'; });
    }
    
    window.addEventListener('click', function(e) { if (e.target === document.getElementById('reviewModal')) closeReviewModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeReviewModal(); });
    </script>
</body>
</html>
<?php
/**
 * Navbar untuk halaman pembeli/frontend
 * Include di: index.php, products.php, cart.php, checkout.php, orders.php
 */

// Get cart count if logged in
// Get brand info
$brand_name = 'Kayumanies';
$brand_logo = '';

try {
    require_once __DIR__ . '/../config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_name', 'store_logo')");
    foreach ($stmt->fetchAll() as $row) {
        if ($row['setting_key'] == 'store_name') $brand_name = $row['setting_value'];
        if ($row['setting_key'] == 'store_logo') $brand_logo = $row['setting_value'];
    }
} catch (Exception $e) {}

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute(array($_SESSION['user_id']));
        $result = $stmt->fetch();
        $cart_count = $result['total'] ? intval($result['total']) : 0;
    } catch (Exception $e) {
        $cart_count = 0;
    }
}

// Tentukan base path
$base_path = '';
if (basename(dirname($_SERVER['PHP_SELF'])) == 'pembeli') {
    $base_path = '../../'; // Untuk file di folder modules/pembeli/
} elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'auth') {
    $base_path = '../../'; // Untuk file di folder modules/auth/
} else {
    $base_path = ''; // Untuk file di root
}
?>

<nav class="navbar" id="navbar">
    <div class="container">
        <div class="nav-inner">
           
			<!-- LOGO -->
<a href="<?php echo $base_path; ?>index.php" class="logo">
    <?php if (!empty($brand_logo) && file_exists(__DIR__ . '/../assets/images/' . $brand_logo)): ?>
    <span class="logo-icon">
	<img src="<?php echo $base_path; ?>assets/images/<?php echo $brand_logo; ?>" alt="<?php echo htmlspecialchars($brand_name); ?>" style="height:35px;max-width:120px;object-fit:contain;border-radius:10px;">
    </span>
	<?php else: ?>
    <!--<span class="logo-text"><?php echo htmlspecialchars($brand_name); ?></span>-->
	<?php endif; ?>
	<span class="logo-text">Kayu<span>manies</span></span>    
</a>
            
            <!-- NAV LINKS -->
            <ul class="nav-links" id="navLinks">
                <li><a href="<?php echo $base_path; ?>index.php">Beranda</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#categories">Kategori</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#products">Produk Unggulan</a></li>
                <li><a href="<?php echo $base_path; ?>modules/pembeli/products.php">Semua Produk</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#contact">Kontak</a></li>
            </ul>
            
            <!-- ACTIONS -->
            <div class="nav-actions">
                <!-- Cart Icon -->
                <a href="<?php echo $base_path; ?>modules/pembeli/cart.php" class="cart-icon">
                    🛒
                    <span class="cart-badge" id="cartBadge" style="<?php echo $cart_count > 0 ? '' : 'display: none;'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>
                
                <!-- User Menu / Login -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="position: relative;">
                        <button class="btn-user" onclick="toggleUserMenu()">
                            👤 <?php echo htmlspecialchars(substr($_SESSION['full_name'] ?? 'User', 0, 12)); ?> ▼
                        </button>
                        <div id="userMenu" class="user-dropdown">
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="<?php echo $base_path; ?>modules/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard Admin
                            </a>
                            <a href="<?php echo $base_path; ?>modules/admin/products.php">
                                <i class="fas fa-boxes"></i> Kelola Produk
                            </a>
                            <a href="<?php echo $base_path; ?>modules/admin/orders.php">
                                <i class="fas fa-shopping-cart"></i> Kelola Pesanan
                            </a>
                            <hr>
                            <?php elseif ($_SESSION['role'] == 'kasir'): ?>
                            <a href="<?php echo $base_path; ?>modules/kasir/pos.php">
                                <i class="fas fa-cash-register"></i> POS Kasir
                            </a>
                            <hr>
                            <?php endif; ?>
							<a href="<?php echo $base_path; ?>modules/pembeli/dashboard.php">
								<i class="fas fa-dashboard-list"></i>Dashboard
							</a>
							<a href="<?php echo $base_path; ?>modules/pembeli/chat.php">💬 Chat CS</a>
                            <a href="<?php echo $base_path; ?>modules/pembeli/orders.php">
                                <i class="fas fa-clipboard-list"></i> Pesanan Saya
                            </a>
                            <a href="<?php echo $base_path; ?>modules/pembeli/cart.php">
                                <i class="fas fa-shopping-cart"></i> Keranjang (<?php echo $cart_count; ?>)
                            </a>
                            <hr>
                            <a href="<?php echo $base_path; ?>modules/auth/logout.php" style="color: #f44336;">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>modules/auth/login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Masuk
                    </a>
                    <a href="<?php echo $base_path; ?>modules/auth/register.php" class="btn-register">
                        <i class="fas fa-user-plus"></i> Daftar
                    </a>
                <?php endif; ?>
                
                <!-- Hamburger Menu -->
                <button class="menu-toggle" onclick="toggleMenu()" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- User Dropdown Style -->
<style>
    .user-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        min-width: 220px;
        z-index: 100;
        overflow: hidden;
        animation: fadeIn 0.2s ease;
    }
    
    .user-dropdown.show {
        display: block;
    }
    
    .user-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .user-dropdown a:hover {
        background: #FFF3E0;
        color: #8B4513;
    }
    
    .user-dropdown a i {
        width: 18px;
        text-align: center;
        color: #8B4513;
    }
    
    .user-dropdown hr {
        border: none;
        border-top: 1px solid #eee;
        margin: 5px 0;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    // Toggle mobile menu
    function toggleMenu() {
        document.getElementById('navLinks').classList.toggle('active');
    }
    
    // Toggle user dropdown
    function toggleUserMenu() {
        var menu = document.getElementById('userMenu');
        menu.classList.toggle('show');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        var menu = document.getElementById('userMenu');
        if (menu && !e.target.closest('.btn-user')) {
            menu.classList.remove('show');
        }
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        var nav = document.getElementById('navbar');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
    
    // Close mobile menu when clicking nav links
    document.querySelectorAll('.nav-links a').forEach(function(link) {
        link.addEventListener('click', function() {
            document.getElementById('navLinks').classList.remove('active');
        });
    });
</script>
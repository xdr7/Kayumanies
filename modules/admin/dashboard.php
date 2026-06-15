<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Get all statistics
$stats = [];

// Total Orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetch()['total'];

// Today Orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $stmt->fetch()['total'];

// Pending Orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'");
$stats['pending_orders'] = $stmt->fetch()['total'];

// Total Revenue
$stmt = $db->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE payment_status = 'paid'");
$stats['total_revenue'] = $stmt->fetch()['total'];

// Today Revenue
$stmt = $db->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'");
$stats['today_revenue'] = $stmt->fetch()['total'];

// Total Products
$stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$stats['total_products'] = $stmt->fetch()['total'];

// Total Customers
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'pembeli' AND is_active = 1");
$stats['total_customers'] = $stmt->fetch()['total'];

// Total Categories
$stmt = $db->query("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
$stats['total_categories'] = $stmt->fetch()['total'];

// Low Stock Products
$stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE stock <= 5 AND is_active = 1");
$stats['low_stock'] = $stmt->fetch()['total'];

// Recent Orders (Last 5)
$stmt = $db->query("SELECT o.*, u.full_name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

// Recent Customers
$stmt = $db->query("SELECT * FROM users WHERE role = 'pembeli' ORDER BY created_at DESC LIMIT 5");
$recent_customers = $stmt->fetchAll();

// Monthly Sales Data (Last 6 months)
$stmt = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           COUNT(*) as total_orders,
           COALESCE(SUM(final_amount), 0) as total_sales
    FROM orders 
    WHERE payment_status = 'paid' 
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month ASC
");
$monthly_sales = $stmt->fetchAll();

// Top Products
$stmt = $db->query("
    SELECT od.product_name, COUNT(*) as total_sold, SUM(od.quantity) as total_qty
    FROM order_details od
    JOIN orders o ON od.order_id = o.id
    WHERE o.payment_status = 'paid'
    GROUP BY od.product_name
    ORDER BY total_qty DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll();

// Unread Notifications
$stmt = $db->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
$stats['unread_notifications'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<link rel="stylesheet" href="../../assets/css/admin.css">   
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
      <!-- INCLUDE SIDEBAR -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <main class="admin-main">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <small style="color: #666;"><?php echo date('l, d F Y'); ?></small>
            </div>
            <div class="admin-info">
                <span style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
            </div>
        </div>
        
        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orders"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($stats['today_orders']); ?></div>
                    <div class="stat-label">Pesanan Hari Ini</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <div class="stat-value">Rp <?php echo number_format($stats['today_revenue'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Pendapatan Hari Ini</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">Pesanan Pending</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon products"><i class="fas fa-box"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Produk Aktif</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon customers"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total_customers']; ?></div>
                    <div class="stat-label">Pelanggan</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stock"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-label">Stok Menipis</div>
                </div>
            </div>
        </div>
        
        <!-- CHARTS & TABLES -->
        <div class="content-grid">
            <div class="content-grid content-grid-3">
                <!-- MONTHLY SALES CHART -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 Penjualan Bulanan</div>
                    </div>
                    <div class="chart-bar">
                        <?php 
                        $max_sales = 0;
                        foreach ($monthly_sales as $ms) {
                            $max_sales = max($max_sales, $ms['total_sales']);
                        }
                        foreach ($monthly_sales as $ms): 
                            $height = $max_sales > 0 ? ($ms['total_sales'] / $max_sales) * 150 : 5;
                        ?>
                        <div class="chart-column">
                            <div class="chart-value">Rp <?php echo number_format($ms['total_sales']/1000000, 1); ?>M</div>
                            <div class="chart-fill" style="height: <?php echo max($height, 10); ?>px;"></div>
                            <div class="chart-label"><?php echo date('M', strtotime($ms['month'] . '-01')); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- RECENT ORDERS -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-clipboard-list"></i> Pesanan Terbaru</div>
                        <a href="orders.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td>Rp <?php echo number_format($order['final_amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['order_status'] == 'pending' ? 'warning' : ($order['order_status'] == 'completed' ? 'success' : 'info'); ?>">
                                        <?php echo strtoupper($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- TOP PRODUCTS -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-star"></i> Produk Terlaris</div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th>Terjual</th>
                                <th>Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($top_products as $tp): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($tp['product_name']); ?></td>
                                <td><?php echo $tp['total_sold']; ?>x</td>
                                <td><strong><?php echo $tp['total_qty']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="content-grid content-grid-2">
                <!-- RECENT CUSTOMERS -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-user-plus"></i> Pelanggan Baru</div>
                        <a href="users.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <?php foreach ($recent_customers as $customer): ?>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                        <div style="width: 45px; height: 45px; background: #FFF3E0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #8B4513;">
                            <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                        </div>
                        <div style="flex: 1;">
                            <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($customer['email']); ?></small>
                        </div>
                        <small style="color: #999;"><?php echo date('d M', strtotime($customer['created_at'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- QUICK STATS -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-info-circle"></i> Ringkasan</div>
                    </div>
                    <div style="display: grid; gap: 15px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total Pesanan</span>
                            <strong><?php echo number_format($stats['total_orders']); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total Revenue</span>
                            <strong>Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total Produk</span>
                            <strong><?php echo $stats['total_products']; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total Kategori</span>
                            <strong><?php echo $stats['total_categories']; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Total Pelanggan</span>
                            <strong><?php echo $stats['total_customers']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Auto-refresh data every 60 seconds
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
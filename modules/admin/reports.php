<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// ==========================================
// GET STORE INFO (DINAMIS)
// ==========================================
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$store_name = $settings['store_name'] ?? 'Kayumanies Cake Shop';
$store_address = $settings['store_address'] ?? 'Jl. Kayu Manis No. 123';
$store_phone = $settings['store_phone'] ?? '-';

// Filter
$period = $_GET['period'] ?? 'today';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build date condition
switch ($period) {
    case 'today':
        $date_condition = "DATE(created_at) = CURDATE()";
        $period_label = 'Hari Ini';
        break;
    case 'yesterday':
        $date_condition = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $period_label = 'Kemarin';
        break;
    case 'this_week':
        $date_condition = "YEARWEEK(created_at) = YEARWEEK(CURDATE())";
        $period_label = 'Minggu Ini';
        break;
    case 'this_month':
        $date_condition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $period_label = 'Bulan Ini';
        break;
    case 'custom':
        $date_condition = "DATE(created_at) BETWEEN ? AND ?";
        $period_label = date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
        break;
    default:
        $date_condition = "DATE(created_at) = CURDATE()";
        $period_label = 'Hari Ini';
}

$params = [];
if ($period == 'custom') {
    $params = [$start_date, $end_date];
}

// ==========================================
// EXPORT EXCEL
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-' . strtolower(str_replace(' ', '-', $store_name)) . '-' . date('Ymd-His') . '.xls');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr><th colspan="10" style="text-align:center;font-size:16px;background:#8B4513;color:white;">' . strtoupper(htmlspecialchars($store_name)) . '</th></tr>';
    echo '<tr><th colspan="10" style="text-align:center;">' . htmlspecialchars($store_address) . ' | Telp: ' . htmlspecialchars($store_phone) . '</th></tr>';
    echo '<tr><th colspan="10" style="text-align:center;">Periode: ' . $period_label . ' | Dicetak: ' . date('d M Y H:i') . ' | Oleh: ' . htmlspecialchars($_SESSION['full_name']) . '</th></tr>';
    echo '<tr><th colspan="10"></th></tr>';
    echo '<tr style="background:#f0f0f0;"><th>No</th><th>No. Pesanan</th><th>Pelanggan</th><th>Telepon</th><th>Total</th><th>Diskon</th><th>Final</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th></tr>';
    
    $all_sql = "SELECT o.*, u.full_name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE " . str_replace('created_at', 'o.created_at', $date_condition) . " ORDER BY o.created_at DESC";
    $all_stmt = $db->prepare($all_sql);    
    $export_params = [];
    if ($period == 'custom') {
        $export_params = [$start_date, $end_date];
    }
    $all_stmt->execute($export_params);
    $all_transactions = $all_stmt->fetchAll();
    
    $no = 1; $total_final = 0;
    foreach ($all_transactions as $t) {
        $total_final += $t['final_amount'];
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . $t['order_number'] . '</td>';
        echo '<td>' . htmlspecialchars($t['customer_name'] ?? $t['customer_name'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($t['customer_phone'] ?? '-') . '</td>';
        echo '<td>Rp ' . number_format($t['total_amount'], 0, ',', '.') . '</td>';
        echo '<td>Rp ' . number_format($t['discount_amount'], 0, ',', '.') . '</td>';
        echo '<td><strong>Rp ' . number_format($t['final_amount'], 0, ',', '.') . '</strong></td>';
        echo '<td>' . strtoupper($t['payment_method'] ?? '-') . '</td>';
        echo '<td>' . strtoupper($t['order_status']) . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($t['created_at'])) . '</td>';
        echo '</tr>';
    }
    echo '<tr style="background:#f0f0f0;font-weight:bold;"><td colspan="6" style="text-align:right;">TOTAL</td><td><strong>Rp ' . number_format($total_final, 0, ',', '.') . '</strong></td><td colspan="3"></td></tr>';
    echo '<tr><td colspan="10" style="text-align:center;font-size:10px;">© ' . date('Y') . ' ' . htmlspecialchars($store_name) . ' - Generated by System</td></tr>';
    echo '</table></body></html>';
    exit;
}

// ==========================================
// SALES REPORT
// ==========================================
$sql = "SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(discount_amount), 0) as total_discount,
            COALESCE(SUM(final_amount), 0) as total_revenue,
            COALESCE(AVG(final_amount), 0) as avg_order_value
        FROM orders WHERE {$date_condition} AND payment_status = 'paid'";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetch();

// Orders by Status
$sql = "SELECT order_status, COUNT(*) as count FROM orders WHERE {$date_condition} GROUP BY order_status";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$order_statuses = $stmt->fetchAll();

// Payment Methods
$sql = "SELECT payment_method, COUNT(*) as count, COALESCE(SUM(final_amount), 0) as total FROM orders WHERE {$date_condition} GROUP BY payment_method";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payment_methods = $stmt->fetchAll();

// Top Products
$sql = "SELECT od.product_name, SUM(od.quantity) as qty, COALESCE(SUM(od.subtotal), 0) as total 
        FROM order_details od JOIN orders o ON od.order_id = o.id 
        WHERE {$date_condition} GROUP BY od.product_name ORDER BY qty DESC LIMIT 10";
$sql = str_replace('created_at', 'o.created_at', $sql);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$top_products = $stmt->fetchAll();

// Daily Sales
$sql = "SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(final_amount), 0) as revenue
        FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND payment_status = 'paid'
        GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt = $db->query($sql);
$daily_sales = $stmt->fetchAll();

// Customer Stats
$sql = "SELECT COUNT(DISTINCT user_id) as total_customers FROM orders WHERE {$date_condition}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetch()['total_customers'];

// All Transactions (Paginated)
$count_sql = "SELECT COUNT(*) as total FROM orders WHERE {$date_condition}";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_transactions = $stmt->fetch()['total'];
$total_pages = ceil($total_transactions / $limit);

$sql = "SELECT o.*, u.full_name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id 
        WHERE " . str_replace('created_at', 'o.created_at', $date_condition) . " ORDER BY o.created_at DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Single receipt print
$print_single = isset($_GET['print_single']) ? intval($_GET['print_single']) : 0;
$bulk_print = isset($_GET['print']) && $_GET['print'] == 'bulk';

// Query params untuk link
$query_string = http_build_query(array_filter([
    'period' => $period,
    'start_date' => $start_date,
    'end_date' => $end_date
]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - <?php echo htmlspecialchars($store_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
    
    <style>
        .period-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
        .period-btn { padding: 10px 20px; border: 2px solid #e0e0e0; background: white; border-radius: 25px; cursor: pointer; text-decoration: none; color: #333; font-weight: 600; font-size: 13px; transition: all 0.3s ease; }
        .period-btn:hover, .period-btn.active { background: #8B4513; color: white; border-color: #8B4513; }
        .custom-date { display: inline-flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .custom-date input { padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit; }
        
        .chart-container { display: flex; align-items: flex-end; gap: 10px; height: 200px; padding: 10px 0; }
        .chart-bar-item { flex: 1; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .chart-bar-fill { width: 100%; background: linear-gradient(to top, #8B4513, #A0522D); border-radius: 5px 5px 0 0; min-height: 5px; }
        .chart-bar-value { font-size: 10px; font-weight: 700; color: #8B4513; }
        .chart-bar-label { font-size: 10px; color: #666; font-weight: 600; }
        
        .progress-bar { width: 100%; height: 8px; background: #f0f0f0; border-radius: 5px; overflow: hidden; margin-top: 5px; }
        .progress-fill { height: 100%; background: #8B4513; border-radius: 5px; }
        
        .pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
        .page-link { padding: 6px 12px; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #333; font-size: 12px; }
        .page-link.active { background: #8B4513; color: white; border-color: #8B4513; }
        
        .receipt-print { page-break-after: always; padding: 15px; max-width: 380px; margin: 0 auto 15px; border: 1px dashed #ccc; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.6; }
        .receipt-print:last-child { page-break-after: avoid; }
        .receipt-print h3 { text-align: center; font-size: 13px; margin-bottom: 2px; }
        .receipt-print .store-info { text-align: center; font-size: 9px; color: #555; }
        .receipt-print .divider { border-top: 1px dashed #999; margin: 6px 0; }
        .receipt-print .total-row { font-weight: bold; font-size: 13px; text-align: right; }
        
        @media print {
            .admin-sidebar, .top-bar, .btn, .period-filters, .pagination, .no-print,
            .chart-container, .progress-bar, .card-header, .stats-grid, .content-grid { display: none !important; }
            .receipt-print { border: none; padding: 8px; page-break-after: always; }
            body { background: white; }
        }
        
        @media (max-width: 968px) {
            .chart-container { height: 150px; }
            .period-btn { padding: 8px 15px; font-size: 12px; }
        }
    </style>
</head>
<body>
    
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
       <?php if ($bulk_print): ?>
        <!-- ========== BULK PRINT MODE ========== -->
        <?php 
        $bulk_params = [];
        if ($period == 'custom') {
            $bulk_params = [$start_date, $end_date];
        }
        $bulk_sql = "SELECT o.*, u.full_name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE " . str_replace('created_at', 'o.created_at', $date_condition) . " ORDER BY o.created_at ASC";
        $bulk_stmt = $db->prepare($bulk_sql);
        $bulk_stmt->execute($bulk_params);
        $bulk_orders = $bulk_stmt->fetchAll();
        ?>
        
        <?php foreach ($bulk_orders as $bo): 
            $item_sql = "SELECT * FROM order_details WHERE order_id = ?";
            $item_stmt = $db->prepare($item_sql);
            $item_stmt->execute([$bo['id']]);
            $bo_items = $item_stmt->fetchAll();
        ?>
        <div class="receipt-print">
            <h3><?php echo htmlspecialchars($store_name); ?></h3>
            <p class="store-info"><?php echo htmlspecialchars($store_address); ?> | <?php echo htmlspecialchars($store_phone); ?></p>
            <p class="store-info">
                <strong>#<?php echo $bo['order_number']; ?></strong><br>
                <?php echo date('d/m/Y H:i', strtotime($bo['created_at'])); ?><br>
                Pelanggan: <?php echo htmlspecialchars($bo['customer_name']); ?>
            </p>
            <div class="divider"></div>
            <?php foreach ($bo_items as $bi): ?>
            <div style="display:flex;justify-content:space-between;">
                <span><?php echo htmlspecialchars($bi['product_name']); ?></span>
                <span><?php echo $bi['quantity']; ?>x</span>
                <span>Rp <?php echo number_format($bi['subtotal'],0,',','.'); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="divider"></div>
            <?php if ($bo['discount_amount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;"><span>Diskon</span><span>-Rp <?php echo number_format($bo['discount_amount'],0,',','.'); ?></span></div>
            <?php endif; ?>
            <div class="total-row">TOTAL: Rp <?php echo number_format($bo['final_amount'],0,',','.'); ?></div>
            <p class="store-info">Metode: <?php echo strtoupper($bo['payment_method']); ?> | Status: <?php echo strtoupper($bo['payment_status']); ?></p>
        </div>
        <?php endforeach; ?>
        
        <div style="text-align:center;padding:20px;" class="no-print">
            <p>Total: <?php echo count($bulk_orders); ?> struk</p>
            <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Cetak Semua</button>
            <a href="reports.php?<?php echo $query_string; ?>" class="btn btn-secondary">← Kembali</a>
        </div>
        
        <?php elseif ($print_single > 0): ?>
        <!-- ========== SINGLE RECEIPT PRINT ========== -->
        <?php 
        $single_sql = "SELECT o.*, u.full_name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?";
        $single_stmt = $db->prepare($single_sql);
        $single_stmt->execute([$print_single]);
        $so = $single_stmt->fetch();
        
        if ($so):
            $item_sql = "SELECT * FROM order_details WHERE order_id = ?";
            $item_stmt = $db->prepare($item_sql);
            $item_stmt->execute([$so['id']]);
            $so_items = $item_stmt->fetchAll();
        ?>
        <div class="receipt-print" style="border:none;max-width:400px;">
            <h3><?php echo htmlspecialchars($store_name); ?></h3>
            <p class="store-info"><?php echo htmlspecialchars($store_address); ?> | <?php echo htmlspecialchars($store_phone); ?></p>
            <p class="store-info">
                <strong>#<?php echo $so['order_number']; ?></strong><br>
                <?php echo date('d/m/Y H:i', strtotime($so['created_at'])); ?><br>
                Pelanggan: <?php echo htmlspecialchars($so['customer_name']); ?><br>
                Telp: <?php echo htmlspecialchars($so['customer_phone'] ?? '-'); ?>
            </p>
            <div class="divider"></div>
            <?php foreach ($so_items as $si): ?>
            <div style="display:flex;justify-content:space-between;">
                <span><?php echo htmlspecialchars($si['product_name']); ?></span>
                <span><?php echo $si['quantity']; ?>x</span>
                <span>Rp <?php echo number_format($si['subtotal'],0,',','.'); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="divider"></div>
            <?php if ($so['discount_amount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;"><span>Diskon</span><span>-Rp <?php echo number_format($so['discount_amount'],0,',','.'); ?></span></div>
            <?php endif; ?>
            <div class="total-row">TOTAL: Rp <?php echo number_format($so['final_amount'],0,',','.'); ?></div>
            <p class="store-info">Metode: <?php echo strtoupper($so['payment_method']); ?> | Status: <?php echo strtoupper($so['order_status']); ?></p>
            <?php if ($so['notes']): ?>
            <p class="store-info">Catatan: <?php echo htmlspecialchars($so['notes']); ?></p>
            <?php endif; ?>
        </div>
        
        <div style="text-align:center;padding:20px;" class="no-print">
            <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
            <a href="reports.php?<?php echo $query_string; ?>" class="btn btn-secondary">← Kembali</a>
        </div>
        <?php else: ?>
        <p style="text-align:center;">Pesanan tidak ditemukan.</p>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- ========== NORMAL VIEW ========== -->
        
        <!-- TOP BAR -->
        <div class="top-bar no-print">
            <div class="top-bar-left">
                <h1>📈 Laporan Penjualan</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Laporan</div>
            </div>
            <div class="top-bar-right d-flex gap-1">
                <a href="?<?php echo $query_string; ?>&export=excel" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="?<?php echo $query_string; ?>&print=bulk" class="btn btn-sm btn-info">
                    <i class="fas fa-print"></i> Cetak Semua Struk
                </a>
                <button class="btn btn-sm" style="background:#333;color:white;" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Halaman
                </button>
            </div>
        </div>
        
        <!-- PERIOD FILTER -->
        <div class="card no-print">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-calendar"></i> Periode: <?php echo $period_label; ?></div>
            </div>
            <div class="card-body">
                <div class="period-filters">
                    <a href="?period=today" class="period-btn <?php echo $period == 'today' ? 'active' : ''; ?>">Hari Ini</a>
                    <a href="?period=yesterday" class="period-btn <?php echo $period == 'yesterday' ? 'active' : ''; ?>">Kemarin</a>
                    <a href="?period=this_week" class="period-btn <?php echo $period == 'this_week' ? 'active' : ''; ?>">Minggu Ini</a>
                    <a href="?period=this_month" class="period-btn <?php echo $period == 'this_month' ? 'active' : ''; ?>">Bulan Ini</a>
                    <a href="?period=custom" class="period-btn <?php echo $period == 'custom' ? 'active' : ''; ?>">Custom</a>
                </div>
                <?php if ($period == 'custom'): ?>
                <form class="custom-date" style="margin-top:15px;">
                    <input type="hidden" name="period" value="custom">
                    <label>Dari:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    <label>Sampai:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- SALES SUMMARY -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon stat-icon-primary"><i class="fas fa-shopping-cart"></i></div><div class="stat-info"><div class="stat-value"><?php echo number_format($sales['total_orders']); ?></div><div class="stat-label">Total Pesanan</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-success"><i class="fas fa-money-bill-wave"></i></div><div class="stat-info"><div class="stat-value">Rp <?php echo number_format($sales['total_sales'], 0, ',', '.'); ?></div><div class="stat-label">Total Penjualan</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-warning"><i class="fas fa-tags"></i></div><div class="stat-info"><div class="stat-value">Rp <?php echo number_format($sales['total_discount'], 0, ',', '.'); ?></div><div class="stat-label">Total Diskon</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-success"><i class="fas fa-wallet"></i></div><div class="stat-info"><div class="stat-value">Rp <?php echo number_format($sales['total_revenue'], 0, ',', '.'); ?></div><div class="stat-label">Pendapatan Bersih</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-purple"><i class="fas fa-calculator"></i></div><div class="stat-info"><div class="stat-value">Rp <?php echo number_format($sales['avg_order_value'], 0, ',', '.'); ?></div><div class="stat-label">Rata-rata Order</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-primary"><i class="fas fa-users"></i></div><div class="stat-info"><div class="stat-value"><?php echo number_format($customers); ?></div><div class="stat-label">Pelanggan Unik</div></div></div>
        </div>
        
        <!-- CHARTS & TABLES -->
        <div class="content-grid content-grid-2">
            
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Penjualan 7 Hari Terakhir</div></div>
                <div class="card-body">
                    <?php if (empty($daily_sales)): ?>
                    <div class="empty-state"><i class="fas fa-chart-line"></i><p>Belum ada data</p></div>
                    <?php else: ?>
                    <div class="chart-container">
                        <?php $max_revenue = 0; foreach ($daily_sales as $ds) $max_revenue = max($max_revenue, $ds['revenue']); ?>
                        <?php foreach ($daily_sales as $ds): $height = $max_revenue > 0 ? ($ds['revenue'] / $max_revenue) * 150 : 5; ?>
                        <div class="chart-bar-item">
                            <div class="chart-bar-value">Rp<?php echo round($ds['revenue']/1000); ?>k</div>
                            <div class="chart-bar-fill" style="height: <?php echo max($height, 8); ?>px;"></div>
                            <div class="chart-bar-label"><?php echo date('d/m', strtotime($ds['date'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-tasks"></i> Status Pesanan</div></div>
                <div class="card-body">
                    <?php if (empty($order_statuses)): ?>
                    <div class="empty-state"><p>Belum ada data</p></div>
                    <?php else: ?>
                    <?php foreach ($order_statuses as $status): $percent = $sales['total_orders'] > 0 ? ($status['count'] / $sales['total_orders']) * 100 : 0; ?>
                    <div class="mb-1">
                        <div class="d-flex justify-between" style="margin-bottom:5px;">
                            <span style="font-size:13px;text-transform:capitalize;"><?php echo $status['order_status']; ?></span>
                            <strong style="font-size:13px;"><?php echo $status['count']; ?></strong>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $percent; ?>%;"></div></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-credit-card"></i> Metode Pembayaran</div></div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead><tr><th>Metode</th><th>Jumlah</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php if (empty($payment_methods)): ?>
                                <tr><td colspan="3" style="text-align:center;padding:30px;color:#999;">Belum ada data</td></tr>
                                <?php else: ?>
                                <?php foreach ($payment_methods as $pm): ?>
                                <tr><td style="text-transform:uppercase;font-weight:600;"><?php echo $pm['payment_method']; ?></td><td><?php echo $pm['count']; ?>x</td><td><strong>Rp <?php echo number_format($pm['total'], 0, ',', '.'); ?></strong></td></tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><div class="card-title"><i class="fas fa-trophy"></i> Produk Terlaris</div></div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead><tr><th width="40">#</th><th>Produk</th><th width="70">Qty</th><th width="130">Total</th></tr></thead>
                            <tbody>
                                <?php if (empty($top_products)): ?>
                                <tr><td colspan="4" style="text-align:center;padding:30px;color:#999;">Belum ada data</td></tr>
                                <?php else: ?>
                                <?php $no = 1; foreach ($top_products as $tp): ?>
                                <tr>
                                    <td><?php echo $no == 1 ? '🥇' : ($no == 2 ? '🥈' : ($no == 3 ? '🥉' : $no)); ?></td>
                                    <td><strong><?php echo htmlspecialchars($tp['product_name']); ?></strong></td>
                                    <td><?php echo $tp['qty']; ?></td>
                                    <td><strong>Rp <?php echo number_format($tp['total'], 0, ',', '.'); ?></strong></td>
                                </tr>
                                <?php $no++; endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- TRANSACTION LIST -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Daftar Transaksi</div>
                <span class="text-sm text-gray">Total: <?php echo $total_transactions; ?> transaksi</span>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>No</th><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th><th width="80">Cetak</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr><td colspan="7" class="text-center text-gray" style="padding:30px;">Tidak ada transaksi</td></tr>
                            <?php else: ?>
                            <?php $no = $offset + 1; foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo $t['order_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($t['customer_name'] ?? '-'); ?></td>
                                <td>Rp <?php echo number_format($t['final_amount'], 0, ',', '.'); ?></td>
                                <td><span class="badge badge-<?php echo $t['order_status']=='completed'?'success':($t['order_status']=='pending'?'warning':'info'); ?>"><?php echo strtoupper($t['order_status']); ?></span></td>
                                <td><small><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></small></td>
                                <td>
                                    <a href="?<?php echo $query_string; ?>&print_single=<?php echo $t['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-print"></i> Struk
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php 
                    $pq = http_build_query(array_filter(['period' => $period, 'start_date' => $start_date, 'end_date' => $end_date]));
                    for ($i = 1; $i <= $total_pages; $i++): 
                    ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo $pq; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endif; ?>
        
    </main>
    
    <?php if ($bulk_print || $print_single): ?>
    <script>window.onload = function() { window.print(); }</script>
    <?php endif; ?>
    
</body>
</html>
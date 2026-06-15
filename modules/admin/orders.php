<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['order_status'];
    
    $stmt = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    $stmt = $db->prepare("SELECT user_id, order_number FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, 'Status Pesanan Diupdate', ?, 'order', ?)");
    $stmt->execute([$order['user_id'], "Pesanan #{$order['order_number']} status: " . strtoupper($new_status), "order-detail.php?id={$order_id}"]);
}

// Handle payment verification
if (isset($_POST['verify_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    
    $stmt = $db->prepare("UPDATE payments SET payment_status = 'verified', verified_by = ?, verified_at = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $payment_id]);
    
    $stmt = $db->prepare("SELECT order_id FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$payment['order_id']]);
}

// Filters
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $where .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

if (!empty($payment_filter)) {
    $where .= " AND o.payment_status = ?";
    $params[] = $payment_filter;
}

if (!empty($search)) {
    $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// Total orders
$countSql = "SELECT COUNT(*) as total FROM orders o {$where}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get orders
$sql = "SELECT o.*, u.full_name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id {$where} ORDER BY o.created_at DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>🛒 Manajemen Pesanan</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Pesanan</div>
            </div>
            <div class="top-bar-right">
                <span class="text-sm text-gray">Total: <?php echo $total; ?> pesanan</span>
            </div>
        </div>
        
        <!-- FILTER -->
        <div class="card">
            <form class="filter-bar" method="GET">
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                
                <select name="payment">
                    <option value="">Semua Pembayaran</option>
                    <option value="pending" <?php echo $payment_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $payment_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="failed" <?php echo $payment_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
                
                <input type="text" name="search" placeholder="Cari nomor pesanan..." value="<?php echo htmlspecialchars($search); ?>">
                
                <button type="submit" class="btn btn-info btn-sm">Filter</button>
                <a href="orders.php" class="btn btn-sm btn-secondary">Reset</a>
            </form>
        </div>
        
        <!-- ORDERS TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Daftar Pesanan</div>
                <span class="text-sm text-muted"><?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> dari <?php echo $total; ?></span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-receipt"></i><p>Tidak ada pesanan</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['order_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>Rp <?php echo number_format($order['final_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['order_status']; ?>">
                                    <?php echo strtoupper($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $order['payment_status'] == 'paid' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo strtoupper($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="order_status" onchange="this.form.submit()" class="form-control" style="width:auto;padding:4px 8px;font-size:11px;display:inline-block;">
                                            <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="ready" <?php echo $order['order_status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="completed" <?php echo $order['order_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php 
                $pq = http_build_query(array_filter(['status' => $status_filter, 'payment' => $payment_filter, 'search' => $search]));
                for ($i = 1; $i <= $total_pages; $i++): 
                ?>
                <a href="?page=<?php echo $i; ?>&<?php echo $pq; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </main>
</body>
</html>
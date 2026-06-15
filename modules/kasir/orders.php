<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$payment_filter = $_GET['payment'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
// HAPUS karakter # dari search
$search = str_replace('#', '', $search);
$date = $_GET['date'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($payment_filter)) { $where[] = "o.payment_status = ?"; $params[] = $payment_filter; }
if (!empty($status_filter)) { $where[] = "o.order_status = ?"; $params[] = $status_filter; }
if (!empty($search)) { 
    $where[] = "o.order_number LIKE ?"; 
    $params[] = "%{$search}%";
}
if (!empty($date)) { $where[] = "DATE(o.created_at) = ?"; $params[] = $date; }

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders o {$where_clause}");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$stmt = $db->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id {$where_clause} ORDER BY o.created_at DESC LIMIT {$limit} OFFSET {$offset}");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$flash = $_SESSION['kasir_flash'] ?? '';
$flash_type = $_SESSION['kasir_flash_type'] ?? '';
unset($_SESSION['kasir_flash'], $_SESSION['kasir_flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Pesanan - Kasir</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/kasir.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="kasir-main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📋 Semua Pesanan</h1>
                <div class="date">Total: <?php echo $total; ?> pesanan</div>
            </div>
            <div class="top-bar-right">
                <a href="dashboard.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>
        
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash_type == 'success' ? 'success' : 'error'; ?>"><?php echo $flash; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-filter"></i> Filter Pesanan</div>
            </div>
            <form class="filter-bar" method="GET">
                <input type="date" name="date" value="<?php echo $date; ?>">
                <select name="payment">
                    <option value="">Semua Pembayaran</option>
                    <option value="pending" <?php echo $payment_filter == 'pending' ? 'selected' : ''; ?>>Menunggu Bayar</option>
                    <option value="paid" <?php echo $payment_filter == 'paid' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                </select>
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <input type="text" name="search" placeholder="🔍 Cari nomor pesanan (tanpa #)..." value="<?php echo htmlspecialchars(str_replace('#', '', $search)); ?>">
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                <?php if ($payment_filter || $status_filter || $search || $date): ?>
                <a href="orders.php" class="btn btn-sm btn-gray">Reset</a>
                <?php endif; ?>
            </form>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Bayar</th><th>Status</th><th>Ambil</th><th>Waktu</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-receipt"></i><p>Tidak ada pesanan</p></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($orders as $o): 
                            $dot = ['pending'=>'warning','processing'=>'purple','ready'=>'success','completed'=>'gray','cancelled'=>'red'][$o['order_status']] ?? 'gray';
                            $pay_badge = $o['payment_status'] == 'paid' ? 'badge-success' : 'badge-warning';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $o['order_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?><br><small class="text-light"><?php echo htmlspecialchars($o['customer_phone'] ?? '-'); ?></small></td>
                            <td><strong>Rp <?php echo number_format($o['final_amount'],0,',','.'); ?></strong></td>
                            <td><span class="badge <?php echo $pay_badge; ?>"><?php echo $o['payment_status'] == 'paid' ? 'Lunas' : 'Belum'; ?></span></td>
                            <td><span class="status-dot dot-<?php echo $dot; ?>"></span><?php echo ucfirst($o['order_status']); ?></td>
                            <td><?php echo $o['pickup_date'] ? date('d/m', strtotime($o['pickup_date'])).' '.$o['pickup_time'] : '-'; ?></td>
                            <td class="text-xs text-light"><?php echo date('d/m H:i', strtotime($o['created_at'])); ?></td>
                            <td><a href="order-detail.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-info">Detail</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php 
                $q = http_build_query(array_filter(['payment'=>$payment_filter,'status'=>$status_filter,'search'=>$search,'date'=>$date]));
                for ($i=1; $i<=$total_pages; $i++): 
                ?>
                <a href="?page=<?php echo $i; ?>&<?php echo $q; ?>" class="page-link <?php echo $page==$i?'active':''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
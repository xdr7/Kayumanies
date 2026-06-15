<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Get all products
$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.name ASC");
$products = $stmt->fetchAll();

// Handle save HPP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calculate'])) {
    $product_id = intval($_POST['product_id']);
    $material_cost = floatval($_POST['material_cost'] ?? 0);
    $labor_cost = floatval($_POST['labor_cost'] ?? 0);
    $overhead_cost = floatval($_POST['overhead_cost'] ?? 0);
    $packaging_cost = floatval($_POST['packaging_cost'] ?? 0);
    $margin_percent = floatval($_POST['margin_percent'] ?? 30);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id > 0 && $quantity > 0) {
        $total_cost = $material_cost + $labor_cost + $overhead_cost + $packaging_cost;
        $hpp_per_unit = $total_cost / $quantity;
        $margin_amount = $hpp_per_unit * ($margin_percent / 100);
        $selling_price = $hpp_per_unit + $margin_amount;
        
        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        // Save to history
        $stmt = $db->prepare("INSERT INTO hpp_calculations (product_id, product_name, material_cost, labor_cost, overhead_cost, packaging_cost, total_cost, quantity, hpp_per_unit, margin_percent, selling_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $product['name'], $material_cost, $labor_cost, $overhead_cost, $packaging_cost, $total_cost, $quantity, $hpp_per_unit, $margin_percent, $selling_price]);
        
        $calculated = true;
    }
}

// Get history
$stmt = $db->query("SELECT * FROM hpp_calculations ORDER BY created_at DESC LIMIT 10");
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator HPP - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>🧮 Kalkulator HPP</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / HPP</div>
            </div>
        </div>
        
        <div class="content-grid content-grid-2">
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-calculator"></i> Hitung HPP</div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="calculate" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Pilih Produk</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?> (Rp <?php echo number_format($p['price'],0,',','.'); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Biaya Bahan Baku (Rp)</label>
                                <input type="number" name="material_cost" class="form-control" value="<?php echo $_POST['material_cost'] ?? ''; ?>" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Biaya Tenaga Kerja (Rp)</label>
                                <input type="number" name="labor_cost" class="form-control" value="<?php echo $_POST['labor_cost'] ?? ''; ?>" placeholder="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Biaya Overhead (Rp)</label>
                                <input type="number" name="overhead_cost" class="form-control" value="<?php echo $_POST['overhead_cost'] ?? ''; ?>" placeholder="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Biaya Kemasan (Rp)</label>
                                <input type="number" name="packaging_cost" class="form-control" value="<?php echo $_POST['packaging_cost'] ?? ''; ?>" placeholder="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Jumlah Produk (Qty)</label>
                                <input type="number" name="quantity" class="form-control" value="<?php echo $_POST['quantity'] ?? '1'; ?>" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Margin Keuntungan (%)</label>
                                <input type="number" name="margin_percent" class="form-control" value="<?php echo $_POST['margin_percent'] ?? '30'; ?>" min="0" max="100" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full"><i class="fas fa-calculator"></i> Hitung HPP</button>
                    </form>
                </div>
            </div>
            
            <div>
                <?php if (isset($calculated) && $calculated): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-pie"></i> Hasil: <?php echo htmlspecialchars($product['name']); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <tr><td>Biaya Bahan Baku</td><td class="text-right"><strong>Rp <?php echo number_format($material_cost,0,',','.'); ?></strong></td></tr>
                                <tr><td>Biaya Tenaga Kerja</td><td class="text-right">Rp <?php echo number_format($labor_cost,0,',','.'); ?></td></tr>
                                <tr><td>Biaya Overhead</td><td class="text-right">Rp <?php echo number_format($overhead_cost,0,',','.'); ?></td></tr>
                                <tr><td>Biaya Kemasan</td><td class="text-right">Rp <?php echo number_format($packaging_cost,0,',','.'); ?></td></tr>
                                <tr><td colspan="2"><hr></td></tr>
                                <tr><td><strong>Total Biaya</strong></td><td class="text-right"><strong>Rp <?php echo number_format($total_cost,0,',','.'); ?></strong></td></tr>
                                <tr><td>Jumlah Produk</td><td class="text-right"><?php echo $quantity; ?> unit</td></tr>
                                <tr style="background:#FFF3E0;"><td style="padding:8px;"><strong>HPP per Unit</strong></td><td class="text-right"><strong style="font-size:18px;color:#8B4513;">Rp <?php echo number_format($hpp_per_unit,0,',','.'); ?></strong></td></tr>
                                <tr><td>Margin (<?php echo $margin_percent; ?>%)</td><td class="text-right">Rp <?php echo number_format($margin_amount,0,',','.'); ?></td></tr>
                                <tr style="background:#E8F5E9;"><td style="padding:8px;"><strong>Harga Jual</strong></td><td class="text-right"><strong style="font-size:20px;color:#2E7D32;">Rp <?php echo number_format($selling_price,0,',','.'); ?></strong></td></tr>
                            </table>
                        </div>
                        
                        <div class="alert <?php echo $selling_price > $product['price'] ? 'alert-danger' : 'alert-success'; ?> mt-2">
                            <i class="fas fa-<?php echo $selling_price > $product['price'] ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                            <span>
                                Harga saat ini: <strong>Rp <?php echo number_format($product['price'],0,',','.'); ?></strong> |
                                HPP + Margin: <strong>Rp <?php echo number_format($selling_price,0,',','.'); ?></strong>
                                <?php if ($selling_price > $product['price']): ?>
                                <br>⚠️ HPP lebih tinggi! Naikkan harga atau turunkan biaya.
                                <?php else: ?>
                                <br>✅ Harga jual sudah di atas HPP.
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Info</div></div>
                    <div class="card-body">
                        <p class="text-gray">Pilih produk dan masukkan biaya untuk menghitung HPP (Harga Pokok Produksi).</p>
                        <hr>
                        <small class="text-muted">
                            <strong>Rumus:</strong><br>
                            Total Biaya = Bahan + Tenaga + Overhead + Kemasan<br>
                            HPP/Unit = Total ÷ Jumlah<br>
                            Harga Jual = HPP + (HPP × Margin%)
                        </small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- HISTORY -->
            <?php if (!empty($history)): ?>
            <div class="card" style="grid-column:1/-1;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-history"></i> Riwayat Kalkulasi</div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr><th>Produk</th><th>Qty</th><th>Total Biaya</th><th>HPP/Unit</th><th>Margin</th><th>Harga Jual</th><th>Waktu</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($h['product_name']); ?></strong></td>
                                    <td><?php echo $h['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($h['total_cost'],0,',','.'); ?></td>
                                    <td>Rp <?php echo number_format($h['hpp_per_unit'],0,',','.'); ?></td>
                                    <td><?php echo $h['margin_percent']; ?>%</td>
                                    <td><strong>Rp <?php echo number_format($h['selling_price'],0,',','.'); ?></strong></td>
                                    <td><small><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </main>
</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add' || $action == 'edit') {
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $discount_type = $_POST['discount_type'];
        $discount_value = floatval($_POST['discount_value']);
        $min_purchase = floatval($_POST['min_purchase'] ?? 0);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($action == 'add') {
            // Insert tanpa max_discount
            $stmt = $db->prepare("INSERT INTO promos (code, name, description, discount_type, discount_value, min_purchase, start_date, end_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $min_purchase, $start_date, $end_date, $usage_limit, $is_active]);
            $msg = 'Promo berhasil ditambahkan!';
        } else {
            $id = intval($_POST['id']);
            // Update tanpa max_discount
            $stmt = $db->prepare("UPDATE promos SET code=?, name=?, description=?, discount_type=?, discount_value=?, min_purchase=?, start_date=?, end_date=?, usage_limit=?, is_active=? WHERE id=?");
            $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $min_purchase, $start_date, $end_date, $usage_limit, $is_active, $id]);
            $msg = 'Promo berhasil diupdate!';
        }
        
        $_SESSION['flash_msg'] = $msg;
        $_SESSION['flash_type'] = 'success';
        header('Location: promos.php');
        exit;
    }
    
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("DELETE FROM promos WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_msg'] = 'Promo berhasil dihapus!';
        $_SESSION['flash_type'] = 'warning';
        header('Location: promos.php');
        exit;
    }
}

// Get flash message
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Get all promos
$stmt = $db->query("SELECT * FROM promos ORDER BY created_at DESC");
$promos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Promo - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <main class="admin-main">
        
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>🏷️ Manajemen Promo</h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Home</a> / Promo
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Tambah Promo Baru
                </button>
            </div>
        </div>
        
        <!-- FLASH MESSAGE -->
        <?php if ($flash_msg): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <span><?php echo htmlspecialchars($flash_msg); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- PROMOS TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-tags"></i> Daftar Promo
                </div>
                <span class="text-sm text-muted">Total: <?php echo count($promos); ?> promo</span>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="120">Kode</th>
                                <th>Nama Promo</th>
                                <th width="90">Tipe</th>
                                <th width="120">Nilai Diskon</th>
                                <th width="120">Min. Pembelian</th>
                                <th width="150">Periode</th>
                                <th width="90">Penggunaan</th>
                                <th width="100">Status</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($promos)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-ticket-alt"></i>
                                        <h3>Belum Ada Promo</h3>
                                        <p>Klik tombol "Tambah Promo Baru" untuk membuat promo</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($promos as $promo): 
                                $is_expired = strtotime($promo['end_date']) < time();
                                
                                // Status badge
                                if (!$promo['is_active']) {
                                    $badge_status = 'badge-inactive';
                                    $status_text = 'Nonaktif';
                                    $status_icon = 'fa-ban';
                                } elseif ($is_expired) {
                                    $badge_status = 'badge-danger';
                                    $status_text = 'Kadaluarsa';
                                    $status_icon = 'fa-clock';
                                } else {
                                    $badge_status = 'badge-active';
                                    $status_text = 'Aktif';
                                    $status_icon = 'fa-check-circle';
                                }
                                
                                // Type badge
                                $badge_type = $promo['discount_type'] == 'percentage' ? 'badge-info' : 'badge-purple';
                                $type_text = $promo['discount_type'] == 'percentage' ? 'Persen %' : 'Nominal Rp';
                            ?>
                            <tr>
                                <td>
                                    <span style="font-family: 'Courier New', monospace; font-weight: 700; font-size: 14px; color: #8B4513; background: #FFF3E0; padding: 3px 10px; border-radius: 5px; letter-spacing: 1px;">
                                        <?php echo htmlspecialchars($promo['code']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($promo['name']); ?></strong>
                                    <?php if (!empty($promo['description'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($promo['description'], 0, 60)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_type; ?>">
                                        <?php echo $type_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($promo['discount_type'] == 'percentage'): ?>
                                        <strong style="font-size: 15px;"><?php echo $promo['discount_value']; ?>%</strong>
                                    <?php else: ?>
                                        <strong>Rp <?php echo number_format($promo['discount_value'], 0, ',', '.'); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($promo['min_purchase'] > 0): ?>
                                    Rp <?php echo number_format($promo['min_purchase'], 0, ',', '.'); ?>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($promo['start_date'])); ?></small>
                                    <br>
                                    <small><?php echo date('d/m/Y', strtotime($promo['end_date'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $promo['usage_count']; ?></strong>
                                    <?php if ($promo['usage_limit']): ?>
                                    <span class="text-muted">/ <?php echo $promo['usage_limit']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_status; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info" onclick='editPromo(<?php echo json_encode($promo); ?>)' title="Edit Promo">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus promo <?php echo htmlspecialchars(addslashes($promo['code'])); ?>? Tindakan ini tidak dapat dibatalkan.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus Promo">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </main>
    
    <!-- ========== MODAL ADD/EDIT PROMO ========== -->
    <div class="modal" id="promoModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Tambah Promo Baru</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form method="POST" id="promoForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="promoId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Kode Promo <span class="required">*</span>
                            </label>
                            <input type="text" name="code" id="code" class="form-control" 
                                   placeholder="Contoh: WELCOME20" required 
                                   style="text-transform: uppercase; font-family: monospace; font-weight: 700; letter-spacing: 1px;">
                            <small class="form-hint">Gunakan huruf kapital & angka, tanpa spasi</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Nama Promo <span class="required">*</span>
                            </label>
                            <input type="text" name="name" id="name" class="form-control" 
                                   placeholder="Contoh: Welcome Discount 20%" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Deskripsi (Opsional)</label>
                        <textarea name="description" id="description" class="form-control" rows="2" 
                                  placeholder="Deskripsi singkat promo ini..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Tipe Diskon <span class="required">*</span>
                            </label>
                            <select name="discount_type" id="discount_type" class="form-control" required>
                                <option value="percentage">📊 Persentase (%)</option>
                                <option value="fixed">💰 Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Nilai Diskon <span class="required">*</span>
                            </label>
                            <input type="number" name="discount_value" id="discount_value" class="form-control" 
                                   step="1" min="1" placeholder="Masukkan nilai" required>
                            <small class="form-hint" id="discountHint">Untuk persen: 10 = 10%, Untuk nominal: 50000 = Rp 50.000</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Minimal Pembelian (Rp)
                            </label>
                            <input type="number" name="min_purchase" id="min_purchase" class="form-control" 
                                   value="0" step="10000" min="0">
                            <small class="form-hint">Isi 0 jika tanpa minimal pembelian</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Batas Penggunaan
                            </label>
                            <input type="number" name="usage_limit" id="usage_limit" class="form-control" 
                                   placeholder="Kosongkan = unlimited" min="1">
                            <small class="form-hint">Batas berapa kali kupon bisa dipakai</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Tanggal Mulai <span class="required">*</span>
                            </label>
                            <input type="datetime-local" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Tanggal Berakhir <span class="required">*</span>
                            </label>
                            <input type="datetime-local" name="end_date" id="end_date" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" checked>
                            <label for="is_active" style="cursor: pointer; font-weight: 600;">Aktifkan Promo Sekarang</label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('promoForm').submit();">
                    <i class="fas fa-save"></i> Simpan Promo
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Open modal for ADD
        function openModal(type) {
            var modal = document.getElementById('promoModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            
            if (type === 'add') {
                document.getElementById('modalTitle').textContent = 'Tambah Promo Baru';
                document.getElementById('formAction').value = 'add';
                clearForm();
            }
        }
        
        // Open modal for EDIT
        function editPromo(promo) {
            var modal = document.getElementById('promoModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            
            document.getElementById('modalTitle').textContent = 'Edit Promo: ' + promo.code;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('promoId').value = promo.id;
            document.getElementById('code').value = promo.code;
            document.getElementById('name').value = promo.name;
            document.getElementById('description').value = promo.description || '';
            document.getElementById('discount_type').value = promo.discount_type;
            document.getElementById('discount_value').value = promo.discount_value;
            document.getElementById('min_purchase').value = promo.min_purchase;
            document.getElementById('start_date').value = promo.start_date.replace(' ', 'T');
            document.getElementById('end_date').value = promo.end_date.replace(' ', 'T');
            document.getElementById('usage_limit').value = promo.usage_limit || '';
            document.getElementById('is_active').checked = promo.is_active == 1;
        }
        
        // Clear form untuk tambah baru
        function clearForm() {
            document.getElementById('promoId').value = '';
            document.getElementById('code').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('discount_type').value = 'percentage';
            document.getElementById('discount_value').value = '';
            document.getElementById('min_purchase').value = '0';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('usage_limit').value = '';
            document.getElementById('is_active').checked = true;
        }
        
        // Close modal
        function closeModal() {
            var modal = document.getElementById('promoModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
        
        // Close on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('promoModal')) {
                closeModal();
            }
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // Auto-hide flash message after 5 seconds
        setTimeout(function() {
            var alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    </script>
    
</body>
</html>
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
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $account_number = trim($_POST['account_number'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');
        $bank_name = trim($_POST['bank_name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Upload QRIS image
        $qris_image = $_POST['old_qris'] ?? null;
        if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] == 0) {
            $upload_dir = __DIR__ . '/../../assets/uploads/payments/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = strtolower(pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                $qris_image = 'qris-' . time() . '.' . $ext;
                move_uploaded_file($_FILES['qris_image']['tmp_name'], $upload_dir . $qris_image);
            }
        }
        
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO payment_methods (name, type, account_number, account_name, bank_name, qris_image, instructions, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $account_number, $account_name, $bank_name, $qris_image, $instructions, $sort_order, $is_active]);
        } else {
            $id = intval($_POST['id']);
            if ($qris_image) {
                $stmt = $db->prepare("UPDATE payment_methods SET name=?, type=?, account_number=?, account_name=?, bank_name=?, qris_image=?, instructions=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $type, $account_number, $account_name, $bank_name, $qris_image, $instructions, $sort_order, $is_active, $id]);
            } else {
                $stmt = $db->prepare("UPDATE payment_methods SET name=?, type=?, account_number=?, account_name=?, bank_name=?, instructions=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $type, $account_number, $account_name, $bank_name, $instructions, $sort_order, $is_active, $id]);
            }
        }
        
        $_SESSION['flash_msg'] = 'Metode pembayaran berhasil disimpan!';
        $_SESSION['flash_type'] = 'success';
        header('Location: payment-methods.php');
        exit;
    }
    
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("DELETE FROM payment_methods WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_msg'] = 'Metode pembayaran dihapus!';
        $_SESSION['flash_type'] = 'warning';
        header('Location: payment-methods.php');
        exit;
    }
}

// Get all payment methods
$stmt = $db->query("SELECT * FROM payment_methods ORDER BY sort_order ASC");
$methods = $stmt->fetchAll();

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>💳 Metode Pembayaran</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Pembayaran</div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Tambah Metode
                </button>
            </div>
        </div>
        
        <?php if ($flash_msg): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $flash_msg; ?>
        </div>
        <?php endif; ?>
        
        <!-- INFO -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <span>Metode pembayaran ini akan ditampilkan ke pembeli saat checkout. Urutkan sesuai prioritas.</span>
        </div>
        
        <!-- TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Daftar Metode Pembayaran</div>
                <span class="text-sm text-muted"><?php echo count($methods); ?> metode</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Nama</th>
                            <th width="80">Tipe</th>
                            <th>No. Rekening</th>
                            <th>Atas Nama</th>
                            <th width="60">QRIS</th>
                            <th width="60">Urut</th>
                            <th width="80">Status</th>
                            <th width="140">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($methods)): ?>
                        <tr><td colspan="9"><div class="empty-state"><i class="fas fa-credit-card"></i><h3>Belum ada metode</h3></div></td></tr>
                        <?php else: ?>
                        <?php foreach ($methods as $m): ?>
                        <tr>
                            <td><?php echo $m['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($m['name']); ?></strong>
                                <?php if ($m['bank_name']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($m['bank_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $type_icons = ['bank'=>'🏦 Bank','qris'=>'📱 QRIS','cash'=>'💵 Cash','ewallet'=>'📲 E-Wallet'];
                                echo $type_icons[$m['type']] ?? $m['type'];
                                ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($m['account_number'] ?? '-'); ?></code></td>
                            <td><?php echo htmlspecialchars($m['account_name'] ?? '-'); ?></td>
                            <td>
                                <?php if ($m['qris_image']): ?>
                                <img src="../../assets/uploads/payments/<?php echo $m['qris_image']; ?>" 
                                     style="width:40px;height:40px;object-fit:cover;border-radius:5px;cursor:pointer;"
                                     onclick="window.open('../../assets/uploads/payments/<?php echo $m['qris_image']; ?>')">
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $m['sort_order']; ?></td>
                            <td>
                                <span class="badge <?php echo $m['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $m['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" onclick='editMethod(<?php echo json_encode($m); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus metode ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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
        
    </main>
    
    <!-- MODAL FORM -->
    <div class="modal" id="methodModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Tambah Metode Pembayaran</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="methodId">
                <input type="hidden" name="old_qris" id="oldQris">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama <span class="required">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: BCA, QRIS, Cash" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipe <span class="required">*</span></label>
                            <select name="type" id="type" class="form-control" required onchange="toggleFields()">
                                <option value="bank">🏦 Transfer Bank</option>
                                <option value="qris">📱 QRIS</option>
                                <option value="cash">💵 Cash / Tunai</option>
                                <option value="ewallet">📲 E-Wallet</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="bankFields">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" name="bank_name" id="bank_name" class="form-control" placeholder="BCA / Mandiri / BRI">
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Rekening</label>
                                <input type="text" name="account_number" id="account_number" class="form-control" placeholder="1234567890">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Atas Nama</label>
                            <input type="text" name="account_name" id="account_name" class="form-control" placeholder="Kayumanies Cake Shop">
                        </div>
                    </div>
                    
                    <div id="qrisField" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Gambar QRIS</label>
                            <input type="file" name="qris_image" id="qris_image" class="form-control" accept="image/*">
                            <small class="form-hint">Upload gambar QRIS (JPG/PNG)</small>
                            <div id="qrisPreview" style="margin-top:10px;display:none;">
                                <img id="previewImg" src="" style="max-width:200px;border-radius:10px;border:2px solid #eee;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instruksi Pembayaran</label>
                        <textarea name="instructions" id="instructions" class="form-control" rows="3" placeholder="Instruksi untuk pembeli..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Urutan</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" checked>
                                <label for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleFields() {
            var type = document.getElementById('type').value;
            document.getElementById('bankFields').style.display = (type === 'bank' || type === 'ewallet') ? 'block' : 'none';
            document.getElementById('qrisField').style.display = (type === 'qris') ? 'block' : 'none';
        }
        
        function openModal(type) {
            document.getElementById('methodModal').style.display = 'block';
            if (type === 'add') {
                document.getElementById('modalTitle').textContent = 'Tambah Metode Pembayaran';
                document.getElementById('formAction').value = 'add';
                document.getElementById('methodId').value = '';
                document.getElementById('name').value = '';
                document.getElementById('type').value = 'bank';
                document.getElementById('bank_name').value = '';
                document.getElementById('account_number').value = '';
                document.getElementById('account_name').value = '';
                document.getElementById('instructions').value = '';
                document.getElementById('sort_order').value = '0';
                document.getElementById('is_active').checked = true;
                document.getElementById('oldQris').value = '';
                document.getElementById('qris_image').value = '';
                document.getElementById('qrisPreview').style.display = 'none';
                toggleFields();
            }
        }
        
        function editMethod(m) {
            document.getElementById('methodModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit: ' + m.name;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('methodId').value = m.id;
            document.getElementById('name').value = m.name;
            document.getElementById('type').value = m.type;
            document.getElementById('bank_name').value = m.bank_name || '';
            document.getElementById('account_number').value = m.account_number || '';
            document.getElementById('account_name').value = m.account_name || '';
            document.getElementById('instructions').value = m.instructions || '';
            document.getElementById('sort_order').value = m.sort_order;
            document.getElementById('is_active').checked = m.is_active == 1;
            document.getElementById('oldQris').value = m.qris_image || '';
            toggleFields();
            
            if (m.qris_image) {
                document.getElementById('qrisPreview').style.display = 'block';
                document.getElementById('previewImg').src = '../../assets/uploads/payments/' + m.qris_image;
            }
        }
        
        function closeModal() {
            document.getElementById('methodModal').style.display = 'none';
        }
        
        document.getElementById('qris_image').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('qrisPreview').style.display = 'block';
                    document.getElementById('previewImg').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        window.onclick = function(e) { if (e.target == document.getElementById('methodModal')) closeModal(); };
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
    </script>
</body>
</html>
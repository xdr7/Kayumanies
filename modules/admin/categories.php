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
    
    if ($action == 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(str_replace(' ', '-', $name));
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        // Cek slug exists
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . date('His');
        }
        
        $stmt = $db->prepare("INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $sort_order]);
        $msg = 'Kategori berhasil ditambahkan!';
        
    } elseif ($action == 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(str_replace(' ', '-', $name));
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 1);
        
        // Cek slug exists (kecuali untuk dirinya sendiri)
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . date('His');
        }
        
        $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, description=?, sort_order=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $slug, $description, $sort_order, $is_active, $id]);
        $msg = 'Kategori berhasil diupdate!';
        
    } elseif ($action == 'toggle_status') {
        $id = intval($_POST['id']);
        $is_active = intval($_POST['is_active']);
        $stmt = $db->prepare("UPDATE categories SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $id]);
        $msg = 'Status kategori berhasil diubah!';
        
    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        
        // Set category_id ke NULL untuk produk terkait
        $stmt = $db->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'Kategori berhasil dihapus!';
    }
    
    $_SESSION['flash_msg'] = $msg ?? 'Operasi berhasil!';
    $_SESSION['flash_type'] = ($action == 'delete') ? 'warning' : 'success';
    header('Location: categories.php');
    exit;
}

// Get flash message
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Get all categories
$stmt = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.sort_order ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📋 Manajemen Kategori</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Kategori</div>
            </div>
            <div class="top-bar-right">
                <span class="text-gray text-sm">Total: <?php echo count($categories); ?> kategori</span>
            </div>
        </div>
        
        <?php if ($flash_msg): ?>
        <div class="alert alert-<?php echo $flash_type; ?>">
            <i class="fas fa-<?php echo $flash_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <span><?php echo htmlspecialchars($flash_msg); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- ADD CATEGORY FORM -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-plus-circle"></i> Tambah Kategori Baru</div>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama Kategori <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Birthday Cake" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi</label>
                            <input type="text" name="description" class="form-control" placeholder="Deskripsi singkat (opsional)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0" style="max-width:120px;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Kategori</button>
                </form>
            </div>
        </div>
        
        <!-- CATEGORIES TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Daftar Kategori</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th>Nama Kategori</th>
                                <th width="150">Slug</th>
                                <th width="100">Produk</th>
                                <th width="80">Sort</th>
                                <th width="110">Status</th>
                                <th width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-folder-open"></i><h3>Belum Ada Kategori</h3></div></td></tr>
                            <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong>#<?php echo $cat['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                    <?php if (!empty($cat['description'])): ?><br><small class="text-muted"><?php echo htmlspecialchars(substr($cat['description'],0,60)); ?></small><?php endif; ?>
                                </td>
                                <td><code style="background:#f5f5f5;padding:2px 8px;border-radius:4px;font-size:12px;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                <td><span class="badge <?php echo $cat['product_count']>0?'badge-info':''; ?>"><?php echo $cat['product_count']; ?> produk</span></td>
                                <td><?php echo $cat['sort_order']; ?></td>
                                <td>
                                    <form method="POST" id="toggleForm_<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                        <select name="is_active" onchange="document.getElementById('toggleForm_<?php echo $cat['id']; ?>').submit();" class="form-control" style="width:auto;padding:5px 10px;font-size:11px;display:inline-block;">
                                            <option value="1" <?php echo $cat['is_active']?'selected':''; ?>>✅ Aktif</option>
                                            <option value="0" <?php echo !$cat['is_active']?'selected':''; ?>>❌ Nonaktif</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" onclick='editCategory(<?php echo json_encode($cat); ?>)' title="Edit"><i class="fas fa-edit"></i> Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus kategori ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash-alt"></i> Hapus</button>
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
    
    <!-- MODAL EDIT KATEGORI -->
    <div class="modal" id="editModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-header">
                <h2 class="modal-title">Edit Kategori</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="form-group">
                        <label class="form-label">Nama Kategori <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="description" id="edit_description" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" id="edit_is_active" class="form-control">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').submit();"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </div>
    </div>
    
    <script>
    function editCategory(cat) {
        var modal = document.getElementById('editModal');
        modal.style.display = 'block'; modal.classList.add('show');
        document.getElementById('edit_id').value = cat.id;
        document.getElementById('edit_name').value = cat.name;
        document.getElementById('edit_description').value = cat.description || '';
        document.getElementById('edit_sort_order').value = cat.sort_order;
        document.getElementById('edit_is_active').value = cat.is_active ? '1' : '0';
    }
    
    function closeEditModal() {
        var modal = document.getElementById('editModal');
        modal.style.display = 'none'; modal.classList.remove('show');
    }
    
    window.onclick = function(event) { if (event.target == document.getElementById('editModal')) closeEditModal(); }
    document.addEventListener('keydown', function(event) { if (event.key === 'Escape') closeEditModal(); });
    
    setTimeout(function() {
        var alert = document.querySelector('.alert');
        if (alert) { alert.style.transition = 'opacity 0.5s'; alert.style.opacity = '0'; setTimeout(function() { if (alert && alert.parentNode) alert.parentNode.removeChild(alert); }, 500); }
    }, 5000);
    </script>
    
</body>
</html>
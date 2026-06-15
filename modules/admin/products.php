<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add' || $action == 'edit') {
        $name = trim($_POST['name']);
        $slug = strtolower(str_replace(' ', '-', $name));
        $category_id = intval($_POST['category_id']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
        $stock = intval($_POST['stock']);
        $weight = trim($_POST['weight']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Cek slug duplicate
        if ($action == 'add') {
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug = $slug . '-' . date('His');
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug = $slug . '-' . date('His');
            }
        }
        
        // Handle image upload
        $image_name = $_POST['old_image'] ?? 'default-cake.jpg';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = __DIR__ . '/../../assets/uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed) && $_FILES['image']['size'] <= 5000000) {
                $image_name = 'product-' . time() . '-' . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
                
                // Hapus gambar lama
                if (!empty($_POST['old_image']) && $_POST['old_image'] != 'default-cake.jpg') {
                    $old_path = $upload_dir . $_POST['old_image'];
                    if (file_exists($old_path)) unlink($old_path);
                }
            }
        }
        
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO products (category_id, name, slug, description, price, discount_price, stock, weight, image, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $slug, $description, $price, $discount_price, $stock, $weight, $image_name, $is_featured, $is_active]);
            $msg = 'Produk berhasil ditambahkan!';
        } else {
            $stmt = $db->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, discount_price=?, stock=?, weight=?, image=?, is_featured=?, is_active=? WHERE id=?");
            $stmt->execute([$category_id, $name, $slug, $description, $price, $discount_price, $stock, $weight, $image_name, $is_featured, $is_active, $id]);
            $msg = 'Produk berhasil diupdate!';
        }
        
        $_SESSION['flash_msg'] = $msg;
        $_SESSION['flash_type'] = 'success';
        header('Location: products.php');
        exit;
    }
    
    // HAPUS PERMANENT
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        
        // Hapus gambar
        $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product && $product['image'] != 'default-cake.jpg') {
            $img_path = __DIR__ . '/../../assets/uploads/products/' . $product['image'];
            if (file_exists($img_path)) unlink($img_path);
        }
        
        // Hapus dari order_details yang terkait (set NULL)
        $stmt = $db->prepare("UPDATE order_details SET product_id = NULL WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Hapus dari cart
        $stmt = $db->prepare("DELETE FROM cart WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Hapus dari reviews
        $stmt = $db->prepare("DELETE FROM reviews WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // Hapus produk
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_msg'] = 'Produk berhasil dihapus permanen!';
        $_SESSION['flash_type'] = 'warning';
        header('Location: products.php');
        exit;
    }
}

// Flash message
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// ==========================================
// FILTER & PAGINATION
// ==========================================
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($category_filter)) {
    $where[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter == 'active') {
    $where[] = "p.is_active = 1";
} elseif ($status_filter == 'inactive') {
    $where[] = "p.is_active = 0";
}

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE " . ($where ? implode(" AND ", $where) : "1=1");
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get products
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id {$where_clause} ORDER BY p.created_at DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
$categories = $stmt->fetchAll();

// Query params untuk link
$query_string = http_build_query(array_filter(['category' => $category_filter, 'status' => $status_filter, 'search' => $search]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📦 Manajemen Produk</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Produk</div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Tambah Produk
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
        
        <!-- PRODUCTS TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-boxes"></i> Daftar Produk</div>
                <span class="text-sm text-muted"><?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total); ?> dari <?php echo $total; ?></span>
            </div>
            <div class="card-body">
                
                <!-- FILTER -->
                <form class="filter-bar" method="GET">
                    <select name="category">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                    
                    <input type="text" name="search" placeholder="🔍 Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <button type="submit" class="btn btn-sm btn-info">Filter</button>
                    <?php if ($category_filter || $status_filter || $search): ?>
                    <a href="products.php" class="btn btn-sm btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
                
                <!-- TABLE -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th width="60">Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th width="120">Harga</th>
                                <th width="60">Stok</th>
                                <th width="70">Featured</th>
                                <th width="70">Status</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr><td colspan="9"><div class="empty-state"><i class="fas fa-box-open"></i><h3>Belum Ada Produk</h3></div></td></tr>
                            <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong>#<?php echo $product['id']; ?></strong></td>
                                <td>
                                    <?php if (!empty($product['image']) && $product['image'] != 'default-cake.jpg'): ?>
                                    <img src="../../assets/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:8px;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <?php endif; ?>
                                    <div style="width:50px;height:50px;background:#FFF3E0;border-radius:8px;display:<?php echo (!empty($product['image']) && $product['image']!='default-cake.jpg')?'none':'flex'; ?>;align-items:center;justify-content:center;font-size:20px;">🎂</div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if (!empty($product['weight'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($product['weight']); ?></small><?php endif; ?>
                                </td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($product['category_name']); ?></span></td>
                                <td>
                                    <?php if (!empty($product['discount_price'])): ?>
                                    <span style="text-decoration:line-through;color:#ccc;font-size:12px;">Rp <?php echo number_format($product['price'],0,',','.'); ?></span><br>
                                    <strong style="color:#8B4513;">Rp <?php echo number_format($product['discount_price'],0,',','.'); ?></strong>
                                    <?php else: ?>
                                    <strong>Rp <?php echo number_format($product['price'],0,',','.'); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['stock'] <= 5 ? '<span style="color:#f44336;font-weight:700;">'.$product['stock'].'</span>' : $product['stock']; ?></td>
                                <td><?php echo $product['is_featured'] ? '<span class="badge badge-warning">⭐ Featured</span>' : '-'; ?></td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                    <span class="badge badge-active"><i class="fas fa-check-circle"></i> Aktif</span>
                                    <?php else: ?>
                                    <span class="badge badge-inactive"><i class="fas fa-ban"></i> Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info" onclick='editProduct(<?php echo json_encode($product); ?>)' title="Edit"><i class="fas fa-edit"></i> Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('HAPUS PERMANEN produk <?php echo htmlspecialchars(addslashes($product['name'])); ?>? Data terkait akan dihapus!')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus Permanen"><i class="fas fa-trash"></i> Hapus</button>
                                        </form>
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
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo $query_string; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
    </main>
    
    <!-- MODAL ADD/EDIT PRODUCT -->
    <div class="modal" id="productModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Tambah Produk Baru</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="productForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="old_image" id="oldImage" value="">
                    
                    <div class="form-row">
                        <div class="form-group" style="flex:2;">
                            <label class="form-label">Nama Produk <span class="required">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Nama produk" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label class="form-label">Kategori <span class="required">*</span></label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Harga <span class="required">*</span></label><input type="number" name="price" id="price" class="form-control" step="100" min="0" required></div>
                        <div class="form-group"><label class="form-label">Harga Diskon</label><input type="number" name="discount_price" id="discount_price" class="form-control" step="100" min="0"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Stok</label><input type="number" name="stock" id="stock" class="form-control" value="0" min="0" required></div>
                        <div class="form-group"><label class="form-label">Berat / Ukuran</label><input type="text" name="weight" id="weight" class="form-control" placeholder="Contoh: 1 kg"></div>
                    </div>
                    
                    <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="description" id="description" class="form-control" rows="3"></textarea></div>
                    
                    <div class="form-group">
                        <label class="form-label">Gambar Produk</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*" style="padding:8px;">
                        <small class="form-hint">JPG/PNG/GIF/WEBP, Maks 5MB. Kosongkan jika tidak ingin mengubah.</small>
                        <div id="imagePreview" style="margin-top:10px;display:none;"><img id="previewImg" src="" style="max-width:200px;max-height:150px;object-fit:cover;border-radius:10px;border:2px solid #eee;"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><div class="form-check"><input type="checkbox" name="is_featured" id="is_featured"><label for="is_featured">Featured</label></div></div>
                        <div class="form-group"><div class="form-check"><input type="checkbox" name="is_active" id="is_active" checked><label for="is_active">Aktif</label></div></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()"><i class="fas fa-times"></i> Batal</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('productForm').submit();"><i class="fas fa-save"></i> Simpan Produk</button>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('image').addEventListener('change', function(e) {
        var p = document.getElementById('imagePreview'), i = document.getElementById('previewImg');
        if (this.files && this.files[0]) {
            var r = new FileReader(); r.onload = function(e) { i.src = e.target.result; p.style.display = 'block'; }; r.readAsDataURL(this.files[0]);
        } else { p.style.display = 'none'; }
    });
    
    function openModal(type) {
        var m = document.getElementById('productModal'); m.style.display = 'block'; m.classList.add('show');
        if (type === 'add') {
            document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            document.getElementById('oldImage').value = '';
            document.getElementById('name').value = '';
            document.getElementById('price').value = '';
            document.getElementById('discount_price').value = '';
            document.getElementById('description').value = '';
            document.getElementById('stock').value = '0';
            document.getElementById('weight').value = '';
            document.getElementById('category_id').value = '';
            document.getElementById('is_featured').checked = false;
            document.getElementById('is_active').checked = true;
            document.getElementById('image').value = '';
            document.getElementById('imagePreview').style.display = 'none';
        }
    }
    
    function editProduct(p) {
        var m = document.getElementById('productModal'); m.style.display = 'block'; m.classList.add('show');
        document.getElementById('modalTitle').textContent = 'Edit Produk: ' + p.name;
        document.getElementById('formAction').value = 'edit';
        document.getElementById('productId').value = p.id;
        document.getElementById('oldImage').value = p.image || '';
        document.getElementById('name').value = p.name;
        document.getElementById('price').value = p.price;
        document.getElementById('discount_price').value = p.discount_price || '';
        document.getElementById('description').value = p.description || '';
        document.getElementById('stock').value = p.stock;
        document.getElementById('weight').value = p.weight || '';
        document.getElementById('category_id').value = p.category_id;
        document.getElementById('is_featured').checked = p.is_featured == 1;
        document.getElementById('is_active').checked = p.is_active == 1;
        document.getElementById('image').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        if (p.image && p.image !== 'default-cake.jpg') {
            document.getElementById('previewImg').src = '../../assets/uploads/products/' + p.image;
            document.getElementById('imagePreview').style.display = 'block';
        }
    }
    
    function closeModal() { var m = document.getElementById('productModal'); m.style.display = 'none'; m.classList.remove('show'); }
    window.onclick = function(e) { if (e.target == document.getElementById('productModal')) closeModal(); }
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
    
    setTimeout(function() {
        var a = document.querySelector('.alert');
        if (a) { a.style.transition = 'opacity 0.5s'; a.style.opacity = '0'; setTimeout(function() { if (a && a.parentNode) a.parentNode.removeChild(a); }, 500); }
    }, 5000);
    </script>
    
</body>
</html>
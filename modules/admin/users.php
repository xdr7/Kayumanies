<?php
/**
 * Kayumanies - Admin Users Management
 * Full CRUD: Create, Read, Update, Delete
 */
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

$message = '';
$message_type = '';

// ==========================================
// HANDLE ALL ACTIONS
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // ========== CREATE NEW USER ==========
        if ($action == 'add') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $role = $_POST['role'];
            
            // Validation
            $errors = [];
            
            if (empty($username) || strlen($username) < 3) {
                $errors[] = 'Username minimal 3 karakter';
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email tidak valid';
            }
            
            if (empty($password) || strlen($password) < 6) {
                $errors[] = 'Password minimal 6 karakter';
            }
            
            if (empty($full_name)) {
                $errors[] = 'Nama lengkap harus diisi';
            }
            
            if (!in_array($role, ['admin', 'kasir', 'pembeli'])) {
                $errors[] = 'Role tidak valid';
            }
            
            // Check existing username
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username sudah digunakan';
            }
            
            // Check existing email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah terdaftar';
            }
            
            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, address, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address, $role]);
                
                $message = "User <strong>{$username}</strong> berhasil ditambahkan!";
                $message_type = 'success';
            } else {
                $message = implode('<br>', $errors);
                $message_type = 'error';
            }
        }
        
        // ========== UPDATE USER ==========
        if ($action == 'edit') {
            $user_id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Check user exists
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $existing_user = $stmt->fetch();
            
            if (!$existing_user) {
                $message = 'User tidak ditemukan!';
                $message_type = 'error';
            } else {
                // Check username uniqueness (exclude current user)
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $user_id]);
                if ($stmt->fetch()) {
                    $message = 'Username sudah digunakan oleh user lain!';
                    $message_type = 'error';
                } else {
                    // Check email uniqueness
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        $message = 'Email sudah digunakan oleh user lain!';
                        $message_type = 'error';
                    } else {
                        // Update user
                        if (!empty($password)) {
                            // Update with new password
                            if (strlen($password) < 6) {
                                $message = 'Password minimal 6 karakter!';
                                $message_type = 'error';
                            } else {
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $db->prepare("UPDATE users SET username=?, email=?, password=?, full_name=?, phone=?, address=?, role=?, is_active=? WHERE id=?");
                                $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address, $role, $is_active, $user_id]);
                                $message = "User <strong>{$username}</strong> berhasil diupdate!";
                                $message_type = 'success';
                            }
                        } else {
                            // Update without password
                            $stmt = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, phone=?, address=?, role=?, is_active=? WHERE id=?");
                            $stmt->execute([$username, $email, $full_name, $phone, $address, $role, $is_active, $user_id]);
                            $message = "User <strong>{$username}</strong> berhasil diupdate!";
                            $message_type = 'success';
                        }
                    }
                }
            }
        }
        
        // ========== QUICK TOGGLE ACTIVE ==========
        if ($action == 'toggle_active') {
            $user_id = intval($_POST['user_id']);
            
            if ($user_id == $_SESSION['user_id']) {
                $message = 'Anda tidak bisa menonaktifkan diri sendiri!';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'");
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Status user berhasil diubah!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal mengubah status admin utama!';
                    $message_type = 'error';
                }
            }
        }
        
        // ========== QUICK CHANGE ROLE ==========
        if ($action == 'change_role') {
            $user_id = intval($_POST['user_id']);
            $role = $_POST['role'];
            
            if ($user_id == $_SESSION['user_id']) {
                $message = 'Anda tidak bisa mengubah role sendiri!';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'admin'");
                $stmt->execute([$role, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Role user berhasil diubah!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal mengubah role admin utama!';
                    $message_type = 'error';
                }
            }
        }
        
        // ========== DELETE USER ==========
        if ($action == 'delete') {
            $user_id = intval($_POST['user_id']);
            
            if ($user_id == $_SESSION['user_id']) {
                $message = 'Anda tidak bisa menghapus diri sendiri!';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $has_orders = $stmt->fetch()['total'] > 0;
                
                if ($has_orders) {
                    $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $message = 'User memiliki pesanan, status diubah menjadi nonaktif.';
                    $message_type = 'warning';
                } else {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'User berhasil dihapus!';
                        $message_type = 'success';
                    } else {
                        $message = 'Gagal menghapus admin utama!';
                        $message_type = 'error';
                    }
                }
            }
        }
        
        if (!empty($message)) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $message_type;
            header('Location: users.php');
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: users.php');
        exit;
    }
}

// Get flash messages
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// ==========================================
// GET USERS WITH FILTER
// ==========================================

$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    if ($status_filter == 'active') {
        $where[] = "is_active = 1";
    } elseif ($status_filter == 'inactive') {
        $where[] = "is_active = 0";
    }
}

if (!empty($search)) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$count_sql = "SELECT COUNT(*) as total FROM users {$where_clause}";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get users
$sql = "SELECT * FROM users {$where_clause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Stats
$stmt = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role = 'kasir' THEN 1 ELSE 0 END) as kasir_count,
    SUM(CASE WHEN role = 'pembeli' THEN 1 ELSE 0 END) as pembeli_count,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count
    FROM users");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Users - Admin Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
    
    <style>
        /* Additional styles untuk user cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            background: #FFF3E0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            color: #8B4513;
            flex-shrink: 0;
        }
        .user-info strong {
            font-size: 14px;
            display: block;
        }
        .user-info small {
            color: #999;
            font-size: 12px;
        }
        .action-btns {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- MAIN CONTENT -->
    <main class="admin-main">
        
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>👥 Manajemen Users</h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Home</a> / Users
                </div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-user-plus"></i> Tambah User
                </button>
            </div>
        </div>
        
        <!-- FLASH MESSAGE -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'error' ? 'times-circle' : 'exclamation-triangle'); ?>"></i>
            <span><?php echo $message; ?></span>
        </div>
        <?php endif; ?>
        
        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['admin_count']; ?></div>
                    <div class="stat-label">Admin</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-info">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['kasir_count']; ?></div>
                    <div class="stat-label">Kasir</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-success">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['pembeli_count']; ?></div>
                    <div class="stat-label">Pembeli</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['active_count']; ?></div>
                    <div class="stat-label">Aktif</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-danger">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['inactive_count']; ?></div>
                    <div class="stat-label">Nonaktif</div>
                </div>
            </div>
        </div>
        
        <!-- FILTER & TABLE -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-list"></i> Daftar Users
                </div>
                <span class="text-muted text-sm">Total: <?php echo $total; ?> user</span>
            </div>
            
            <!-- Filter -->
            <form class="filter-bar" method="GET">
                <select name="role" class="form-control" style="width: auto;">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="kasir" <?php echo $role_filter == 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                    <option value="pembeli" <?php echo $role_filter == 'pembeli' ? 'selected' : ''; ?>>Pembeli</option>
                </select>
                
                <select name="status" class="form-control" style="width: auto;">
                    <option value="">Semua Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
                
                <input type="text" name="search" class="form-control" placeholder="🔍 Cari username, email, nama..." value="<?php echo htmlspecialchars($search); ?>" style="width: 300px;">
                
                <button type="submit" class="btn btn-info">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if (!empty($role_filter) || !empty($search) || !empty($status_filter)): ?>
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </form>
            
            <!-- Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>User</th>
                            <th width="200">Email</th>
                            <th width="130">Telepon</th>
                            <th width="90">Role</th>
                            <th width="100">Status</th>
                            <th width="140">Login Terakhir</th>
                            <th width="100">Terdaftar</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <h3>Tidak ada user ditemukan</h3>
                                    <p>Coba ubah filter pencarian</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div class="user-info">
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <small>@<?php echo htmlspecialchars($user['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo strtoupper($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                <span class="badge badge-active">
                                    <i class="fas fa-check"></i> Aktif
                                </span>
                                <?php else: ?>
                                <span class="badge badge-inactive">
                                    <i class="fas fa-ban"></i> Blokir
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                <small><?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?></small>
                                <?php else: ?>
                                <small class="text-muted">Belum login</small>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-sm btn-info" onclick='editUser(<?php echo json_encode($user); ?>)' title="Edit User">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                title="<?php echo $user['is_active'] ? 'Blokir User' : 'Aktifkan User'; ?>">
                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            <?php echo $user['is_active'] ? 'Blokir' : 'Aktifkan'; ?>
                                        </button>
                                    </form>
                                    
                                    <?php if ($user['role'] != 'admin'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus user <?php echo htmlspecialchars(addslashes($user['full_name'])); ?>?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus User">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Info -->
            <div class="d-flex justify-between align-center" style="margin-top: 20px;">
                <span class="text-sm text-muted">
                    Menampilkan <?php echo min($offset + 1, $total); ?>-<?php echo min($offset + $limit, $total); ?> dari <?php echo $total; ?> user
                </span>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="margin-top: 0;">
                    <?php 
                    $query_params = http_build_query(array_filter([
                        'role' => $role_filter,
                        'status' => $status_filter,
                        'search' => $search
                    ]));
                    
                    // Previous
                    if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&<?php echo $query_params; ?>" class="page-link">«</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo $query_params; ?>" 
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&<?php echo $query_params; ?>" class="page-link">»</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    </main>
    
    <!-- ========== ADD/EDIT MODAL ========== -->
    <div class="modal" id="userModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Tambah User Baru</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form method="POST" id="userForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="user_id" id="userId">
                    
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Nama lengkap user" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username <span class="required">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Username unik" required minlength="3">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="email@example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Password <span class="required" id="passRequired">*</span>
                            </label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6">
                            <small class="form-hint" id="passHint" style="display: none;">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="0812-3456-7890">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Role <span class="required">*</span></label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="pembeli">Pembeli</option>
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="is_active" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" id="address" class="form-control" rows="2" placeholder="Alamat (opsional)"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('userForm').submit();">
                    <i class="fas fa-save"></i> Simpan User
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(type) {
            var modal = document.getElementById('userModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            
            document.getElementById('modalTitle').textContent = 'Tambah User Baru';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userId').value = '';
            
            document.getElementById('full_name').value = '';
            document.getElementById('username').value = '';
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('role').value = 'pembeli';
            document.getElementById('is_active').value = '1';
            document.getElementById('address').value = '';
            
            document.getElementById('password').required = true;
            document.getElementById('passRequired').style.display = 'inline';
            document.getElementById('passHint').style.display = 'none';
        }
        
        function editUser(user) {
            var modal = document.getElementById('userModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            
            document.getElementById('modalTitle').textContent = 'Edit User: ' + user.full_name;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = user.id;
            
            document.getElementById('full_name').value = user.full_name || '';
            document.getElementById('username').value = user.username || '';
            document.getElementById('email').value = user.email || '';
            document.getElementById('password').value = '';
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role || 'pembeli';
            document.getElementById('is_active').value = user.is_active ? '1' : '0';
            document.getElementById('address').value = user.address || '';
            
            document.getElementById('password').required = false;
            document.getElementById('passRequired').style.display = 'none';
            document.getElementById('passHint').style.display = 'block';
        }
        
        function closeModal() {
            var modal = document.getElementById('userModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('userModal')) {
                closeModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        setTimeout(function() {
            var alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    </script>
    
</body>
</html>
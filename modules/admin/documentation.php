<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../modules/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Admin - Kayumanies</title>
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
                <h1>📚 Dokumentasi Admin</h1>
                <div class="breadcrumb"><a href="dashboard.php">Home</a> / Dokumentasi</div>
            </div>
            <div class="top-bar-right">
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <!-- DAFTAR ISI -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Daftar Isi</div>
            </div>
            <div class="card-body">
                <div class="btn-group flex-wrap" style="gap:5px;">
                    <a href="#dashboard" class="btn btn-sm btn-outline-primary">📊 Dashboard</a>
                    <a href="#pesanan" class="btn btn-sm btn-outline-primary">🛒 Pesanan</a>
                    <a href="#produk" class="btn btn-sm btn-outline-primary">📦 Produk</a>
                    <a href="#kategori" class="btn btn-sm btn-outline-primary">📋 Kategori</a>
                    <a href="#users" class="btn btn-sm btn-outline-primary">👥 Users</a>
                    <a href="#promo" class="btn btn-sm btn-outline-primary">🏷️ Promo</a>
                    <a href="#review" class="btn btn-sm btn-outline-primary">⭐ Review</a>
                    <a href="#laporan" class="btn btn-sm btn-outline-primary">📈 Laporan</a>
                    <a href="#pembayaran" class="btn btn-sm btn-outline-primary">💳 Pembayaran</a>
                    <a href="#pengaturan" class="btn btn-sm btn-outline-primary">⚙️ Pengaturan</a>
                </div>
            </div>
        </div>
        
        <!-- 1. DASHBOARD -->
        <div class="card" id="dashboard">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-th-large"></i> Dashboard</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">Halaman utama admin menampilkan ringkasan bisnis secara real-time.</p>
                
                <div class="table-container mt-2">
                    <table>
                        <thead>
                            <tr><th>Komponen</th><th>Deskripsi</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Statistik Cards</strong></td><td>Pesanan hari ini, pendapatan, produk aktif, pelanggan, stok menipis</td></tr>
                            <tr><td><strong>Grafik Penjualan</strong></td><td>Chart batang penjualan 6 bulan terakhir</td></tr>
                            <tr><td><strong>Pesanan Terbaru</strong></td><td>5 pesanan terakhir dengan status</td></tr>
                            <tr><td><strong>Pelanggan Baru</strong></td><td>5 pelanggan terbaru</td></tr>
                            <tr><td><strong>Produk Terlaris</strong></td><td>Top 5 produk berdasarkan qty terjual</td></tr>
                            <tr><td><strong>Ringkasan</strong></td><td>Total pesanan, revenue, produk, kategori, pelanggan</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 2. PESANAN -->
        <div class="card" id="pesanan">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-shopping-cart"></i> Manajemen Pesanan</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>orders.php</code> | <code>order-detail.php</code>
                </p>
                
                <div class="table-container mt-2">
                    <table>
                        <thead>
                            <tr><th>Fitur</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Filter</strong></td>
                                <td>Status, pembayaran, tanggal, search nomor/nama pelanggan</td>
                            </tr>
                            <tr>
                                <td><strong>Update Status</strong></td>
                                <td>
                                    <span class="badge badge-pending">Pending</span> →
                                    <span class="badge badge-processing">Processing</span> →
                                    <span class="badge badge-completed">Ready</span> →
                                    <span class="badge badge-success">Completed</span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Verifikasi Bayar</strong></td>
                                <td>Konfirmasi pembayaran manual dari pelanggan</td>
                            </tr>
                            <tr>
                                <td><strong>Detail Pesanan</strong></td>
                                <td>Lihat item, data pelanggan, pembayaran, catatan</td>
                            </tr>
                            <tr>
                                <td><strong>Cetak Struk</strong></td>
                                <td>Print bukti pembayaran</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 3. PRODUK -->
        <div class="card" id="produk">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-boxes"></i> Manajemen Produk</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>products.php</code>
                </p>
                
                <div class="table-container mt-2">
                    <table>
                        <thead>
                            <tr><th>Aksi</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-info">CREATE</span></td>
                                <td>Tambah produk via modal: nama, harga, kategori, stok, gambar</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">READ</span></td>
                                <td>Tabel produk dengan thumbnail, harga, stok, status</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning">UPDATE</span></td>
                                <td>Edit via modal, upload gambar baru (JPG/PNG/GIF, max 5MB)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-danger">DELETE</span></td>
                                <td>Soft delete (set is_active = 0)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 4. KATEGORI -->
        <div class="card" id="kategori">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list-alt"></i> Manajemen Kategori</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>categories.php</code>
                </p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Tambah kategori via form (nama, deskripsi, sort order)</li>
                    <li>Toggle aktif/nonaktif dengan select dropdown (1 klik)</li>
                    <li>Edit kategori via modal</li>
                    <li>Hapus kategori (produk terkait di-set NULL)</li>
                    <li>Tampil jumlah produk per kategori</li>
                </ul>
            </div>
        </div>
        
        <!-- 5. USERS -->
        <div class="card" id="users">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-users"></i> Manajemen Users</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>users.php</code>
                </p>
                
                <div class="table-container mt-2">
                    <table>
                        <thead>
                            <tr><th>Role</th><th>Akses</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-admin">Admin</span></td>
                                <td>Akses penuh semua fitur (produk, users, laporan, pengaturan)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-info">Kasir</span></td>
                                <td>Hanya akses modul kasir (verifikasi bayar, update status)</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">Pembeli</span></td>
                                <td>Hanya akses frontend website (belanja, checkout)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-shield-alt"></i>
                    <span>Tidak bisa blokir/hapus/ubah role diri sendiri. Admin utama tidak bisa dihapus.</span>
                </div>
            </div>
        </div>
        
        <!-- 6. PROMO -->
        <div class="card" id="promo">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-tags"></i> Manajemen Promo</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>promos.php</code>
                </p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li><strong>Tipe Diskon:</strong> Persentase (%) atau Nominal (Rp)</li>
                    <li>Kode promo unik (otomatis UPPERCASE)</li>
                    <li>Minimal pembelian & batas penggunaan</li>
                    <li>Periode aktif (tanggal mulai - berakhir)</li>
                    <li>Tracking usage count otomatis</li>
                    <li>Status: Aktif / Nonaktif / Kadaluarsa</li>
                </ul>
            </div>
        </div>
        
        <!-- 7. REVIEW -->
        <div class="card" id="review">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-star"></i> Manajemen Review</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>reviews.php</code>
                </p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Lihat semua review dari pembeli</li>
                    <li>Filter: Semua / Pending / Approved</li>
                    <li>Approve → tampil di website</li>
                    <li>Unapprove → sembunyikan</li>
                    <li>Hapus review</li>
                    <li>Statistik: total, pending, rata-rata rating</li>
                </ul>
            </div>
        </div>
        
        <!-- 8. LAPORAN -->
        <div class="card" id="laporan">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-chart-line"></i> Laporan</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>reports.php</code>
                </p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li><strong>Filter Periode:</strong> Hari Ini / Kemarin / Minggu Ini / Bulan Ini / Custom</li>
                    <li>Ringkasan penjualan (total order, pendapatan, diskon)</li>
                    <li>Grafik penjualan 7 hari terakhir</li>
                    <li>Status pesanan (progress bar)</li>
                    <li>Metode pembayaran & produk terlaris</li>
                    <li>Bisa di-print</li>
                </ul>
            </div>
        </div>
        
        <!-- 9. PEMBAYARAN -->
        <div class="card" id="pembayaran">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-credit-card"></i> Metode Pembayaran</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>payment-methods.php</code>
                </p>
                
                <div class="table-container mt-2">
                    <table>
                        <thead>
                            <tr><th>Tipe</th><th>Data</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>🏦 Bank</strong></td><td>Nama bank, nomor rekening, atas nama</td></tr>
                            <tr><td><strong>📱 QRIS</strong></td><td>Upload gambar QR code</td></tr>
                            <tr><td><strong>💵 Cash</strong></td><td>Pembayaran tunai di toko</td></tr>
                            <tr><td><strong>📲 E-Wallet</strong></td><td>Info dompet digital</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <p style="color:#666;margin-top:10px;">Metode ini akan ditampilkan ke pembeli saat checkout. Bisa diatur urutan dan status aktif/nonaktif.</p>
            </div>
        </div>
        
        <!-- 10. PENGATURAN -->
        <div class="card" id="pengaturan">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-cog"></i> Pengaturan</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">
                    <strong>File:</strong> <code>settings.php</code>
                </p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li><strong>Info Toko:</strong> Nama, email, telepon, alamat, jam operasional</li>
                    <li><strong>Bisnis:</strong> Mata uang, persentase pajak, minimal order</li>
                </ul>
            </div>
        </div>
        
    </main>
</body>
</html>
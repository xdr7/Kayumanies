<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    header('Location: ../../modules/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Kasir - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/kasir.css">
	<!-- PWA -->
		<?php $pwa_base = '../../'; ?>
		<?php require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="kasir-main">
        
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>📚 Panduan Kasir</h1>
                <div class="date">Dokumentasi Penggunaan</div>
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
                <div class="d-flex flex-wrap gap-1">
                    <a href="#dashboard" class="btn btn-sm" style="background:white;border:1px solid #ddd;">📊 Dashboard</a>
                    <a href="#pesanan" class="btn btn-sm" style="background:white;border:1px solid #ddd;">📋 Pesanan</a>
                    <a href="#detail" class="btn btn-sm" style="background:white;border:1px solid #ddd;">📄 Detail</a>
                    <a href="#aksi" class="btn btn-sm" style="background:white;border:1px solid #ddd;">⚡ Aksi</a>
                    <a href="#struk" class="btn btn-sm" style="background:white;border:1px solid #ddd;">🧾 Struk</a>
                    <a href="#flow" class="btn btn-sm" style="background:white;border:1px solid #ddd;">🔄 Flow</a>
                    <a href="#tips" class="btn btn-sm" style="background:white;border:1px solid #ddd;">💡 Tips</a>
                    <a href="#faq" class="btn btn-sm" style="background:white;border:1px solid #ddd;">❓ FAQ</a>
                </div>
            </div>
        </div>
        
        <!-- 1. DASHBOARD -->
        <div class="card" id="dashboard">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-th-large"></i> Dashboard Kasir</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">Dashboard menampilkan <strong>4 kolom status pesanan</strong> yang perlu ditangani:</p>
                
                <div class="table-container mt-2">
                    <table>
                        <thead>
                            <tr><th>Kolom</th><th>Isi</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-warning">Verifikasi Bayar</span></td>
                                <td>Pesanan belum dibayar</td>
                                <td><strong>Konfirmasi Bayar</strong></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-info">Perlu Diproses</span></td>
                                <td>Sudah bayar, menunggu proses</td>
                                <td><strong>Mulai Proses</strong></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-purple">Sedang Diproses</span></td>
                                <td>Dalam pengerjaan</td>
                                <td><strong>Siap Diambil</strong></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">Siap Diambil</span></td>
                                <td>Sudah jadi, tinggal ambil</td>
                                <td><strong>Selesaikan</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-2">
                    <i class="fas fa-lightbulb"></i>
                    <span>Klik card pesanan untuk detail lengkap. Gunakan filter tanggal untuk riwayat.</span>
                </div>
            </div>
        </div>
        
        <!-- 2. PESANAN -->
        <div class="card" id="pesanan">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list-alt"></i> Daftar Pesanan</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">Filter tersedia:</p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li><strong>Tanggal</strong> - Pilih tanggal tertentu</li>
                    <li><strong>Pembayaran</strong> - Menunggu / Lunas</li>
                    <li><strong>Status</strong> - Pending / Processing / Ready / Completed / Cancelled</li>
                    <li><strong>Search</strong> - Cari nomor pesanan atau nama pelanggan</li>
                </ul>
            </div>
        </div>
        
        <!-- 3. DETAIL -->
        <div class="card" id="detail">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-file-alt"></i> Detail Pesanan</div>
            </div>
            <div class="card-body">
                <p style="color:#666;line-height:1.8;">Menampilkan:</p>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Status pesanan & pembayaran</li>
                    <li>Data pelanggan (nama, telepon, email)</li>
                    <li>Item pesanan (produk, harga, qty, subtotal)</li>
                    <li>Diskon & total pembayaran</li>
                    <li>Tombol aksi sesuai status saat ini</li>
                </ul>
            </div>
        </div>
        
        <!-- 4. AKSI -->
        <div class="card" id="aksi">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-bolt"></i> Aksi Kasir</div>
            </div>
            <div class="card-body">
                
                <h3 style="margin-bottom:8px;color:#2E7D32;">A. Konfirmasi Pembayaran</h3>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Validasi stok produk</li>
                    <li>Update payment → <strong>paid</strong></li>
                    <li>Update status → <strong>processing</strong></li>
                    <li>Kurangi stok & kirim notifikasi</li>
                </ul>
                
                <h3 style="margin:15px 0 8px;color:#2E7D32;">B. Update Status</h3>
                <div class="d-flex align-center gap-1 flex-wrap" style="padding:10px;background:#f8faf8;border-radius:8px;font-size:13px;">
                    <span class="badge badge-warning">Pending</span>
                    <span>→</span>
                    <span class="badge badge-purple">Processing</span>
                    <span>→</span>
                    <span class="badge badge-success">Ready</span>
                    <span>→</span>
                    <span class="badge badge-gray">Completed</span>
                </div>
                
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Status tidak bisa mundur! Hanya bisa maju sesuai flow.</span>
                </div>
                
                <h3 style="margin:15px 0 8px;color:#2E7D32;">C. Batalkan Pesanan</h3>
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Hanya yang belum completed</li>
                    <li>Stok otomatis dikembalikan</li>
                    <li>Notifikasi ke pembeli</li>
                </ul>
            </div>
        </div>
        
        <!-- 5. STRUK -->
        <div class="card" id="struk">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-receipt"></i> Cetak Struk</div>
            </div>
            <div class="card-body">
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Klik <strong>Struk</strong> di detail pesanan</li>
                    <li>Auto-print saat halaman terbuka</li>
                    <li>Informasi: nomor pesanan, kasir, item, total</li>
                </ul>
            </div>
        </div>
        
        <!-- 6. FLOW -->
        <div class="card" id="flow">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-sync-alt"></i> Flow Kerja</div>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:10px;">
                    <div class="alert alert-info">
                        <strong>1.</strong> Pembeli pesan via website → Order created
                    </div>
                    <div class="alert alert-warning">
                        <strong>2.</strong> Pembeli datang → Kasir <strong>Konfirmasi Bayar</strong> → Processing
                    </div>
                    <div class="alert" style="background:#F3E5F5;color:#6A1B9A;border-left:4px solid #9C27B0;">
                        <strong>3.</strong> Pesanan selesai → Kasir <strong>Siap Diambil</strong> → Ready
                    </div>
                    <div class="alert alert-success">
                        <strong>4.</strong> Pembeli ambil → Kasir <strong>Selesaikan</strong> → Completed
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 7. TIPS -->
        <div class="card" id="tips">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-lightbulb"></i> Tips</div>
            </div>
            <div class="card-body">
                <ul style="margin-left:20px;color:#555;line-height:2;">
                    <li>Dashboard auto-refresh setiap 60 detik (hari ini)</li>
                    <li>Filter tanggal untuk lihat riwayat</li>
                    <li>Sidebar menampilkan badge real-time</li>
                    <li>Semua aksi dicatat di activity log</li>
                </ul>
            </div>
        </div>
        
        <!-- 8. FAQ -->
        <div class="card" id="faq">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-question-circle"></i> FAQ</div>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:10px;">
                    <div>
                        <strong>Q: Bisakah kasir membuat pesanan baru?</strong>
                        <p style="color:#666;">A: Tidak. Kasir hanya memproses pesanan yang sudah dibuat pembeli.</p>
                    </div>
                    <div>
                        <strong>Q: Apa yang terjadi jika stok tidak cukup?</strong>
                        <p style="color:#666;">A: Sistem menolak konfirmasi dan menampilkan pesan error.</p>
                    </div>
                    <div>
                        <strong>Q: Bisakah status dimundurkan?</strong>
                        <p style="color:#666;">A: Tidak. Hanya bisa maju sesuai flow.</p>
                    </div>
                    <div>
                        <strong>Q: Apakah kasir bisa mengelola produk?</strong>
                        <p style="color:#666;">A: Tidak. Itu tugas admin.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </main>
</body>
</html>
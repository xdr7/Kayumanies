<?php
session_start();
$page = $_GET['page'] ?? 'order';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php require_once __DIR__ . '/../../includes/theme.php'; ?>
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main style="padding-top:100px;padding-bottom:60px;min-height:100vh;">
        <div class="container" style="max-width:800px;">
            
            <div class="section-header">
                <h1 class="section-title">❓ Bantuan</h1>
                <p class="section-desc">Informasi seputar pemesanan di Kayumanies</p>
            </div>
            
            <!-- Menu Bantuan -->
            <div class="d-flex gap-1 flex-wrap justify-center mb-2">
                <a href="?page=order" class="btn <?php echo $page=='order'?'btn-primary':'btn-outline'; ?> btn-sm">📝 Cara Pesan</a>
                <a href="?page=delivery" class="btn <?php echo $page=='delivery'?'btn-primary':'btn-outline'; ?> btn-sm">🚚 Pengiriman</a>
                <a href="?page=payment" class="btn <?php echo $page=='payment'?'btn-primary':'btn-outline'; ?> btn-sm">💳 Pembayaran</a>
                <a href="?page=faq" class="btn <?php echo $page=='faq'?'btn-primary':'btn-outline'; ?> btn-sm">💬 FAQ</a>
            </div>
            
            <!-- CONTENT -->
            <div class="card">
                <?php if ($page == 'order'): ?>
                <div class="card-header"><div class="card-title"><i class="fas fa-shopping-cart"></i> Cara Pesan</div></div>
                <div class="card-body">
                    <ol style="line-height:2;color:#555;">
                        <li><strong>Pilih Kue:</strong> Buka halaman <a href="products.php">Produk</a> dan pilih kue favorit Anda.</li>
                        <li><strong>Tambah ke Keranjang:</strong> Klik tombol "🛒 Keranjang" atau buka detail produk untuk melihat info lengkap.</li>
                        <li><strong>Checkout:</strong> Buka <a href="cart.php">Keranjang</a>, review pesanan, lalu klik "Lanjut ke Pembayaran".</li>
                        <li><strong>Isi Data:</strong> Lengkapi nama, nomor telepon, dan pilih tanggal pengambilan.</li>
                        <li><strong>Pilih Pembayaran:</strong> Pilih metode pembayaran yang tersedia (Cash, Transfer, QRIS).</li>
                        <li><strong>Konfirmasi:</strong> Klik "Buat Pesanan" untuk menyelesaikan.</li>
                        <li><strong>Datang ke Toko:</strong> Bayar sesuai metode yang dipilih dan ambil pesanan Anda!</li>
                    </ol>
                </div>
                
                <?php elseif ($page == 'delivery'): ?>
                <div class="card-header"><div class="card-title"><i class="fas fa-truck"></i> Pengiriman / Pengambilan</div></div>
                <div class="card-body">
                    <?php
                    try {
                        require_once __DIR__ . '/../../config/database.php';
                        $database = Database::getInstance();
                        $db = $database->getConnection();
                        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key IN ('store_address', 'opening_hours')");
                        $info = [];
                        foreach ($stmt->fetchAll() as $r) $info[$r['setting_key']] = $r['setting_value'];
                    } catch (Exception $e) {}
                    ?>
                    <p style="color:#555;line-height:2;">
                        <strong>📍 Alamat Pengambilan:</strong><br>
                        <?php echo htmlspecialchars($info['store_address'] ?? 'Jl. Kayu Manis No. 123, Jakarta'); ?>
                    </p>
                    <p style="color:#555;line-height:2;">
                        <strong>🕐 Jam Operasional:</strong><br>
                        <?php echo htmlspecialchars($info['opening_hours'] ?? '08:00 - 21:00'); ?>
                    </p>
                    <p style="color:#555;line-height:2;">
                        <strong>ℹ️ Informasi:</strong><br>
                        Saat ini kami hanya melayani <strong>pengambilan langsung di toko</strong> (pickup).<br>
                        Silakan datang sesuai jadwal yang Anda pilih saat checkout.<br>
                        Jangan lupa membawa bukti pembayaran (jika transfer/QRIS).
                    </p>
                </div>
                
                <?php elseif ($page == 'payment'): ?>
                <div class="card-header"><div class="card-title"><i class="fas fa-credit-card"></i> Pembayaran</div></div>
                <div class="card-body">
                    <p style="color:#555;line-height:2;">
                        <strong>Metode Pembayaran yang Tersedia:</strong>
                    </p>
                    <ul style="line-height:2;color:#555;margin-left:20px;">
                        <li><strong>💵 Tunai (Cash):</strong> Bayar langsung saat mengambil pesanan di toko.</li>
                        <li><strong>🏦 Transfer Bank:</strong> Transfer ke rekening yang tertera, upload bukti transfer.</li>
                        <li><strong>📱 QRIS:</strong> Scan QR code yang tersedia, upload bukti pembayaran.</li>
                    </ul>
                    <p style="color:#555;line-height:2;margin-top:10px;">
                        <strong>ℹ️ Catatan:</strong><br>
                        - Pembayaran non-tunai akan diverifikasi oleh kasir saat Anda datang.<br>
                        - Simpan bukti pembayaran untuk verifikasi.<br>
                        - Pesanan akan diproses setelah pembayaran dikonfirmasi.
                    </p>
                </div>
                
                <?php else: ?>
                <div class="card-header"><div class="card-title"><i class="fas fa-question-circle"></i> FAQ</div></div>
                <div class="card-body">
                    <div style="line-height:2;color:#555;">
                        <p><strong>Q: Apakah bisa pesan untuk hari yang sama?</strong></p>
                        <p>A: Ya, silakan pilih tanggal pengambilan saat checkout. Minimal H+1 dari tanggal pesan.</p>
                        <hr>
                        <p><strong>Q: Bagaimana cara membatalkan pesanan?</strong></p>
                        <p>A: Hubungi kami melalui chat atau datang langsung ke toko sebelum pesanan diproses.</p>
                        <hr>
                        <p><strong>Q: Apakah bisa custom tulisan di kue?</strong></p>
                        <p>A: Tulis permintaan khusus di kolom "Catatan" saat checkout. Kami akan menghubungi Anda jika ada pertanyaan.</p>
                        <hr>
                        <p><strong>Q: Berapa lama proses pesanan?</strong></p>
                        <p>A: Tergantung jenis kue dan antrian. Status pesanan bisa dipantau di dashboard Anda.</p>
                        <hr>
                        <p><strong>Q: Apakah bisa kirim ke alamat?</strong></p>
                        <p>A: Saat ini hanya melayani pengambilan di toko. Layanan delivery coming soon!</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
    
    <?php $base_path = '../../'; require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
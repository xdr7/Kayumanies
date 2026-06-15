<?php
header("HTTP/1.1 503 Service Unavailable");
header("Retry-After: 3600");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#8B4513">
    <title>Offline - Kayumanies Cake Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #FFF8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
        }
        .offline-card {
            background: white;
            border-radius: 20px;
            padding: 50px 30px;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .offline-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #8B4513;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover { background: #6B3410; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="offline-icon">📡</div>
        <h1>Anda Sedang Offline</h1>
        <p>Maaf, halaman ini tidak tersedia saat offline. Silakan periksa koneksi internet Anda dan coba lagi.</p>
        <a href="/kayumanies/" class="btn" onclick="window.location.reload();">Coba Lagi</a>
    </div>
    
    <script>
        // Check connectivity and reload when back online
        window.addEventListener('online', function() {
            window.location.reload();
        });
    </script>
</body>
</html>
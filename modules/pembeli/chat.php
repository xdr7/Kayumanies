<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/pembeli.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>
    
    <main style="padding-top:80px;min-height:100vh;">
        <div class="container" style="max-width:600px;">
            
            <!-- Header -->
            <div class="card" style="margin-bottom:0;border-radius:12px 12px 0 0;">
                <div class="card-header">
                    <div class="card-title">
                        <a href="dashboard.php" class="btn-detail">←</a>
                        💬 Chat Customer Service
                    </div>
                </div>
            </div>
            
            <!-- Pilih Kasir -->
            <div class="card" id="contactPicker" style="border-radius:0;margin-bottom:0;">
                <div class="card-body text-center">
                    <p class="text-gray mb-1">Pilih kasir yang ingin dihubungi:</p>
                    <div class="d-flex gap-1 justify-center flex-wrap" id="contactOptions">
                        <p class="text-gray">Memuat kontak...</p>
                    </div>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="card" id="chatArea" style="display:none;border-radius:0 0 12px 12px;margin-bottom:25px;padding:0;">
                <div style="background:#f9f9f9;padding:10px 15px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #eee;">
                    <span id="chatWithName" style="font-weight:600;font-size:13px;"></span>
                    <button class="btn-detail" onclick="switchContact()" style="margin-left:auto;font-size:10px;">↩ Ganti Kasir</button>
                </div>
                
                <div id="chatMessages" style="height:350px;overflow-y:auto;padding:15px;background:white;display:flex;flex-direction:column;gap:8px;">
                    <p class="text-center text-gray" style="margin:auto;">Mulai percakapan...</p>
                </div>
                
                <div style="padding:10px 15px;border-top:1px solid #eee;display:flex;gap:8px;">
                    <input type="text" id="messageInput" class="form-control" placeholder="Ketik pesan..." style="border-radius:20px;" onkeypress="if(event.key==='Enter')sendMessage()">
                    <button class="btn-primary" onclick="sendMessage()" style="padding:8px 16px;border-radius:20px;font-size:13px;"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
            
        </div>
    </main>
    
    <?php $base_path = '../../'; require_once __DIR__ . '/../../includes/footer.php'; ?>
    
    <script>
    var currentChat = null, interval = null;
    var userId = <?php echo $_SESSION['user_id']; ?>;
    
    // Load KASIR ONLY
    fetch('../../api/chat_api.php?action=contacts')
        .then(r => r.json())
        .then(data => {
            var contacts = [];
            if (data.contacts) {
                // Filter kasir saja
                contacts = data.contacts.filter(function(c) { return c.role == 'kasir'; });
            }
            
            if (contacts.length > 0) {
                var html = '';
                contacts.forEach(function(c) {
                    html += '<button class="btn btn-outline" onclick="openChat(' + c.id + ',\'' + c.full_name + '\')" style="padding:12px 20px;">' +
                        '💳 ' + c.full_name + '</button>';
                });
                document.getElementById('contactOptions').innerHTML = html;
            } else {
                document.getElementById('contactOptions').innerHTML = '<p class="text-gray">Tidak ada kasir tersedia</p>';
            }
        });
    
    function openChat(id, name) {
        currentChat = id;
        document.getElementById('contactPicker').style.display = 'none';
        document.getElementById('chatArea').style.display = 'block';
        document.getElementById('chatWithName').textContent = '💬 ' + name;
        loadMessages();
        if (interval) clearInterval(interval);
        interval = setInterval(loadMessages, 3000);
    }
    
    function switchContact() {
        currentChat = null;
        if (interval) clearInterval(interval);
        document.getElementById('contactPicker').style.display = 'block';
        document.getElementById('chatArea').style.display = 'none';
    }
    
    function loadMessages() {
        if (!currentChat) return;
        fetch('../../api/chat_api.php?action=get&other_id=' + currentChat)
            .then(r => r.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    var html = '';
                    data.messages.forEach(function(m) {
                        var isMe = m.sender_id == userId;
                        html += '<div style="display:flex;flex-direction:column;align-items:' + (isMe?'flex-end':'flex-start') + ';">' +
                            '<div style="max-width:75%;padding:8px 12px;border-radius:12px;font-size:12px;' +
                            (isMe?'background:#8B4513;color:white;border-bottom-right-radius:4px;':'background:#f0f0f0;border-bottom-left-radius:4px;') + '">' +
                            m.message + '</div>' +
                            '<small style="color:#999;font-size:9px;margin-top:1px;">' + (isMe?'':m.sender_name+' · ') + m.created_at.substr(11,5) + '</small>' +
                        '</div>';
                    });
                    document.getElementById('chatMessages').innerHTML = html;
                    document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
                }
            });
    }
    
    function sendMessage() {
        var input = document.getElementById('messageInput');
        var msg = input.value.trim();
        if (!msg || !currentChat) return;
        
        var f = new FormData(); f.append('receiver_id', currentChat); f.append('message', msg);
        fetch('../../api/chat_api.php?action=send', { method: 'POST', body: f })
            .then(r => r.json())
            .then(data => { if (data.success) { input.value = ''; loadMessages(); } });
    }
    </script>
</body>
</html>
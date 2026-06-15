<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','kasir'])) { header('Location: ../../modules/auth/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Kasir Kayumanies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/kasir.css">
    <?php $pwa_base = '../../'; require_once __DIR__ . '/../../includes/pwa.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    
    <main class="kasir-main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>💬 Chat</h1>
                <div class="date">Pesan masuk dari pembeli</div>
            </div>
            <div class="top-bar-right">
                <span id="unreadBadge" class="badge badge-warning" style="display:none;"></span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-inbox"></i> Pesan Masuk</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Pengirim</th><th>Pesan Terakhir</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="inboxTable">
                            <tr><td colspan="5" class="text-center text-gray p-2">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- MODAL CHAT - DI TENGAH -->
    <div class="modal" id="chatModal">
        <div class="modal-dialog modal-sm" style="height:70vh;display:flex;flex-direction:column;">
            <div class="modal-header">
                <h2 class="modal-title" id="chatModalTitle">💬 Chat</h2>
                <button class="modal-close" onclick="closeChat()">&times;</button>
            </div>
            <div class="modal-body" style="flex:1;overflow-y:auto;padding:15px;background:#f5f5f5;display:flex;flex-direction:column;gap:6px;" id="chatMessages">
                <p class="text-center text-gray">Mulai percakapan</p>
            </div>
            <div style="padding:12px 15px;border-top:1px solid #e8efe8;display:flex;gap:8px;">
                <input type="text" id="messageInput" class="form-control" placeholder="Ketik pesan..." onkeypress="if(event.key==='Enter')sendMessage()">
                <button class="btn btn-primary" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    
    <script>
    var currentChat = null, interval = null;
    var userId = <?php echo $_SESSION['user_id']; ?>;
    
    function loadInbox() {
        fetch('../../api/chat_api.php?action=inbox')
            .then(r => r.json())
            .then(data => {
                var totalUnread = 0;
                var html = '';
                
                if (data.inbox && data.inbox.length > 0) {
                    data.inbox.forEach(function(item) {
                        totalUnread += parseInt(item.unread || 0);
                        var unreadLabel = item.unread > 0 ? '<span class="badge badge-danger">' + item.unread + ' baru</span>' : '<span class="text-sm text-gray">Dibaca</span>';
                        
                        html += '<tr>' +
                            '<td><strong>' + item.full_name + '</strong> <small class="text-gray">(' + item.role + ')</small></td>' +
                            '<td><small>' + (item.last_message ? item.last_message.substr(0,35) + '...' : '-') + '</small></td>' +
                            '<td><small>' + (item.last_time ? item.last_time.substr(11,5) : '') + '</small></td>' +
                            '<td>' + unreadLabel + '</td>' +
                            '<td><button class="btn btn-sm btn-primary" onclick="openChat(' + item.id + ',\'' + item.full_name + '\')">💬 Buka</button></td>' +
                        '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center text-gray p-2">Belum ada pesan masuk</td></tr>';
                }
                
                document.getElementById('inboxTable').innerHTML = html;
                
                var badge = document.getElementById('unreadBadge');
                if (totalUnread > 0) {
                    badge.textContent = totalUnread + ' pesan baru';
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            });
    }
    
    function openChat(id, name) {
        currentChat = id;
        document.getElementById('chatModalTitle').textContent = '💬 ' + name;
        document.getElementById('chatModal').style.display = 'block';
        document.getElementById('chatModal').classList.add('show');
        document.getElementById('chatMessages').innerHTML = '<p class="text-center text-gray">Memuat...</p>';
        
        loadMessages();
        if (interval) clearInterval(interval);
        interval = setInterval(function() { loadMessages(); loadInbox(); }, 3000);
    }
    
    function closeChat() {
        currentChat = null;
        if (interval) clearInterval(interval);
        document.getElementById('chatModal').style.display = 'none';
        document.getElementById('chatModal').classList.remove('show');
        loadInbox();
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
                        html += '<div style="align-self:' + (isMe?'flex-end':'flex-start') + ';max-width:75%;">' +
                            '<div style="padding:8px 12px;border-radius:10px;font-size:13px;' +
                            (isMe?'background:#4CAF50;color:white;':'background:white;border:1px solid #ddd;') + '">' +
                            m.message + '</div>' +
                            '<small style="color:#999;font-size:9px;">' + (isMe?'':m.sender_name+' · ') + m.created_at.substr(11,5) + '</small>' +
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
            .then(data => { if (data.success) { input.value = ''; loadMessages(); loadInbox(); } });
    }
    
    loadInbox();
    setInterval(loadInbox, 10000);
    
    window.addEventListener('click', function(e) { if (e.target === document.getElementById('chatModal')) closeChat(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeChat(); });
    </script>
</body>
</html>
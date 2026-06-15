<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();
$action = $_GET['action'] ?? '';

// Kirim pesan
if ($action == 'send' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Login dulu']);
        exit;
    }
    
    $sender_id = $_SESSION['user_id'];
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Pesan kosong']);
        exit;
    }
    
    $stmt = $db->prepare("INSERT INTO chats (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $message]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

// Get messages between two users
if ($action == 'get') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $other_id = intval($_GET['other_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT c.*, u.full_name as sender_name FROM chats c JOIN users u ON c.sender_id = u.id 
            WHERE ((c.sender_id = ? AND c.receiver_id = ?) OR (c.sender_id = ? AND c.receiver_id = ?))
            ORDER BY c.created_at ASC LIMIT 100");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    $messages = $stmt->fetchAll();
    
    // Mark as read
    $stmt = $db->prepare("UPDATE chats SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $stmt->execute([$user_id, $other_id]);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// Get contacts (for pembeli)
if ($action == 'contacts') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $stmt = $db->query("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'kasir') AND is_active = 1 LIMIT 10");
    $contacts = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'contacts' => $contacts]);
    exit;
}

// Inbox - daftar user yang pernah chat (for admin/kasir)
if ($action == 'inbox') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Ambil user yang pernah chat dengan admin/kasir
    $stmt = $db->prepare("
        SELECT DISTINCT u.id, u.full_name, u.role,
            (SELECT message FROM chats WHERE ((sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM chats WHERE ((sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) ORDER BY created_at DESC LIMIT 1) as last_time,
            (SELECT COUNT(*) FROM chats WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread
        FROM chats c
        JOIN users u ON (u.id = c.sender_id OR u.id = c.receiver_id)
        WHERE (c.sender_id = ? OR c.receiver_id = ?) AND u.id != ?
        ORDER BY last_time DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $inbox = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'inbox' => $inbox]);
    exit;
}

// Unread count
if ($action == 'unread') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM chats WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['count' => $stmt->fetch()['total']]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
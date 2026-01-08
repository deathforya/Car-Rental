<?php
require_once __DIR__ . '/../config/db.php';
session_start();
header('Content-Type: application/json');

// Auth Check
if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type']; // 'admin' or 'customer'
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ------------------------------------------------------------------
// AUTO-CLEANUP: Delete messages older than 48 hours on every request
// ------------------------------------------------------------------
mysqli_query($conn, "DELETE FROM chat_messages WHERE created_at < NOW() - INTERVAL 48 HOUR");
// ------------------------------------------------------------------


if ($action === 'send_message') {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit;
    }

    if ($user_type === 'admin') {
        // Admin replying to specific user
        $target_user_id = intval($_POST['target_user_id'] ?? 0);
        if ($target_user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Target user required']);
            exit;
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'admin', ?)");
        mysqli_stmt_bind_param($stmt, 'is', $target_user_id, $message);
    } else {
        // User requesting admin
        $stmt = mysqli_prepare($conn, "INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'user', ?)");
        mysqli_stmt_bind_param($stmt, 'is', $current_user_id, $message);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }

} elseif ($action === 'get_messages') {
    
    // For admin, must specify which user's chat to view
    // For user, view their own chat
    $target_uid = ($user_type === 'admin') ? intval($_GET['target_user_id'] ?? 0) : $current_user_id;

    if ($target_uid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit;
    }

    $after_id = intval($_GET['after_id'] ?? 0);

    $sql = "SELECT * FROM chat_messages WHERE user_id = ? AND id > ? ORDER BY id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $target_uid, $after_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $messages[] = $row;
    }

    // Mark admin messages as read if user is viewing, or user messages as read if admin is viewing
    if ($user_type === 'customer') {
        mysqli_query($conn, "UPDATE chat_messages SET is_read=1 WHERE user_id=$current_user_id AND sender='admin'");
    } elseif ($user_type === 'admin' && !empty($messages)) {
         mysqli_query($conn, "UPDATE chat_messages SET is_read=1 WHERE user_id=$target_uid AND sender='user'");
    }

    echo json_encode(['status' => 'success', 'messages' => $messages]);

} elseif ($action === 'get_conversations' && $user_type === 'admin') {
    
    // Get list of users who have chatted
    $sql = "SELECT u.id, u.name, u.email,
            (SELECT message FROM chat_messages WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as last_msg,
            (SELECT created_at FROM chat_messages WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as last_time,
            (SELECT COUNT(*) FROM chat_messages WHERE user_id = u.id AND sender='user' AND is_read=0) as unread
            FROM users u
            WHERE EXISTS (SELECT 1 FROM chat_messages WHERE user_id = u.id)
            ORDER BY last_time DESC";
            
    $res = mysqli_query($conn, $sql);
    $users = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
    echo json_encode(['status' => 'success', 'users' => $users]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>

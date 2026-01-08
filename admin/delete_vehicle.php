<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.html'); exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: dashboard.php'); exit; }

// Fetch image name to delete file
$res = mysqli_query($conn, "SELECT image FROM vehicles WHERE id = $id LIMIT 1");
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    if (!empty($row['image'])) {
        $file = __DIR__ . '/../uploads/vehicles/' . $row['image'];
        if (file_exists($file)) unlink($file);
    }
}

mysqli_query($conn, "DELETE FROM vehicles WHERE id = $id");
header('Location: dashboard.php');
exit;

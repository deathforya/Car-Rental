<?php
session_start();

// 🔐 Prevent browser caching (important)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));

    if (empty($email) || empty($password)) {
        header('Location: login.html');
        exit;
    }

    $sql = "SELECT id, name, email, user_type 
            FROM users 
            WHERE email = '$email' AND password = '$password' 
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        die('DB error: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($res) === 1) {

        // 🔐 Prevent session fixation attack
        session_regenerate_id(true);

        $user = mysqli_fetch_assoc($res);

        // ✅ Set session variables
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type']  = $user['user_type'];

        // 🔁 Role-based redirect
        if ($user['user_type'] === 'admin') {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../user/dashboard.php');
        }
        exit;

    } else {
        // ❌ Invalid credentials
        header('Location: login.html');
        exit;
    }
}

// Fallback
header('Location: login.html');
exit;

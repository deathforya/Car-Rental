<?php
session_start();

// 🚫 Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// 🔐 Unset all session variables
$_SESSION = [];

// 🔥 Destroy session cookie (important)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 🧹 Destroy the session
session_destroy();

// 🔁 Redirect to login page
header('Location: login.html');
exit;

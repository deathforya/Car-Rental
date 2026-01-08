<?php
session_start();

// 🚫 Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/db.php';

/**
 * ===============================
 * RESEND OTP LOGIC
 * ===============================
 */
if (isset($_GET['resend']) && !empty($_SESSION['pending_register'])) {

    $pending = &$_SESSION['pending_register'];

    // ⏳ OTP expiry reset (5 minutes)
    $otp = strval(random_int(100000, 999999));
    $pending['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
    $pending['created_at'] = time();

    require_once __DIR__ . '/../includes/mailer.php';

    $to = $pending['email'];
    $subject = "DriveNow verification code";
    $message = "Your DriveNow verification code is: $otp\n\nThis code is valid for 5 minutes.";

    send_email($to, $subject, $message);

    header('Location: verify_otp.php');
    exit;
}

/**
 * ===============================
 * NEW REGISTRATION
 * ===============================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        header('Location: register.html');
        exit;
    }

    // 🔎 Check if email already exists
    $emailEsc = mysqli_real_escape_string($conn, $email);
    $sql = "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die('DB error: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($res) > 0) {
        // Email already registered
        header('Location: register.html');
        exit;
    }

    // 🔐 Generate OTP
    $otp = strval(random_int(100000, 999999));

    // 🧠 Store pending registration securely in session
    $_SESSION['pending_register'] = [
        'name'       => $name,
        'email'      => $email,
        'phone'      => $phone,
        'password'   => $password, // unchanged (your app logic)
        'otp_hash'   => password_hash($otp, PASSWORD_DEFAULT),
        'created_at' => time() // for expiry check
    ];

    // 📧 Send OTP email
    require_once __DIR__ . '/../includes/mailer.php';

    $subject = "DriveNow verification code";
    $message = "Hello $name,\n\nYour DriveNow verification code is: $otp\n\nThis code is valid for 5 minutes.";

    send_email($email, $subject, $message);

    header('Location: verify_otp.php');
    exit;
}

// Fallback
header('Location: register.html');
exit;

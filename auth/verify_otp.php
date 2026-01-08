<?php
session_start();

// 🚫 Prevent browser caching (VERY IMPORTANT)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/db.php';

// ❌ If no pending registration → redirect
if (empty($_SESSION['pending_register'])) {
    header('Location: register.html');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = trim($_POST['otp'] ?? '');

    if ($code === '') {
        $error = 'Please enter the OTP sent to your email.';
    } else {

        $pending = $_SESSION['pending_register'];

        // ⏳ OTP expiry check (5 minutes = 300 seconds)
        if (time() - $pending['created_at'] > 300) {
            unset($_SESSION['pending_register']);
            $error = 'OTP expired. Please register again.';
        }
        // 🔐 OTP hash verification
        elseif (!password_verify($code, $pending['otp_hash'])) {
            $error = 'Invalid OTP. Please check and try again.';
        }
        else {
            // ✅ OTP VALID → create user

            // 🔐 Prevent session fixation
            session_regenerate_id(true);

            $name     = mysqli_real_escape_string($conn, $pending['name']);
            $email    = mysqli_real_escape_string($conn, $pending['email']);
            $password = mysqli_real_escape_string($conn, $pending['password']);
            $phone    = mysqli_real_escape_string($conn, $pending['phone']);

            $sql = "INSERT INTO users 
                    (name, email, password, phone, user_type, created_at)
                    VALUES 
                    ('$name', '$email', '$password', '$phone', 'customer', NOW())";

            if (mysqli_query($conn, $sql)) {

                $user_id = mysqli_insert_id($conn);

                // 🧹 Clear OTP session
                unset($_SESSION['pending_register']);

                // ✅ Auto-login after verification
                $_SESSION['user_id']    = $user_id;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type']  = 'customer';

                header('Location: ../user/dashboard.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/login.css">
  <link rel="stylesheet" href="../assets/css/otp.css">
  <title>Verify OTP - DriveNow</title>
</head>
<body class="auth-page">

<main class="auth-card otp-card">
  <h2>Verify your email</h2>

  <p class="muted">
    Enter the code sent to
    <strong><?php echo htmlspecialchars($_SESSION['pending_register']['email']); ?></strong>
  </p>

  <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="verify_otp.php">
    <label>
      <span>OTP code</span>
      <input
        type="text"
        name="otp"
        required
        pattern="[0-9]{6}"
        placeholder="123456"
        maxlength="6">
    </label>

    <button type="submit" class="btn-primary">Verify</button>
  </form>

  <p class="small">
    Didn't get it?
    <a href="register.php?resend=1">Resend code</a>
  </p>
</main>

</body>
</html>

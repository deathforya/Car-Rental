<?php
// Prevent header HTML from rendering more than once (guard)
if (!defined('DRIVENOW_HEADER_INCLUDED')) {
    define('DRIVENOW_HEADER_INCLUDED', true);

    if (session_status() === PHP_SESSION_NONE) session_start();

    // Simple access helper
    function require_login() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /drivenow/auth/login.html');
            exit;
        }
    }

    // Allow pages to provide $page_css, $page_class and optional $base_url
    if (!isset($default_common_css)) {
        $default_common_css = '../assets/css/common.css';
    }
    if (!isset($base_url)) {
        // site root path used for consistent auth links
        $base_url = '/drivenow';
    }
    ?>
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <link rel="stylesheet" href="<?php echo htmlspecialchars($default_common_css); ?>">
      <?php if (!empty($page_css)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($page_css); ?>">
      <?php endif; ?>
      <title>DriveNow</title>
    </head>
    <body<?php echo !empty($page_class) ? ' class="'.htmlspecialchars($page_class).'"' : ''; ?>>
      <header class="site-header">
        <div class="container header-inner">
          <div class="brand">
            <a class="brand-link" href="<?php echo htmlspecialchars($base_url); ?>">
        
              <span class="brand-title">DriveNow</span>
            </a>
          </div>

          <nav class="site-nav" aria-label="Main navigation">
            <ul class="nav-list">
              <li><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>">Home</a></li>
              <li><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/search.php">Browse</a></li>
              <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                <li><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/admin/dashboard.php" style="color:#d63384;font-weight:bold;">Admin Dashboard</a></li>
              <?php endif; ?>
            </ul>

            <div class="auth-buttons">
              <?php if (!empty($_SESSION['user_name'])): ?>
                <?php
                // Try to get avatar from session or DB
                $avatar = $_SESSION['profile_picture'] ?? 'default_avatar.png';
                if (empty($_SESSION['profile_picture']) && isset($conn) && !empty($_SESSION['user_id'])) {
                    $uid = $_SESSION['user_id'];
                    $u_res = mysqli_query($conn, "SELECT profile_picture FROM users WHERE id=$uid");
                    if ($u_res && $u_row = mysqli_fetch_assoc($u_res)) {
                        if (!empty($u_row['profile_picture'])) {
                            $avatar = $u_row['profile_picture'];
                            $_SESSION['profile_picture'] = $avatar;
                        }
                    }
                }
                // Construct path - checks if it's already a full path or just filename
                $avatar_url = (strpos($avatar, '/') !== false) ? $avatar : ($base_url . '/uploads/avatars/' . $avatar);
                // Fallback if file doesn't exist (basic check) or if it's default
                if ($avatar == 'default_avatar.png') $avatar_url = $base_url . '/assets/img/default_avatar.png';
                ?>
                <img src="<?= htmlspecialchars($avatar_url) ?>" class="user-avatar" alt="User" style="width:32px;height:32px;border-radius:50%;object-fit:cover;vertical-align:middle;border:2px solid #eef6ff;">
                <span class="welcome">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/user/settings.php">Settings</a>
                <a class="btn-outline" href="<?php echo htmlspecialchars($base_url); ?>/auth/logout.php">Logout</a>
              <?php else: ?>
                <a class="btn-outline" href="<?php echo htmlspecialchars($base_url); ?>/auth/login.html">Login</a>
                <a class="btn-primary" href="<?php echo htmlspecialchars($base_url); ?>/auth/register.html">Register</a>
              <?php endif; ?>
            </div>
          </nav>
        </div>
      </header>
    <?php
} // end guard
?>

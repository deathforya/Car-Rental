<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Auth Guard
if (empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit;
}

$uid = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Update Profile (Name, Password)
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $pass = trim($_POST['password']);
        
        $sql = "UPDATE users SET name = '$name' WHERE id = $uid";
        if (!empty($pass)) {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = '$name', password = '$hashed' WHERE id = $uid";
        }
        mysqli_query($conn, $sql);
        $_SESSION['user_name'] = $name; // Update session
        
        // Handle Profile Picture
        if (!empty($_FILES['profile_pic']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $dir = __DIR__ . '/../uploads/avatars/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $new_name = 'u_' . $uid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $new_name)) {
                     mysqli_query($conn, "UPDATE users SET profile_picture = '$new_name' WHERE id = $uid");
                     $_SESSION['profile_picture'] = $new_name; // Update session
                }
            }
        }
        $success_msg = "Profile updated successfully!";
    }

    // Handle Profile Picture Removal (Independent of update_profile)
    if (isset($_POST['remove_pic'])) {
        mysqli_query($conn, "UPDATE users SET profile_picture = NULL WHERE id = $uid");
        unset($_SESSION['profile_picture']); // Clear from session
        $success_msg = "Profile picture removed.";
        // Refresh page to show default
        echo "<script>window.location.href='settings.php';</script>";
        exit;
    }

    // 2. Upload Verification ID
    if (isset($_POST['upload_id'])) {
        // ... (existing upload logic) ...
        if (!empty($_FILES['id_doc']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['id_doc']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $dir = __DIR__ . '/../uploads/users_id/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $new_name = 'id_' . $uid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['id_doc']['tmp_name'], $dir . $new_name)) {
                    // Update DB and reset verification status
                    $path = 'uploads/users_id/' . $new_name;
                    $path_esc = mysqli_real_escape_string($conn, $path);
                    mysqli_query($conn, "UPDATE users SET id_document_path = '$path_esc', is_verified = 0 WHERE id = $uid");
                    $success_msg = "ID Document uploaded! Pending admin approval.";
                }
            } else {
                $error_msg = "Invalid file type. Only JPG, PNG, PDF allowd.";
            }
        }
    }
}

// Fetch User Data
$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $uid"));

// Page settings
$page_title = "Settings - DriveNow";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin: 20px 0;">
        <h1>Account Settings</h1>
        <a href="dashboard.php" class="btn-outline">Back to Dashboard</a>
    </div>

    <?php if ($success_msg): ?>
        <div style="background:#d4edda;color:#155724;padding:15px;border-radius:6px;margin-bottom:20px;"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:6px;margin-bottom:20px;"><?= $error_msg ?></div>
    <?php endif; ?>

    <style>
        .settings-container { max-width: 800px; margin: 40px auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .settings-card { background: var(--card); padding: 30px; border-radius: var(--radius); box-shadow: var(--shadow); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .avatar-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid #eee; }
        .btn-text-danger { background:none; border:none; color:#dc3545; cursor:pointer; font-size:0.9em; text-decoration:underline; }
        @media(max-width:768px) { .settings-container { grid-template-columns: 1fr; } }
    </style>

    <div class="settings-container">
        <!-- Profile Section -->
        <div class="settings-card">
            <h3>Profile Details</h3>
            <form method="POST" enctype="multipart/form-data">
                <div style="text-align:center;">
                    <img src="<?= $u['profile_picture'] ? '../uploads/avatars/'.$u['profile_picture'] : '../assets/img/default_avatar.png' ?>" class="avatar-preview">
                    
                    <?php if ($u['profile_picture']): ?>
                        <div style="margin-bottom:10px;">
                            <button type="submit" name="remove_pic" class="btn-text-danger" onclick="return confirm('Remove profile picture?')">Remove Picture</button>
                        </div>
                    <?php endif; ?>

                    <input type="file" name="profile_pic" style="display:block;margin:0 auto 20px;">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                </div>
                <!-- ... existing form fields ... -->
                <div class="form-group">
                    <label>Email (Read only)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" readonly disabled style="background:#f5f5f5;">
                </div>
                <div class="form-group">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control" placeholder="******">
                </div>
                <button type="submit" name="update_profile" class="btn-primary" style="width:100%;">Save Changes</button>
            </form>
        </div>

        <!-- Verification Section -->
        <?php if ($_SESSION['user_type'] !== 'admin'): ?>
        <div class="settings-card">
            <h3>Identity Verification</h3>
            <p style="color:#666;font-size:0.9em;margin-bottom:20px;">Upload your Driving License or ID to rent premium vehicles.</p>

            <div style="background:#f8f9fa;padding:15px;border-radius:6px;margin-bottom:20px;">
                <strong>Status:</strong> 
                <?php if ($u['is_verified']): ?>
                    <span style="color:green;font-weight:bold;">✅ Verified</span>
                <?php elseif ($u['id_document_path']): ?>
                    <span style="color:#d39e00;font-weight:bold;">⏳ Pending Review</span>
                <?php else: ?>
                    <span style="color:red;font-weight:bold;">❌ Not Verified</span>
                <?php endif; ?>
            </div>

            <?php if (!$u['is_verified']): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Upload Document (JPG, PNG, PDF)</label>
                    <input type="file" name="id_doc" class="form-control" required>
                </div>
                <button type="submit" name="upload_id" class="btn-primary" style="width:100%;">Upload ID</button>
            </form>
            <?php else: ?>
                <p>You are all set! Your identity is verified.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

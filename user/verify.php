<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../auth/login.html");
    exit;
}

require_once __DIR__ . '/../config/db.php';
$page_css = '../assets/css/user_dashboard.css';
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$error = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['id_doc'])) {
    $file = $_FILES['id_doc'];
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $error = "Invalid file type. Only JPG, PNG, PDF allowed.";
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $error = "File too large. Max 5MB.";
    } else {
        // Create dir if not exists
        $upload_dir = __DIR__ . '/../uploads/users_id/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $dest = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $path = 'uploads/users_id/' . $filename; // Relative for DB
            
            // Update DB
            // We set is_verified = 0 (false) until Admin approves, but we save the path
            $uid = $_SESSION['user_id'];
            $sql = "UPDATE users SET id_document_path = '$path' WHERE id = $uid";
            if (mysqli_query($conn, $sql)) {
                $msg = "Document uploaded successfully! Verification pending admin review.";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        } else {
            $error = "Failed to move uploaded file.";
        }
    }
}
?>

<main class="page-container">
    <div class="inner" style="max-width: 600px; margin: 0 auto;">
        <h2>Verify Identity</h2>
        <p>To rent premium vehicles, we need to verify your driving license.</p>

        <?php if ($msg): ?>
            <div style="background:#d4edda;color:#155724;padding:15px;margin-bottom:20px;border-radius:4px;">
                <?= $msg; ?>
            </div>
            <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
        <?php elseif ($error): ?>
            <div style="background:#f8d7da;color:#721c24;padding:15px;margin-bottom:20px;border-radius:4px;">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!$msg): ?>
        <form method="POST" enctype="multipart/form-data" style="margin-top: 20px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="margin-bottom: 20px;">
                <label style="display:block;margin-bottom:10px;font-weight:bold;">Upload Driving License (JPG, PNG, PDF)</label>
                <input type="file" name="id_doc" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;">
            </div>
            <button type="submit" class="btn-primary" style="width:100%">Submit for Verification</button>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

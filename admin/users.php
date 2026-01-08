<?php
session_start();

// Auth Guard
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.html");
    exit;
}

require_once __DIR__ . '/../config/db.php';
$page_css = '../assets/css/admin.css';
$page_class = 'admin-area';
require_once __DIR__ . '/../includes/header.php';

// Handle Deletion
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    // Prevent deleting self
    if ($del_id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
        $msg = "User deleted successfully.";
    } else {
        $error = "Cannot delete yourself.";
    }
}

// Fetch Users
$sql = "SELECT id, name, email, user_type, profile_picture, is_verified, created_at FROM users ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);
?>

<main class="admin-container">
    <div class="admin-header">
        <div>
            <h2>Manage Users</h2>
            <p class="small">View and manage registered users</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn-outline">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($msg)) echo "<div style='background:#d4edda;color:#155724;padding:10px;margin-bottom:20px;border-radius:4px;'>$msg</div>"; ?>
    <?php if (isset($error)) echo "<div style='background:#f8d7da;color:#721c24;padding:10px;margin-bottom:20px;border-radius:4px;'>$error</div>"; ?>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Verified</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;">
                            <img src="<?= $row['profile_picture'] ? '../uploads/avatars/'.$row['profile_picture'] : '../assets/img/default_avatar.png' ?>" 
                                 style="width:32px;height:32px;border-radius:50%;margin-right:10px;object-fit:cover;">
                            <?= htmlspecialchars($row['name']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <span style="background:<?= $row['user_type']=='admin'?'#ebdcfc':'#eef6ff'?>;color:<?= $row['user_type']=='admin'?'#6f42c1':'#0d6efd'?>;padding:2px 8px;border-radius:4px;font-size:0.85em;font-weight:600;">
                            <?= ucfirst($row['user_type']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['is_verified']): ?>
                            <span style="color:green;">✔ Verified</span>
                        <?php else: ?>
                            <span style="color:#999;">Not Verified</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                        <a href="users.php?delete_id=<?= $row['id'] ?>" 
                           class="btn-danger" 
                           onclick="return confirm('Are you sure? This will delete the user and their bookings.')"
                           style="padding:5px 10px;font-size:0.9em;">
                           Delete
                        </a>
                        <?php else: ?>
                            <span style="color:#999;font-size:0.9em;">(You)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

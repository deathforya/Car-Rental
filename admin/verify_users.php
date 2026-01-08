<?php
require_once __DIR__ . '/../config/db.php';
$page_css = '../assets/css/admin.css';
$page_class = 'admin-area';
require_once __DIR__ . '/../includes/header.php';

// Auth Check
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.html'); exit;
}

// Handle Actions
if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    if ($_GET['action'] === 'approve') {
        mysqli_query($conn, "UPDATE users SET is_verified = 1 WHERE id = $uid");
        echo "<script>alert('User Verified!'); window.location='verify_users.php';</script>";
    } elseif ($_GET['action'] === 'reject') {
        // Reject: clear the path so they can upload again, or keep it but mark status? 
        // Simple MVP: Clear it + verified=0
        mysqli_query($conn, "UPDATE users SET is_verified = 0, id_document_path = NULL WHERE id = $uid");
        echo "<script>alert('User Verification Rejected. They can upload again.'); window.location='verify_users.php';</script>";
    }
}

// Fetch Pending Verifications
// Logic: Users who have content in id_document_path but is_verified is 0
$sql = "SELECT id, name, email, id_document_path, created_at FROM users WHERE id_document_path IS NOT NULL AND is_verified = 0 ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);
?>

<main class="admin-container">
    <div class="admin-header">
        <h2>Verify Users</h2>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>

    <div class="admin-card">
        <h3>Pending Requests</h3>
        <?php if (mysqli_num_rows($res) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Document</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                                <span style="font-size:0.9em;color:#666;"><?= htmlspecialchars($u['email']) ?></span>
                            </td>
                            <td>
                                <a href="../<?= htmlspecialchars($u['id_document_path']) ?>" target="_blank" class="btn-outline" style="font-size:0.9em;">View ID</a>
                            </td>
                            <td>
                                <a href="verify_users.php?action=approve&uid=<?= $u['id'] ?>" class="btn-primary" style="padding:5px 10px;font-size:0.9em;">Approve</a>
                                <a href="verify_users.php?action=reject&uid=<?= $u['id'] ?>" class="btn-danger" style="padding:5px 10px;font-size:0.9em;" onclick="return confirm('Reject this document?')">Reject</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="padding:20px;color:#666;">No pending verifications.</p>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
session_start();

/* 🔒 SESSION GUARD (FIRST) */
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../auth/login.html");
    exit;
}

/* 🚫 HARD CACHE BLOCK (THIS IS THE FIX) */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/db.php';

/* Page-specific settings */
$page_css   = '../assets/css/user_dashboard.css';
$page_class = 'user-dashboard';

/* ⚠️ INCLUDE HEADER AFTER SECURITY */
require_once __DIR__ . '/../includes/header.php';

/* Show available vehicles */
$sql = "SELECT * FROM vehicles WHERE status = 'available' ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);
?>
<main class="page-container">
  <div class="inner">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
        <h2>My Dashboard</h2>
        <div>
            <a href="chat.php" class="btn-outline" style="margin-right:10px;">Chat Support</a>
            <a href="../search.php" class="btn-primary">Book New Car</a>
        </div>
    </div>

    <!-- 1. Verification Status -->
    <!-- 1. Verification Status (Simplified) -->
    <?php 
    // Get fresh user data
    $uid = $_SESSION['user_id'];
    $u_res = mysqli_query($conn, "SELECT is_verified, id_document_path FROM users WHERE id = $uid");
    $u_data = mysqli_fetch_assoc($u_res);
    ?>
    
    <?php if (!$u_data['is_verified']): ?>
        <section class="dashboard-section" style="background:#fff3cd;padding:15px;border-radius:8px;margin-bottom:30px;border-left:5px solid #ffc107;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <strong>⚠️ Account Not Verified</strong>
                <p style="margin:5px 0 0 0;font-size:0.9em;color:#856404;">You need to verify your identity to rent premium cars.</p>
            </div>
            <a href="settings.php" class="btn-primary" style="font-size:0.9em;">Verify Now</a>
        </section>
    <?php endif; ?>

    <!-- 2. My Bookings -->
    <h3>My Bookings</h3>
    <?php
    $book_sql = "SELECT b.*, v.name as vehicle_name, v.image 
                 FROM bookings b 
                 JOIN vehicles v ON b.vehicle_id = v.id 
                 WHERE b.user_id = $uid 
                 ORDER BY b.booking_date DESC";
    $book_res = mysqli_query($conn, $book_sql);
    ?>

    <?php if (mysqli_num_rows($book_res) > 0): ?>
        <div class="bookings-list" style="display:grid;gap:20px;margin-top:20px;">
            <?php while ($b = mysqli_fetch_assoc($book_res)): ?>
                <div class="booking-card" style="background:#fff;padding:20px;display:flex;gap:20px;border-radius:8px;border:1px solid #eee;">
                    <div style="width:100px;height:80px;background:#f5f5f5;background-image:url('../uploads/vehicles/<?= $b['image']?>');background-size:cover;border-radius:4px;"></div>
                    <div>
                        <h4 style="margin:0 0 5px 0;"><?= htmlspecialchars($b['vehicle_name']) ?></h4>
                        <p style="margin:0;color:#555;">
                            <?= $b['booking_date'] ?> to <?= $b['return_date'] ?? '...' ?>
                        </p>
                        <p style="margin:5px 0 0 0;">
                            Status: <strong><?= ucfirst($b['status']) ?></strong>
                        </p>
                        
                        <?php if ($b['status'] === 'confirmed' || $b['status'] === 'pending'): ?>
                            <?php 
                            // Fallback for old bookings
                            $fallback_date = !empty($b['return_date']) ? $b['return_date'] : $b['booking_date'];
                            $end_time = !empty($b['return_datetime']) ? $b['return_datetime'] : $fallback_date . ' 23:59:59';
                            ?>
                            <div class="countdown-timer" data-end="<?= $end_time ?>" style="margin-top:8px;font-weight:bold;color:#d9534f;">
                                Loading timer...
                            </div>
                            <a href="extend.php?booking_id=<?= $b['id'] ?>" class="btn-small" style="display:inline-block;margin-top:10px;font-size:0.85em;padding:5px 10px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:4px;">Extend Booking</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color:#777;margin-top:20px;">No bookings found. <a href="../search.php">Browse cars</a> to make your first trip!</p>
    <?php endif; ?>

  </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTimers() {
        const now = new Date().getTime();
        document.querySelectorAll('.countdown-timer').forEach(function(el) {
            const endStr = el.getAttribute('data-end').replace(/-/g, '/'); // fix for safari
            const endTime = new Date(endStr).getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                el.innerText = "Time Expired";
                el.style.color = 'gray';
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                el.innerText = `Time Remaining: ${days}d ${hours}h ${minutes}m ${seconds}s`;
            }
        });
    }
    setInterval(updateTimers, 1000);
    updateTimers();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

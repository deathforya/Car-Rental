<?php
require_once __DIR__ . '/../config/db.php';

// Admin page styling
$page_css = '../assets/css/admin.css';
$page_class = 'admin-area';

require_once __DIR__ . '/../includes/header.php';

// Protect page: only admin
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.html');
    exit;
}

// Fetch bookings with joins
$sql = "SELECT b.*, 
               u.name AS customer_name, 
               u.email AS customer_email, 
               u.phone AS customer_phone, 
               v.name AS vehicle_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id
        ORDER BY b.created_at DESC";
$res = mysqli_query($conn, $sql);
?>

<main class="admin-container">
  <div class="admin-header">
    <div>
      <h2>Booking Requests</h2>
      <p class="small">Recent bookings from customers</p>
    </div>
    <div class="admin-actions">
      <a class="btn btn-outline" href="dashboard.php">Back to dashboard</a>
    </div>
  </div>

  <div class="admin-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Vehicle</th>
          <th>Time Remaining</th>
          <th>Booking Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($row['customer_phone']); ?></td>
            <td><?php echo htmlspecialchars($row['customer_email']); ?></td>
            <td><?php echo htmlspecialchars($row['vehicle_name']); ?></td>
            <td>
                <?php if ($row['status'] == 'confirmed' || $row['status'] == 'pending'): ?>
                    <?php 
                    $fallback_date = !empty($row['return_date']) ? $row['return_date'] : $row['booking_date'];
                    $end_time = !empty($row['return_datetime']) ? $row['return_datetime'] : $fallback_date . ' 23:59:59';
                    ?>
                    <span class="admin-timer" data-end="<?php echo $end_time; ?>" style="font-weight:bold;color:#d9534f">Calculating...</span>
                <?php else: ?>
                    <span class="badge badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                <?php endif; ?>
            </td>

            <!-- ✅ FIXED LINE: show real date -->
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateAdminTimers() {
        const now = new Date().getTime();
        document.querySelectorAll('.admin-timer').forEach(function(el) {
            const endStr = el.getAttribute('data-end').replace(/-/g, '/');
            const endTime = new Date(endStr).getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                el.innerText = "Expired";
                el.style.color = 'gray';
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                el.innerText = `${days}d ${hours}h ${minutes}m`;
            }
        });
    }
    setInterval(updateAdminTimers, 1000);
    updateAdminTimers();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

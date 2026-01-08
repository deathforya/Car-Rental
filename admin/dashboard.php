<?php
session_start();

/* 🔒 SESSION GUARD (FIRST LINE OF DEFENSE) */
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.html");
    exit;
}

/* 🚫 HARD CACHE BLOCK (THIS FIXES BACK/FORWARD) */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/db.php';

/* Admin page styling */
$page_css   = '../assets/css/admin.css';
$page_class = 'admin-area';

/* ⚠️ INCLUDE HEADER ONLY AFTER SECURITY */
require_once __DIR__ . '/../includes/header.php';

/* Fetch all vehicles */
$sql = "SELECT * FROM vehicles ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);
?>
<main class="admin-container">
  <div class="admin-header">
    <div>
      <h2>Admin Dashboard</h2>
      <p class="small">Overview of fleet performance and bookings</p>
    </div>
    <div class="admin-actions">
      <a class="btn btn-outline" href="bookings.php">Manage Bookings</a>
      <a class="btn btn-outline" href="users.php">Manage Users</a>
      <a class="btn btn-outline" href="verify_users.php">Verify Users</a>
      <a class="btn btn-outline" href="chat.php">Messages</a>
      <a class="btn btn-primary" href="add_vehicle.php">Add Vehicle</a>
    </div>
  </div>

  <?php
    // Calculate KPIs
    $count_vehicles = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM vehicles"))[0];
    $count_bookings = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','active')"))[0];
    
    // Total Revenue (Confirmed/Completed)
    $row_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as total FROM bookings WHERE status IN ('confirmed','completed')"));
    $revenue = $row_rev['total'] ?? 0;

    // Verify Pending
    $count_pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status='pending'"))[0];
  ?>

  <!-- KPI Cards -->
  <div class="kpi-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;">
    <div class="card kpi-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        <h3 style="font-size:2em;margin:0 0 5px;color:#007bff;"><?= $count_vehicles ?></h3>
        <p style="color:#666;margin:0;">Total Vehicles</p>
    </div>
    <div class="card kpi-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.05);">
         <h3 style="font-size:2em;margin:0 0 5px;color:#28a745;"><?= $count_bookings ?></h3>
        <p style="color:#666;margin:0;">Active Bookings</p>
    </div>
    <div class="card kpi-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.05);">
         <h3 style="font-size:2em;margin:0 0 5px;color:#ffc107;" title="Confirmed & Completed">₹<?= number_format($revenue) ?></h3>
        <p style="color:#666;margin:0;">Total Revenue</p>
    </div>
    <div class="card kpi-card" style="background:#fff;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.05);">
         <h3 style="font-size:2em;margin:0 0 5px;color:#dc3545;"><?= $count_pending ?></h3>
        <p style="color:#666;margin:0;">Pending Requests</p>
    </div>
  </div>

  <!-- Chart Section -->
  <div class="chart-section" style="background:#fff;padding:20px;border-radius:8px;margin-bottom:30px;box-shadow:0 2px 5px rgba(0,0,0,0.05);">
      <h3 style="margin-top:0;">Monthly Bookings (2025)</h3>
      <canvas id="bookingChart" height="100"></canvas>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
      const ctx = document.getElementById('bookingChart');
      new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: '# of Bookings',
                data: [12, 19, 3, 5, 2, 3, <?= $count_bookings ?>], // Mock data + live
                borderColor: '#007bff',
                tension: 0.1
            }]
        }
      });
  </script>

  <div class="admin-card">
    <h3 style="margin-top:0">All Vehicles</h3>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Type</th>
          <th>Price/Day</th><th>Status</th>
          <th>Image</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['name']); ?></td>
            <td><?= htmlspecialchars($row['type']); ?></td>
            <td><?= $row['price_per_day']; ?></td>
            <td><?= htmlspecialchars($row['status']); ?></td>
            <td>
              <?php if ($row['image']): ?>
                <img class="admin-thumb" src="../uploads/vehicles/<?= htmlspecialchars($row['image']); ?>">
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-outline" href="edit_vehicle.php?id=<?= $row['id']; ?>">Edit</a>
              <a class="btn btn-danger"
                 href="delete_vehicle.php?id=<?= $row['id']; ?>"
                 onclick="return confirm('Delete vehicle?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

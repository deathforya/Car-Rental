<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.html'); exit;
}

$page_css = '../assets/css/book.css'; // Reuse book css
$page_class = 'extend-page';
require_once __DIR__ . '/../includes/header.php';

$booking_id = isset($_REQUEST['booking_id']) ? intval($_REQUEST['booking_id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch booking + vehicle info
$sql = "SELECT b.*, v.name as vehicle_name, v.price_per_day 
        FROM bookings b 
        JOIN vehicles v ON b.vehicle_id = v.id 
        WHERE b.id = ? AND b.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $user_id);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$booking) {
    echo "<div class='container'><p>Booking not found.</p><a href='dashboard.php'>Back</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extra_days = intval($_POST['extra_days'] ?? 1);
    if ($extra_days < 1) $extra_days = 1;

    // Calculate new times
    $current_end_ts = strtotime($booking['return_datetime']);
    if (!$current_end_ts) {
        // Fallback if return_datetime was null (old bookings)
        $current_end_ts = strtotime($booking['return_date'] . ' 23:59:59');
    }

    $new_end_ts = $current_end_ts + ($extra_days * 24 * 3600);
    $new_return_dt = date('Y-m-d H:i:s', $new_end_ts);
    $new_return_date = date('Y-m-d', $new_end_ts);

    // Calculate cost
    $extra_cost = $booking['price_per_day'] * $extra_days;
    $new_total_price = $booking['total_price'] + $extra_cost;

    // Update DB
    $upd = mysqli_prepare($conn, "UPDATE bookings SET return_date=?, return_datetime=?, total_price=? WHERE id=?");
    mysqli_stmt_bind_param($upd, 'ssdi', $new_return_date, $new_return_dt, $new_total_price, $booking_id);
    
    if (mysqli_stmt_execute($upd)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}
?>

<div class="container" style="max-width:500px;margin-top:40px;">
  <h2>Extend Booking #<?php echo $booking_id; ?></h2>
  <div style="background:#f9f9f9;padding:15px;border-radius:8px;margin-bottom:20px;">
      <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_name']); ?></p>
      <p><strong>Current Return Time:</strong> <?php echo $booking['return_datetime'] ? $booking['return_datetime'] : $booking['return_date']; ?></p>
      <p><strong>Price per day:</strong> ₹<?php echo $booking['price_per_day']; ?></p>
  </div>

  <?php if ($error): ?>
    <div class="error" style="color:red;margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST">
      <label style="display:block;margin-bottom:15px;">
          Extend by (Days):
          <input type="number" name="extra_days" value="1" min="1" max="30" style="width:100%;padding:10px;margin-top:5px;border:1px solid #ddd;border-radius:4px;">
      </label>
      
      <button type="submit" class="btn-primary" style="width:100%;">Confirm Extension</button>
      <a href="dashboard.php" style="display:block;text-align:center;margin-top:15px;">Cancel</a>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

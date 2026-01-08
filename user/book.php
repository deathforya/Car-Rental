<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Protect page: only logged-in customers
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.html'); exit;
}

// Check Verification
$uid_check = $_SESSION['user_id'];
$v_res = mysqli_query($conn, "SELECT is_verified FROM users WHERE id = $uid_check");
$v_row = mysqli_fetch_assoc($v_res);
if (!$v_row || !$v_row['is_verified']) {
    header("Location: settings.php?msg=verify_needed");
    exit;
}

// Provide page-specific CSS/class then include header
$page_css = '../assets/css/book.css';
$page_class = 'book-page';
require_once __DIR__ . '/../includes/header.php';

// Get vehicle
$vehicle_id = isset($_REQUEST['vehicle_id']) ? intval($_REQUEST['vehicle_id']) : 0;
if ($vehicle_id <= 0) { header('Location: dashboard.php'); exit; }

$stmt = mysqli_prepare($conn, "SELECT id, name, type, price_per_day, image, status FROM vehicles WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $vehicle_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$vehicle = mysqli_fetch_assoc($res);
if (!$vehicle) { header('Location: dashboard.php'); exit; }

// Booking handler
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $date = trim($_POST['booking_date'] ?? '');
    $time = trim($_POST['booking_time'] ?? '');

    if ($date === '') {
        $error = 'Please choose a booking date.';
    } else {
        // Calculate return times
        $duration = intval($_POST['duration'] ?? 1);
        if ($duration < 1) $duration = 1;
        
        $start_ts = strtotime("$date $time");
        $end_ts = $start_ts + ($duration * 24 * 3600);
        
        $booking_time = ($time !== '') ? $time : '00:00';
        $booking_dt = date('Y-m-d H:i:s', $start_ts);
        $return_date = date('Y-m-d', $end_ts);
        $return_dt = date('Y-m-d H:i:s', $end_ts);

        // Calculate total price
        $total_price = $vehicle['price_per_day'] * $duration;

        // Insert with all time fields
        $query = "INSERT INTO bookings (user_id, vehicle_id, booking_date, booking_time, booking_datetime, return_date, return_datetime, total_price) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $ins = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($ins, 'iisssssd', $user_id, $vehicle_id, $date, $booking_time, $booking_dt, $return_date, $return_dt, $total_price);

        if ($ins && mysqli_stmt_execute($ins)) {
            mysqli_query($conn, "UPDATE vehicles SET status='booked' WHERE id = " . intval($vehicle_id));
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Booking failed: ' . mysqli_error($conn);
        }
    }
}
?>
<div class="container">
  <h2>Book: <?php echo htmlspecialchars($vehicle['name']); ?></h2>
  <p>Type: <?php echo htmlspecialchars($vehicle['type']); ?> · Price/day: <?php echo htmlspecialchars($vehicle['price_per_day']); ?></p>

  <?php if ($error): ?>
    <div class="error" style="color:#b91c1c;margin:12px 0;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="book.php?vehicle_id=<?php echo $vehicle_id; ?>" class="booking-form" style="max-width:420px">
    <label style="display:block;margin-bottom:10px">
      <span style="display:block;margin-bottom:6px">Booking date</span>
      <input type="date" name="booking_date" required
             min="<?php echo date('Y-m-d'); ?>"
             value="<?php echo htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d')); ?>"
             style="padding:10px;border:1px solid #e6e9ef;border-radius:8px;width:100%">
    </label>

      <input type="time" name="booking_time"
             value="<?php echo htmlspecialchars($_POST['booking_time'] ?? '09:00'); ?>"
             style="padding:10px;border:1px solid #e6e9ef;border-radius:8px;width:100%">
    </label>

    <label style="display:block;margin-bottom:14px">
      <span style="display:block;margin-bottom:6px">Duration (Days)</span>
      <input type="number" name="duration" min="1" max="30" required
             value="<?php echo htmlspecialchars($_POST['duration'] ?? '1'); ?>"
             style="padding:10px;border:1px solid #e6e9ef;border-radius:8px;width:100%">
    </label>

    <button type="submit" class="btn-primary">Confirm booking</button>
    <p style="margin-top:12px"><a href="dashboard.php">Cancel</a></p>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
session_start();
require_once __DIR__ . '/config/db.php';

$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($vehicle_id <= 0) {
    header('Location: search.php');
    exit;
}

// Fetch Vehicle Data
$stmt = mysqli_prepare($conn, "SELECT * FROM vehicles WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $vehicle_id);
mysqli_stmt_execute($stmt);
$vehicle = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$vehicle) {
    die("Vehicle not found.");
}

// Handle Booking Submission
$book_msg = '';
$book_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    if (empty($_SESSION['user_id'])) {
        header("Location: auth/login.html"); exit;
    }

    $start = $_POST['start_date']; // YYYY-MM-DD
    $end = $_POST['end_date'];
    $uid = $_SESSION['user_id'];

    if ($start && $end) {
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        $days = ceil(($end_ts - $start_ts) / 86400) + 1; // Inclusive (1 day minimum)
        
        if ($days < 1) {
            $book_error = "Invalid date range.";
        } else {
            // Check conflict
            $check_sql = "SELECT id FROM bookings 
                          WHERE vehicle_id = $vehicle_id 
                          AND status IN ('confirmed','pending')
                          AND (booking_date <= '$end' AND return_date >= '$start')";
            $c_res = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($c_res) > 0) {
                $book_error = "Selected dates are not available.";
            } else {
                // Determine Total Price
                $total = $days * $vehicle['price_per_day'];
                
                // Insert Booking
                // Using prepared statement for safety
                $ins = mysqli_prepare($conn, "INSERT INTO bookings (user_id, vehicle_id, booking_date, return_date, status, total_price) VALUES (?, ?, ?, ?, 'confirmed', ?)");
                mysqli_stmt_bind_param($ins, 'iissd', $uid, $vehicle_id, $start, $end, $total);
                
                if (mysqli_stmt_execute($ins)) {
                    $book_msg = "Booking confirmed! Total: $" . number_format($total, 2);
                    // Redirect to dashboard after 2 seconds or show link
                } else {
                    $book_error = "Booking failed: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Fetch Features
$f_sql = "SELECT feature_name FROM vehicle_features WHERE vehicle_id = $vehicle_id";
$f_res = mysqli_query($conn, $f_sql);
$features = [];
while ($f = mysqli_fetch_assoc($f_res)) $features[] = $f['feature_name'];

$default_common_css = 'assets/css/common.css';
$page_css = 'assets/css/vehicle_details.css';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="margin-top:30px;margin-bottom:50px;">
    <div class="vehicle-detail-layout">
        <!-- Left: Image & Info -->
        <div style="flex:2;">
            <div class="vehicle-hero-image" style="background-image:url('uploads/vehicles/<?= $vehicle['image'] ?? 'default.jpg' ?>');"></div>
            
            <h1 class="vehicle-title"><?= htmlspecialchars($vehicle['name']) ?></h1>
            <div class="vehicle-meta">
                <span>Type: <?= $vehicle['type'] ?></span>
                <span>Transmission: <?= $vehicle['transmission'] ?? 'Auto' ?></span>
                <span>Fuel: <?= $vehicle['fuel_type'] ?? 'Petrol' ?></span>
                <span>Location: <?= $vehicle['location'] ?? 'Main Branch' ?></span>
            </div>

            <div class="features-box">
                <h3>Features</h3>
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;">
                    <?php if (count($features) > 0): ?>
                        <?php foreach($features as $ft): ?>
                            <span class="feature-tag">✓ <?= htmlspecialchars($ft) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#888;">No specific features listed.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Review Component -->
            <?php include __DIR__ . '/includes/review_component.php'; ?>
        </div>

        <!-- Right: Booking Form -->
        <div style="flex:1;">
            <div class="booking-card-sticky">
                <div class="price-header">
                    <span class="price-amount">₹<?= $vehicle['price_per_day'] ?></span>
                    <span style="color:#777;">/ day</span>
                </div>

                <?php if ($book_msg): ?>
                    <div style="background:#d4edda;color:#155724;padding:15px;margin-bottom:20px;border-radius:4px;">
                        <?= $book_msg ?>
                        <br><a href="user/dashboard.php" style="font-weight:bold;text-decoration:underline;">Go to Dashboard</a>
                    </div>
                <?php elseif ($book_error): ?>
                    <div style="background:#f8d7da;color:#721c24;padding:15px;margin-bottom:20px;border-radius:4px;">
                        <?= $book_error ?>
                    </div>
                <?php endif; ?>

                <?php if (!$book_msg): ?>
                <form method="POST">
                    <input type="hidden" name="book_now" value="1">
                    
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600;display:block;margin-bottom:5px;">Pick-up Date</label>
                        <input type="date" name="start_date" id="start_date" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;" min="<?= date('Y-m-d') ?>">
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="font-weight:600;display:block;margin-bottom:5px;">Return Date</label>
                        <input type="date" name="end_date" id="end_date" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;" min="<?= date('Y-m-d') ?>">
                    </div>

                    <div id="price-calculation" class="price-calculation">
                        <div style="display:flex;justify-content:space-between;">
                            <span>Duration:</span>
                            <span id="calc-days">0 days</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-weight:bold;margin-top:5px;font-size:1.1em;">
                            <span>Total:</span>
                            <span id="calc-total">₹0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;padding:12px;font-size:1.1em;">Book Now</button>
                    
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <p style="text-align:center;margin-top:10px;font-size:0.9em;color:#666;">Please <a href="auth/login.html">login</a> to book.</p>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    const pricePerDay = <?= $vehicle['price_per_day'] ?>;
    const vehicleId = <?= $vehicle_id ?>;
    const startIn = document.getElementById('start_date');
    const endIn = document.getElementById('end_date');
    const calcDiv = document.getElementById('price-calculation');
    const daysSpan = document.getElementById('calc-days');
    const totalSpan = document.getElementById('calc-total');

    function updatePrice() {
        if (startIn.value && endIn.value) {
            const s = new Date(startIn.value);
            const e = new Date(endIn.value);
            const diffTime = e - s;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Inclusive booking usually? or nights? Let's say days (inclusive). 
            // Actually car rental is usually per 24h. But for simple date picker, 1 day min (same day return).
            
            if (diffDays > 0) {
                const total = diffDays * pricePerDay;
                daysSpan.innerText = diffDays + (diffDays === 1 ? ' day' : ' days');
                totalSpan.innerText = '₹' + total.toFixed(2);
                calcDiv.style.display = 'block';
            } else {
                calcDiv.style.display = 'none';
            }
        }
    }

    startIn.addEventListener('change', updatePrice);
    endIn.addEventListener('change', updatePrice);

    // Fetch disabled dates (booked)
    fetch('api/check_availability.php?vehicle_id=' + vehicleId)
        .then(r => r.json())
        .then(bookings => {
            // Advanced: disable dates in Flatpickr.
            // For native date input, we can't easily disable specific dates visually, 
            // but we can validate on change.
            console.log("Booked dates:", bookings);
            // In a real big-scale app, we'd use a JS library. 
            // For MVP native input, validation is server-side mostly + client warning.
        });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

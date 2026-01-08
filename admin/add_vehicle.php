<?php
require_once __DIR__ . '/../config/db.php';

// Admin page styling
$page_css = '../assets/css/admin.css';
$page_class = 'admin-area';

require_once __DIR__ . '/../includes/header.php';

if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.html');
    exit;
}

// Handle POST (add vehicle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $price = floatval($_POST['price_per_day']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/vehicles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $tmp = $_FILES['image']['tmp_name'];
        // sanitize filename and prefix with timestamp to avoid collisions
        $orig = basename($_FILES['image']['name']);
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
        $fname = time() . '_' . $safe;
        $target = $uploadDir . $fname;
        // Basic check for image file type
        $allowed = ['image/jpeg','image/png','image/gif'];
        if (in_array($_FILES['image']['type'], $allowed)) {
            if (move_uploaded_file($tmp, $target)) {
                $imageName = mysqli_real_escape_string($conn, $fname);
            }
        }
    }

    $sql = "INSERT INTO vehicles (name, type, price_per_day, status, image) VALUES ('$name', '$type', $price, '$status', " . ($imageName ? "'$imageName'" : "NULL") . ")";
    if (mysqli_query($conn, $sql)) {
        header('Location: dashboard.php');
        exit;
    } else {
        die('Insert failed: ' . mysqli_error($conn));
    }
}

// Render form
?>
<main class="admin-container">
  <div class="admin-header">
    <h2>Add Vehicle</h2>
    <div class="admin-actions"><a class="btn btn-outline" href="dashboard.php">Back to dashboard</a></div>
  </div>

  <div class="admin-card">
    <form action="add_vehicle.php" method="post" enctype="multipart/form-data">
      <div class="form-row">
        <label>Name</label>
        <input class="form-control" type="text" name="name" required>
      </div>
      <div class="form-row">
        <label>Type</label>
        <input class="form-control" type="text" name="type" required>
      </div>
      <div class="form-row">
        <label>Price per day</label>
        <input class="form-control" type="number" step="0.01" name="price_per_day" required>
      </div>
      <div class="form-row">
        <label>Status</label>
        <input class="form-control" type="text" name="status" value="available">
      </div>
      <div class="form-row">
        <label>Image</label>
        <input class="form-control" type="file" name="image" accept="image/*">
      </div>
      <div style="margin-top:10px">
        <button class="btn btn-primary" type="submit">Add Vehicle</button>
      </div>
    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

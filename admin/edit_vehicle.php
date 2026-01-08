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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: dashboard.php'); exit; }

// Fetch vehicle
$res = mysqli_query($conn, "SELECT * FROM vehicles WHERE id = $id LIMIT 1");
if (!$res || mysqli_num_rows($res) === 0) { header('Location: dashboard.php'); exit; }
$vehicle = mysqli_fetch_assoc($res);

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $type = mysqli_real_escape_string($conn, trim($_POST['type']));
    $price = floatval($_POST['price']);
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    $trans = mysqli_real_escape_string($conn, $_POST['transmission']);
    $fuel = mysqli_real_escape_string($conn, $_POST['fuel_type']);
    $loc = mysqli_real_escape_string($conn, trim($_POST['location']));

    // 1. Handle Image Upload if present
    $imageName = $vehicle['image']; // Default to current
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
             $uploadDir = __DIR__ . '/../uploads/vehicles/';
             if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
             
             $newName = 'v_' . $id . '_' . time() . '.' . $ext;
             if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                 $imageName = $newName; // Update ref
             }
        }
    }

    $imageSQL = $imageName ? "'$imageName'" : "NULL";

    // 2. Update Vehicle Data
    $sql = "UPDATE vehicles SET 
            name='$name', 
            type='$type', 
            price_per_day=$price, 
            status='$status', 
            transmission='$trans', 
            fuel_type='$fuel', 
            location='$loc',
            image=$imageSQL
            WHERE id=$id";
            
    if (mysqli_query($conn, $sql)) {
        
        // 3. Update Features (Sync: Delete all, re-insert selected)
        mysqli_query($conn, "DELETE FROM vehicle_features WHERE vehicle_id=$id");
        if (!empty($_POST['feature_chk'])) {
            foreach($_POST['feature_chk'] as $chk) {
                 $chk_esc = mysqli_real_escape_string($conn, $chk);
                 mysqli_query($conn, "INSERT INTO vehicle_features (vehicle_id, feature_name) VALUES ($id, '$chk_esc')");
            }
        }
        
        echo "<script>alert('Vehicle Updated!'); window.location='dashboard.php';</script>";
    } else {
        $error = "Error updating: " . mysqli_error($conn);
    }
}

// Refresh data for form
$v = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM vehicles WHERE id=$id"));
$cur_feat = [];
$fr = mysqli_query($conn, "SELECT feature_name FROM vehicle_features WHERE vehicle_id=$id");
while($row = mysqli_fetch_assoc($fr)) $cur_feat[] = $row['feature_name'];
?>

<main class="admin-container">
 <div class="admin-card" style="max-width:800px;margin:20px auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <h2>Edit Vehicle</h2>
    <a href="dashboard.php" class="btn btn-outline">Back</a>
  </div>

  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        
        <label>Name
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($v['name']) ?>" required>
        </label>
        
        <label>Type
            <select name="type" class="form-control">
                <?php foreach(['SUV','Sedan','Luxury','Sports','Van'] as $t): ?>
                    <option value="<?= $t ?>" <?= $v['type']==$t?'selected':''?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        
        <label>Price/Day (₹)
          <input type="number" step="0.01" name="price" class="form-control" value="<?= $v['price_per_day'] ?>" required>
        </label>
    
        <label>Location
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($v['location']??'Main Branch') ?>">
        </label>

        <label>Transmission
            <select name="transmission" class="form-control">
                <option value="Auto" <?= ($v['transmission']??'')=='Auto'?'selected':''?>>Auto</option>
                <option value="Manual" <?= ($v['transmission']??'')=='Manual'?'selected':''?>>Manual</option>
            </select>
        </label>
        
        <label>Fuel
            <select name="fuel_type" class="form-control">
                <option value="Petrol" <?= ($v['fuel_type']??'')=='Petrol'?'selected':''?>>Petrol</option>
                <option value="Diesel" <?= ($v['fuel_type']??'')=='Diesel'?'selected':''?>>Diesel</option>
                 <option value="Electric" <?= ($v['fuel_type']??'')=='Electric'?'selected':''?>>Electric</option>
                 <option value="Hybrid" <?= ($v['fuel_type']??'')=='Hybrid'?'selected':''?>>Hybrid</option>
            </select>
        </label>
    </div>

    <div style="margin-top:20px;">
        <label style="display:block;font-weight:bold;margin-bottom:10px;">Create Features/Tags</label>
        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;background:#f9f9f9;padding:15px;border-radius:5px;">
            <?php 
            $all_feats = ['Bluetooth','GPS','Camera','Heated Seats','Sunroof','Apple CarPlay','Leather Seats','4WD','Blind Spot Monitor'];
            foreach($all_feats as $f): 
                $checked = in_array($f, $cur_feat) ? 'checked' : '';
            ?>
            <label style="font-weight:normal;display:flex;align-items:center;cursor:pointer;">
                <input type="checkbox" name="feature_chk[]" value="<?= $f ?>" <?= $checked ?> style="margin-right:8px;transform:scale(1.2);"> <?= $f ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <label>Update Image
            <input type="file" name="image" class="form-control">
        </label>
        
        <label>Status
          <select name="status" class="form-control">
            <option value="available" <?= $v['status']=='available'?'selected':''?>>Available</option>
            <option value="booked" <?= $v['status']=='booked'?'selected':''?>>Booked</option>
            <option value="maintenance" <?= $v['status']=='maintenance'?'selected':''?>>Maintenance</option>
          </select>
        </label>
    </div>

    <?php if ($v['image']): ?>
        <p style="margin-top:10px;">Current Image: <img src="../uploads/vehicles/<?= htmlspecialchars($v['image']); ?>" style="height:50px;vertical-align:middle;border-radius:4px;"></p>
    <?php endif; ?>

    <button class="btn btn-primary" style="width:100%;margin-top:25px;padding:12px;font-size:1.1em;">Save Vehicle</button>
  </form>
 </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

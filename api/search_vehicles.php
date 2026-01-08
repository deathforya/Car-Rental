<?php
header('Content-Type: application/json');
error_reporting(0); // Suppress warnings to ensure clean JSON
ini_set('display_errors', 0);
require_once __DIR__ . '/../config/db.php';

// 1. Get params
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 500;
$types = isset($_GET['type']) ? $_GET['type'] : []; // Array
$transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
$features = isset($_GET['features']) ? $_GET['features'] : []; // Array
$only_avail = isset($_GET['only_available']) ? $_GET['only_available'] : 0;

// 2. Build Query
$sql = "SELECT v.*, 
        (SELECT AVG(rating) FROM reviews r WHERE r.vehicle_id = v.id) as avg_rating,
        (SELECT COUNT(id) FROM reviews r WHERE r.vehicle_id = v.id) as review_count
        FROM vehicles v 
        WHERE v.status != 'maintenance'";

if ($only_avail == 1) {
    $sql .= " AND v.status = 'available'";
}

// Price
if ($max_price > 0) {
    $sql .= " AND v.price_per_day <= " . (int)$max_price;
}

// Location
if (!empty($location)) {
    $esc_loc = mysqli_real_escape_string($conn, $location);
    $sql .= " AND v.location LIKE '%$esc_loc%'";
}

// Transmission
if (!empty($transmission)) {
    $esc_trans = mysqli_real_escape_string($conn, $transmission);
    $sql .= " AND v.transmission = '$esc_trans'";
}

// Vehicle Types (OR logic)
if (!empty($types) && is_array($types)) {
    $type_list = [];
    foreach ($types as $t) {
        $type_list[] = "'" . mysqli_real_escape_string($conn, $t) . "'";
    }
    if (count($type_list) > 0) {
        $sql .= " AND v.type IN (" . implode(',', $type_list) . ")";
    }
}

// Features (AND logic: must have ALL selected features)
if (!empty($features) && is_array($features)) {
    foreach ($features as $f) {
        $esc_f = mysqli_real_escape_string($conn, $f);
        // Subquery for each feature requirement
        $sql .= " AND v.id IN (SELECT vehicle_id FROM vehicle_features WHERE feature_name = '$esc_f')";
    }
}

// Date Availability (Collision detection)
if (!empty($start_date) && !empty($end_date)) {
    $esc_start = mysqli_real_escape_string($conn, $start_date);
    $esc_end = mysqli_real_escape_string($conn, $end_date);
    
    // Logic: Exclude vehicles that have a confirmed/pending booking overlapping with requested range
    // Overlap: (BookStart <= ReqEnd) AND (BookEnd >= ReqStart)
    $sql .= " AND v.id NOT IN (
        SELECT vehicle_id FROM bookings 
        WHERE status IN ('confirmed', 'pending') 
        AND (booking_date <= '$esc_end' AND return_date >= '$esc_start')
    )";
}

// Sorting
if ($sort === 'price_desc') {
    $sql .= " ORDER BY v.price_per_day DESC";
} else {
    $sql .= " ORDER BY v.price_per_day ASC";
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}

$vehicles = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Add logic to get features list for display if needed, 
    // but simplified for now.
    $vehicles[] = $row;
}

echo json_encode($vehicles);

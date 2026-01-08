<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : 0;

if ($vehicle_id <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch all confirmed or pending bookings for this vehicle
$sql = "SELECT booking_date, return_date 
        FROM bookings 
        WHERE vehicle_id = $vehicle_id 
        AND status IN ('confirmed', 'pending', 'active')";

$result = mysqli_query($conn, $sql);

$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}

echo json_encode($bookings);

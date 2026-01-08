<?php
// Database connection for DriveNow application
// Using procedural mysqli

// Change these values to match your XAMPP/MySQL credentials
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'admin';
$db_name = 'drivenow';

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
	// In production you might log the error instead of showing it
	die('Database connection failed: ' . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

// Now $conn can be used throughout the app with procedural mysqli calls

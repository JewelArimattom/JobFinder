<?php
// --- DATABASE CONFIGURATION ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder";

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');
?>

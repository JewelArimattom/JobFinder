<?php
// --- DATABASE CONFIGURATION ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "careerbridge";

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set the timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

// Log that this file was included
error_log("Database configuration loaded from: " . __FILE__);
?>

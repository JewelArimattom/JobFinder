<?php
// Start session to access it
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the homepage after logout
header("Location: /CareerBridge/frontend/index.html");
exit;
?>
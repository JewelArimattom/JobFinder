<?php
// Start the session to access session variables
session_start();

// Set the header to indicate the response is JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // If logged in, return their status, name, and roles
    echo json_encode([
        'loggedin' => true,
        'full_name' => $_SESSION['full_name'],
        'roles' => $_SESSION['roles']
    ]);
} else {
    // If not logged in, return that status
    echo json_encode(['loggedin' => false]);
}
?>
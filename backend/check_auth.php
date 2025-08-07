<?php
// Configure session settings before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Secure in HTTPS

// Set session cookie path to the root of your application
$path = dirname(dirname($_SERVER['PHP_SELF']));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $path,
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true
]);

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Debug information (remove in production)
    $debug = [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'session_path' => session_save_path(),
        'cookie_path' => $path,
        'session_data' => $_SESSION
    ];

    // Check all required session variables
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true &&
        isset($_SESSION['user_id']) && isset($_SESSION['full_name'])) {
        
        // Return full user state
        echo json_encode([
            'success' => true,
            'loggedin' => true,
            'user_id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'roles' => isset($_SESSION['roles']) ? $_SESSION['roles'] : [],
            'debug' => $debug // Remove this in production
        ]);
    } else {
        // Return not logged in state
        echo json_encode([
            'success' => true,
            'loggedin' => false,
            'debug' => $debug // Remove this in production
        ]);
    }
} catch (Exception $e) {
    // Return error state
    echo json_encode([
        'success' => false,
        'loggedin' => false,
        'error' => $e->getMessage(),
        'debug' => $debug // Remove this in production
    ]);
}
?>
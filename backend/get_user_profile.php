<?php
session_start();
header('Content-Type: application/json');
require_once 'database.php';

// Default response structure
$response = [
    'success' => false,
    'user' => null,
    'applications' => [],
    'message' => ''
];

try {
    // Check for user authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated. Please log in.");
    }
    $user_id = (int)$_SESSION['user_id'];

    // Establish database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // --- Get User Details ---
    $stmt_user = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    if ($user_result->num_rows > 0) {
        $response['user'] = $user_result->fetch_assoc();
    } else {
        throw new Exception("User profile not found.");
    }
    $stmt_user->close();

    // --- Get Applied Jobs (Corrected Query) ---
    // This query now uses the exact column names required by the HTML front-end.
    $stmt_apps = $conn->prepare("
        SELECT 
            j.job_title,
            j.company_name,
            j.location,
            a.status,
            DATE_FORMAT(a.application_date, '%d %b %Y') as applied_date_formatted
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.user_id = ?
        ORDER BY a.application_date DESC
    ");
    $stmt_apps->bind_param("i", $user_id);
    $stmt_apps->execute();
    $apps_result = $stmt_apps->get_result();
    
    while ($row = $apps_result->fetch_assoc()) {
        $response['applications'][] = $row;
    }
    $stmt_apps->close();

    $response['success'] = true;
    $conn->close();

} catch (Exception $e) {
    // If any error occurs, capture the message
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

// Send the final JSON response
echo json_encode($response);
?>

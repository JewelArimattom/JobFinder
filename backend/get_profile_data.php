<?php
session_start();
header('Content-Type: application/json');

// --- DATABASE CONFIGURATION ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Response structure
$response = [
    'success' => false,
    'user' => null,
    'applications' => []
];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated.");
    }
    $user_id = (int)$_SESSION['user_id'];

    $conn = new mysqli($servername, $username, $password, $dbname);

    // --- GET USER DETAILS ---
    $stmt_user = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    if ($user_result->num_rows > 0) {
        $response['user'] = $user_result->fetch_assoc();
    }
    $stmt_user->close();

    // --- GET APPLIED JOBS (QUERY CORRECTED AGAIN) ---
    $stmt_apps = $conn->prepare("
        SELECT 
            j.job_title AS title,
            j.company_name AS company,        -- Corrected Line
            j.location,
            j.company_logo_path AS logo,      -- Corrected Line
            a.status,
            DATE_FORMAT(a.application_date, '%d %b %Y') as application_date_formatted
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
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
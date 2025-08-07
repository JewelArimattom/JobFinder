<?php
session_start();
header('Content-Type: application/json');
require_once 'database.php';

$response = [
    'success' => false,
    'employer' => null,
    'posted_jobs' => []
];

try {
    // --- AUTHENTICATION & ROLE CHECK ---
    if (!isset($_SESSION['user_id']) || !in_array('employer', $_SESSION['roles'] ?? [])) {
        throw new Exception("Access Denied. Employer account required.");
    }
    $employer_id = (int)$_SESSION['user_id'];

    $conn = new mysqli($servername, $username, $password, $dbname);

    // --- GET EMPLOYER DETAILS ---
    $stmt_user = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $employer_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    if ($user_result->num_rows > 0) {
        $response['employer'] = $user_result->fetch_assoc();
    }
    $stmt_user->close();

    // --- GET POSTED JOBS WITH APPLICATION COUNT ---
    $stmt_jobs = $conn->prepare("
        SELECT 
            j.id,
            j.job_title,
            j.location,
            DATE_FORMAT(j.posted_at, '%d %b %Y') as posted_date_formatted,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count
        FROM jobs j
        WHERE j.employer_id = ?
        ORDER BY j.posted_at DESC
    ");
    $stmt_jobs->bind_param("i", $employer_id);
    $stmt_jobs->execute();
    $jobs_result = $stmt_jobs->get_result();
    
    while ($row = $jobs_result->fetch_assoc()) {
        $response['posted_jobs'][] = $row;
    }
    $stmt_jobs->close();

    $response['success'] = true;
    $conn->close();

} catch (Exception $e) {
    http_response_code(403); // Forbidden
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
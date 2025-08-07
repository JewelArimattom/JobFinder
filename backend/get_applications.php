<?php
session_start();
header('Content-Type: application/json');
require_once 'database.php';

// Check if the user is logged in and is an employer
if (!isset($_SESSION['user_id']) || !in_array('employer', $_SESSION['roles'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Access denied. You must be logged in as an employer.']);
    exit;
}

// Check if job_id is provided
if (!isset($_GET['job_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Job ID is required.']);
    exit;
}

$employer_id = $_SESSION['user_id'];
$job_id = filter_var($_GET['job_id'], FILTER_SANITIZE_NUMBER_INT);

$conn = new mysqli($servername, $username, $password, $dbname);

try {
    // First, verify the job belongs to the logged-in employer
    $job_check_stmt = $conn->prepare("SELECT job_title FROM jobs WHERE id = ? AND employer_id = ?");
    $job_check_stmt->bind_param("ii", $job_id, $employer_id);
    $job_check_stmt->execute();
    $job_result = $job_check_stmt->get_result();

    if ($job_result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Job not found or you do not have permission to view it.']);
        exit;
    }

    $job_row = $job_result->fetch_assoc();
    $job_title = $job_row['job_title'];
    $job_check_stmt->close();

    // Fetch applications for the specified job, now including the applicant's user ID
    $stmt = $conn->prepare(
        "SELECT u.id as applicant_id, u.full_name, u.email, a.resume_path, a.application_date
         FROM applications a
         JOIN users u ON a.user_id = u.id
         WHERE a.job_id = ?
         ORDER BY a.application_date DESC"
    );
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $applications = [];
    while ($row = $result->fetch_assoc()) {
        // Format the date for better display
        $applied_date = new DateTime($row['application_date']);
        $row['applied_date_formatted'] = $applied_date->format('F j, Y');
        
        // Sanitize output to prevent XSS
        $row['applicant_name'] = htmlspecialchars($row['full_name']);
        $row['applicant_email'] = htmlspecialchars($row['email']);
        
        $applications[] = $row;
    }

    echo json_encode([
        'success' => true,
        'job_title' => htmlspecialchars($job_title),
        'applications' => $applications
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred: ' . $e->getMessage()]);
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>

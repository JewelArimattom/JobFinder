<?php
require_once 'database.php';

// Set the header to output JSON
header('Content-Type: application/json');

// Set the timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

// Get the job ID from the URL parameter
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid job ID provided.']);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Prepare a statement to prevent SQL injection, now selecting all new columns
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $job = $result->fetch_assoc();
        
        // Format the data for consistency with the frontend
        $job['tags'] = !empty($job['skills']) ? array_map('trim', explode(',', $job['skills'])) : [];
        $job['salary'] = '₹' . $job['salary_min'] . ' - ₹' . $job['salary_max'] . ' ' . $job['salary_unit'];
        $job['posted'] = time_ago($job['posted_at']);
        
        echo json_encode($job);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Job not found.']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => "Server Error: " . $e->getMessage()]);
}

// Helper function to create a "time ago" string
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) return "Just Now";
    else if ($minutes <= 60) return ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
    else if ($hours <= 24) return ($hours == 1) ? "an hour ago" : "$hours hrs ago";
    else if ($days <= 7) return ($days == 1) ? "yesterday" : "$days days ago";
    else if ($weeks <= 4.3) return ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
    else if ($months <= 12) return ($months == 1) ? "a month ago" : "$months months ago";
    else return ($years == 1) ? "one year ago" : "$years years ago";
}
?>

<?php
// Include the database configuration
require_once 'database.php';

// Set the header to output JSON
header('Content-Type: application/json');

try {
    // Get the limit parameter, default to 10 if not specified
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Validate limit
    if ($limit < 1) $limit = 10;
    
    // SQL query to fetch jobs, ordered by the newest first with limit
    $sql = "SELECT id, job_title, company_name, location, salary_min, salary_max, salary_unit, job_type, experience_level, posted_at, company_logo_path, skills FROM jobs ORDER BY posted_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $jobs = [];
    if ($result->num_rows > 0) {
        // Fetch all results into an associative array
        while($row = $result->fetch_assoc()) {
            // Format salary with validation
            $salary = "Not Disclosed";
            if (!empty($row['salary_min']) || !empty($row['salary_max'])) {
                $salary = '₹' . number_format($row['salary_min']) . ' - ₹' . number_format($row['salary_max']) . ' ' . $row['salary_unit'];
            }
            
            // Format the data to match the structure expected by the JavaScript frontend
            $formatted_job = [
                'id' => intval($row['id']),
                'title' => htmlspecialchars($row['job_title']),
                'company' => htmlspecialchars($row['company_name']),
                'location' => htmlspecialchars($row['location']),
                'salary' => $salary,
                'type' => htmlspecialchars($row['job_type']),
                'experience' => htmlspecialchars($row['experience_level']),
                'posted' => time_ago($row['posted_at']),
                'logo' => !empty($row['company_logo_path']) ? '/CareerBridge/backend/' . htmlspecialchars($row['company_logo_path']) : null,
                'tags' => !empty($row['skills']) ? array_map('trim', explode(',', $row['skills'])) : []
            ];
            $jobs[] = $formatted_job;
        }
    }

    // Encode the array of jobs into JSON and output it
    echo json_encode($jobs);

    $conn->close();

} catch (Exception $e) {
    // If there's an error, return an error message in JSON format
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}

// Helper function to create a "time ago" string
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60 );
    $hours           = round($seconds / 3600);
    $days          = round($seconds / 86400 );
    $weeks          = round($seconds / 604800);
    $months      = round($seconds / 2629440);
    $years          = round($seconds / 31553280);
    if($seconds <= 60) {
        return "Just Now";
    } else if($minutes <=60) {
        return ($minutes==1) ? "one minute ago" : "$minutes minutes ago";
    } else if($hours <=24) {
        return ($hours==1) ? "an hour ago" : "$hours hrs ago";
    } else if($days <= 7) {
        return ($days==1) ? "yesterday" : "$days days ago";
    } else if($weeks <= 4.3) { // 4.3 weeks in a month
        return ($weeks==1) ? "a week ago" : "$weeks weeks ago";
    } else if($months <=12) {
        return ($months==1) ? "a month ago" : "$months months ago";
    } else {
        return ($years==1) ? "one year ago" : "$years years ago";
    }
}
?>

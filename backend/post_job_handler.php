<?php
// --- DATABASE CONFIGURATION ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder"; // Corrected database name

// --- FILE UPLOAD CONFIGURATION ---
// Corrected path relative to this script's location (backend/)
$upload_dir = "../frontend/uploads/"; 

// --- SCRIPT LOGIC ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function show_message($title, $message, $is_success = true) {
    $icon = $is_success ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500';
    $button_text = $is_success ? 'View All Jobs' : 'Try Again';
    // Corrected links to match file structure
    $button_link = $is_success ? '/JOBSEEKER/frontend/jobs.html' : '/JOBSEEKER/frontend/post-job.html';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - JobFinder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .gradient-text { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(118, 75, 162, 0.4); }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/JOBSEEKER/frontend/index.html" class="text-2xl font-black gradient-text">JobFinder</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow flex items-center justify-center">
        <div class="max-w-lg w-full bg-white p-8 rounded-2xl shadow-lg text-center">
            <i class="fas $icon text-6xl mb-6"></i>
            <h1 class="text-3xl font-black text-slate-900">$title</h1>
            <p class="text-slate-600 mt-4 text-lg">$message</p>
            <div class="mt-8 flex justify-center gap-4">
                <a href="$button_link" class="btn-primary text-white font-bold px-8 py-3 rounded-xl">$button_text</a>
                <a href="/JOBSEEKER/frontend/post-job.html" class="bg-slate-200 text-slate-700 font-bold px-8 py-3 rounded-xl hover:bg-slate-300 transition-colors">Post Another Job</a>
            </div>
        </div>
    </main>
</body>
</html>
HTML;
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Handle custom job category
        $job_category = $_POST['job_category'];
        if ($job_category === 'other' && !empty($_POST['other_category'])) {
            $job_category = $_POST['other_category'];
        }

        $application_deadline = !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null;
        
        $company_logo_path = '';
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('logo_', true) . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $target_file)) {
                $company_logo_path = $target_file;
            } else {
                throw new Exception("Error: Could not move uploaded file. Check folder permissions for 'frontend/uploads/'.");
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO jobs (job_title, job_category, location, job_type, experience_level, openings, application_deadline, salary_min, salary_max, salary_unit, description, skills, company_name, company_website, company_logo_path, recruiter_name, recruiter_email) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "sssssisssssssssss", 
            $_POST['job_title'], $job_category, $_POST['location'], $_POST['job_type'], 
            $_POST['experience_level'], $_POST['openings'], $application_deadline, 
            $_POST['salary_min'], $_POST['salary_max'], $_POST['salary_unit'], 
            $_POST['description'], $_POST['skills'], $_POST['company_name'], 
            $_POST['company_website'], $company_logo_path, $_POST['recruiter_name'], $_POST['recruiter_email']
        );

        if ($stmt->execute()) {
            show_message(
                "Job Posted Successfully!",
                "Your job listing for '" . htmlspecialchars($_POST['job_title']) . "' has been added and is now live."
            );
        } else {
            throw new Exception("Database execution failed: " . $stmt->error);
        }

        $stmt->close();
    }
    $conn->close();

} catch (Exception $e) {
    show_message(
        "An Error Occurred",
        "<strong>Error details:</strong> " . $e->getMessage(),
        false
    );
}
?>

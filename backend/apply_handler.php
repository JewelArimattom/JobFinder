<?php
session_start();
require_once 'database.php';

// --- FILE UPLOAD CONFIGURATION ---
$upload_dir = "../frontend/resumes/"; 

function show_message($title, $message, $is_success = true) {
    // This function remains the same...
    $icon = $is_success ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500';
    $button_text = $is_success ? 'Find More Jobs' : 'Try Again';
    $button_link = $is_success ? '../frontend/jobs.html' : 'javascript:history.back()';

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
                <a href="../index.html" class="text-2xl font-black gradient-text">CareerBridge</a>
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
            </div>
        </div>
    </main>
</body>
</html>
HTML;
}

try {
    // --- CHECK IF USER IS LOGGED IN ---
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to apply for a job.");
    }
    $user_id = (int)$_SESSION['user_id'];

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- FORM DATA ---
        $job_id = (int)$_POST['job_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        // ... other form fields remain the same

        // --- RESUME UPLOAD ---
        $resume_path = '';
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] == 0) {
            // ... upload logic remains the same...
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('resume_', true) . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['resume_file']['tmp_name'], $target_file)) {
                $resume_path = $target_file;
            } else {
                throw new Exception("Error: Could not move uploaded resume. Check folder permissions for 'frontend/resumes/'.");
            }
        } else {
            throw new Exception("Resume file is required.");
        }


        // --- DATABASE INSERT (UPDATED) ---
        $stmt = $conn->prepare(
            "INSERT INTO applications (job_id, user_id, full_name, email, phone, portfolio_url, current_salary, expected_salary, notice_period, resume_path) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        // --- BIND PARAM (UPDATED) ---
        // The type string is now "iissssssss" because user_id is an integer
        $stmt->bind_param(
            "iissssssss", 
            $job_id, $user_id, $full_name, $email, $_POST['phone'], $_POST['portfolio_url'], 
            $_POST['current_salary'], $_POST['expected_salary'], $_POST['notice_period'], $resume_path
        );

        if ($stmt->execute()) {
            show_message(
                "Application Submitted!",
                "Thank you, " . htmlspecialchars($full_name) . ". Your application has been received."
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
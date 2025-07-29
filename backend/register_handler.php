<?php
// --- DATABASE CONFIGURATION ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder";

// --- SCRIPT LOGIC ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function show_message($title, $message, $is_success = true) {
    $icon = $is_success ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500';
    $button_text = $is_success ? 'Login Now' : 'Try Again';
    $button_link = $is_success ? '/JOBSEEKER/frontend/components/login.html' : 'javascript:history.back()';

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
    <nav class="bg-white shadow-sm"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-between h-16"><a href="/JOBSEEKER/frontend/index.html" class="text-2xl font-black gradient-text">JobFinder</a></div></div></nav>
    <main class="flex-grow flex items-center justify-center"><div class="max-w-lg w-full bg-white p-8 rounded-2xl shadow-lg text-center"><i class="fas $icon text-6xl mb-6"></i><h1 class="text-3xl font-black text-slate-900">$title</h1><p class="text-slate-600 mt-4 text-lg">$message</p><div class="mt-8"><a href="$button_link" class="btn-primary text-white font-bold px-8 py-3 rounded-xl">$button_text</a></div></div></main>
</body>
</html>
HTML;
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (empty($_POST['full_name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['user_type'])) {
            throw new Exception("All fields are required.");
        }
        
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $user_type = $_POST['user_type'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if user already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("This email address is already registered.");
        }
        $stmt_check->close();

        // Start transaction
        $conn->begin_transaction();

        // Insert into users table
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_user = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        $stmt_user->bind_param("sss", $full_name, $email, $hashed_password);
        $stmt_user->execute();
        $user_id = $conn->insert_id; // Get the ID of the new user
        $stmt_user->close();

        // Get the role ID
        $role_id = ($user_type === 'employer') ? 2 : 1; // 1 for jobseeker, 2 for employer

        // Insert into user_roles table
        $stmt_role = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt_role->bind_param("ii", $user_id, $role_id);
        $stmt_role->execute();
        $stmt_role->close();

        // Commit the transaction
        $conn->commit();

        show_message(
            "Registration Successful!",
            "Welcome, " . htmlspecialchars($full_name) . "! Your account has been created successfully."
        );
    }
    $conn->close();

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback(); // Rollback transaction on error
    }
    show_message(
        "Registration Failed",
        "<strong>Error:</strong> " . $e->getMessage(),
        false
    );
}
?>

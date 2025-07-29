<?php
session_start(); // Start the session at the very beginning

// --- DATABASE CONFIGURATION ---
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder";

// --- SCRIPT LOGIC ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function show_error_message($message) {
    // Using heredoc for cleaner HTML without escaping quotes
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Failed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full text-center">
        
        <div class="text-red-500 mb-4">
            <i class="fas fa-exclamation-triangle fa-4x"></i>
        </div>

        <h1 class="text-3xl font-bold text-slate-800 mb-2">
            Login Failed
        </h1>

        <p class="text-slate-600 mb-6">
            {$message}
        </p>

        <a href="/JOBSEEKER/frontend/components/signUp.html" class="inline-block bg-indigo-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-indigo-700 transition-colors duration-300">
            <i class="fas fa-redo-alt mr-2"></i> Try Again
        </a>

    </div>
</body>
</html>
HTML;
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Email and password are required.");
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        // Prepare statement to find user by email
        $stmt = $conn->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, now get roles
                $stmt_roles = $conn->prepare(
                    "SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?"
                );
                $stmt_roles->bind_param("i", $user['id']);
                $stmt_roles->execute();
                $roles_result = $stmt_roles->get_result();
                
                $roles = [];
                while ($row = $roles_result->fetch_assoc()) {
                    $roles[] = $row['name'];
                }
                
                // Store user data in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['roles'] = $roles;
                $_SESSION['loggedin'] = true;

                // Redirect to the home page after successful login
                header("Location: /JOBSEEKER/frontend/index.html");
                exit();

            } else {
                // Incorrect password
                show_error_message("Invalid email or password.");
            }
        } else {
            // User not found
            show_error_message("Invalid email or password.");
        }
        $stmt->close();
    }
    $conn->close();

} catch (Exception $e) {
    show_error_message("An error occurred: " . $e->getMessage());
}
?>

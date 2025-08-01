<?php
session_start();
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder";

// The message data is sent as a JSON payload in the request body
$data = json_decode(file_get_contents('php://input'), true);

// Basic validation to ensure all required data is present
if (!isset($_SESSION['user_id']) || !isset($data['recipient_id']) || !isset($data['message_text'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$recipient_id = filter_var($data['recipient_id'], FILTER_SANITIZE_NUMBER_INT);
$message_text = htmlspecialchars(trim($data['message_text']));

// Prevent empty messages from being sent
if (empty($message_text)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);

// Prepare and execute the SQL query to insert the new message
$sql = "INSERT INTO messages (sender_id, recipient_id, message_text) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $sender_id, $recipient_id, $message_text);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
}

$stmt->close();
$conn->close();
?>

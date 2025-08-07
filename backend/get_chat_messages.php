<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['contact_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$contact_id = filter_var($_GET['contact_id'], FILTER_SANITIZE_NUMBER_INT);
$conn = new mysqli($servername, $username, $password, $dbname);

// Fetch all messages between the current user and the selected contact
$sql = "
    SELECT sender_id, message_text, created_at
    FROM messages
    WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
    ORDER BY created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $current_user_id, $contact_id, $contact_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $date = new DateTime($row['created_at']);
    $row['time'] = $date->format('h:i A');
    // Determine if the message was sent by the current user or the other person
    $row['sender'] = ($row['sender_id'] == $current_user_id) ? 'me' : 'other';
    $row['text'] = htmlspecialchars($row['message_text']);
    $messages[] = $row;
}

// Mark messages as read since they are being fetched
$update_sql = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $contact_id, $current_user_id);
$update_stmt->execute();
$update_stmt->close();

// Get contact info for the chat header
$contact_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$contact_stmt->bind_param("i", $contact_id);
$contact_stmt->execute();
$contact_result = $contact_stmt->get_result();
$contact_info = $contact_result->fetch_assoc();
$contact_info['avatar'] = 'https://placehold.co/100x100/667eea/ffffff?text=' . strtoupper(substr($contact_info['full_name'], 0, 1));


echo json_encode(['success' => true, 'messages' => $messages, 'contact_info' => $contact_info]);

$stmt->close();
$contact_stmt->close();
$conn->close();
?>

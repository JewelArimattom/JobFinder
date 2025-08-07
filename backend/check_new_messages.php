<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, they have no new messages
    echo json_encode(['success' => true, 'has_new_messages' => false]);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conn = new mysqli($servername, $username, $password, $dbname);

// Query to count unread messages where the user is the recipient
$sql = "SELECT COUNT(id) as unread_count FROM messages WHERE recipient_id = ? AND is_read = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$has_new_messages = (int)$row['unread_count'] > 0;

echo json_encode(['success' => true, 'has_new_messages' => $has_new_messages]);

$stmt->close();
$conn->close();
?>

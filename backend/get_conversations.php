<?php
session_start();
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "jobfinder"; header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$conn = new mysqli($servername, $username, $password, $dbname);

// This query finds all unique users the current user has talked to,
// gets the last message, and orders by the most recent conversation.
$sql = "
    SELECT
        u.id as contact_id,
        u.full_name as contact_name,
        (SELECT message_text FROM messages WHERE (sender_id = u.id AND recipient_id = ?) OR (sender_id = ? AND recipient_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (sender_id = u.id AND recipient_id = ?) OR (sender_id = ? AND recipient_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message_timestamp
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT sender_id FROM messages WHERE recipient_id = ?
        UNION
        SELECT DISTINCT recipient_id FROM messages WHERE sender_id = ?
    ) AND u.id != ?
    ORDER BY last_message_timestamp DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed to prepare.']);
    exit;
}

$stmt->bind_param("iiiiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    if (empty($row['last_message_timestamp'])) continue;

    // Format the timestamp for a user-friendly display
    $date = new DateTime($row['last_message_timestamp']);
    $now = new DateTime();
    $interval = $now->diff($date);

    if ($interval->d == 0) {
        $row['timestamp_formatted'] = $date->format('h:i A');
    } elseif ($interval->d == 1) {
        $row['timestamp_formatted'] = 'Yesterday';
    } else {
        $row['timestamp_formatted'] = $date->format('M d');
    }
    
    // Create a placeholder avatar based on the user's initial
    $row['avatar'] = 'https://placehold.co/100x100/764ba2/ffffff?text=' . strtoupper(substr($row['contact_name'], 0, 1));

    $conversations[] = $row;
}

echo json_encode(['success' => true, 'conversations' => $conversations]);

$stmt->close();
$conn->close();
?>

<?php
session_start();
require_once 'config.php';

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Direct access not allowed.');
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate user id
if (!isset($data['user_id']) || $data['user_id'] <= 0 || $data['user_id'] != $_SESSION['user_id']) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$user_id = $data['user_id'];

// Mark notifications as read
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$success = $stmt->execute();

$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
?> 
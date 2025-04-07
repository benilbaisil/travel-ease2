<?php
session_start();
require_once 'config.php';

// Set content type to JSON before any output
header('Content-Type: application/json');

// Suppress PHP errors from being output
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Count unread notifications
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = isset($row['count']) ? (int)$row['count'] : 0;

    echo json_encode(['count' => $count]);

    $stmt->close();
} catch (Exception $e) {
    // Log the error to a file instead of displaying it
    error_log("Error in get_notification_count.php: " . $e->getMessage());
    
    // Return 0 count on error
    echo json_encode(['count' => 0, 'error' => 'An error occurred']);
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>
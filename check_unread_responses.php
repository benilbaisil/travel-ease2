<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $sql = "SELECT COUNT(*) as count 
            FROM enquiries 
            WHERE email = (SELECT email FROM users WHERE user_id = ?)
            AND status = 'Responded'
            AND (responded_at > last_viewed_at OR last_viewed_at IS NULL)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode(['count' => (int)$row['count']]);

} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}

exit(); 
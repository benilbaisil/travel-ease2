<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $sql = "SELECT e.*, 
            CASE WHEN e.responded_at > e.last_viewed_at OR e.last_viewed_at IS NULL THEN 0 ELSE 1 END as is_read
            FROM enquiries e 
            WHERE e.email = (SELECT email FROM users WHERE user_id = ?)
            ORDER BY e.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $enquiries = [];
    while ($row = $result->fetch_assoc()) {
        $enquiries[] = $row;
    }

    // Update last viewed timestamp
    $update_sql = "UPDATE enquiries SET last_viewed_at = NOW() 
                  WHERE email = (SELECT email FROM users WHERE user_id = ?)";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'enquiries' => $enquiries
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit(); 
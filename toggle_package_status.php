<?php
session_start();
require_once 'config.php';

// Debug: Log session information
error_log('User Role: ' . ($_SESSION['user_role'] ?? 'not set'));
error_log('User ID: ' . ($_SESSION['user_id'] ?? 'not set'));

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Staff' && $_SESSION['user_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id']) && isset($_POST['status'])) {
    $package_id = intval($_POST['package_id']);
    $status = intval($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE travel_packages SET active = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $package_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Package status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update package status'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request parameters'
    ]);
}

$conn->close();
?> 
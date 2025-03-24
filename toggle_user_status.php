<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are set
if (!isset($_POST['user_id']) || !isset($_POST['active'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "travel_booking";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$active = (int)$_POST['active'];

// Prevent deactivating your own account
if ($user_id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot modify your own account status']);
    exit();
}

try {
    // First check if the user exists and if the 'active' column exists
    $check_stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'active'");
    $check_stmt->execute();
    $column_exists = $check_stmt->get_result()->num_rows > 0;
    
    if (!$column_exists) {
        // Add 'active' column if it doesn't exist
        $conn->query("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
    }
    
    // Now proceed with the update
    $stmt = $conn->prepare("UPDATE users SET active = ? WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $active, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}

$conn->close(); 
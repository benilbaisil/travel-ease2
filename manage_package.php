<?php
session_start();

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Staff', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
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

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO travel_packages (package_name, description, price, duration, destination, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssdis", 
                $_POST['package_name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['duration'],
                $_POST['destination']
            );
            break;

        case 'edit':
            $stmt = $conn->prepare("UPDATE travel_packages SET package_name = ?, description = ?, price = ?, duration = ?, destination = ? WHERE package_id = ? AND (created_by = ? OR ? IN (SELECT user_id FROM users WHERE role = 'Admin'))");
            $stmt->bind_param("ssdiiii",
                $_POST['package_name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['duration'],
                $_POST['destination'],
                $_POST['package_id'],
                $_SESSION['user_id'],
                $_SESSION['user_id']
            );
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM travel_packages WHERE package_id = ? AND (created_by = ? OR ? IN (SELECT user_id FROM users WHERE role = 'Admin'))");
            $stmt->bind_param("iii", 
                $_POST['package_id'],
                $_SESSION['user_id'],
                $_SESSION['user_id']
            );
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }

    $success = $stmt->execute();
    echo json_encode(['success' => $success]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 
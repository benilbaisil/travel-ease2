<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Staff' && $_SESSION['user_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


// Check if package_id is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Package ID not provided']);
    exit();
}

$package_id = intval($_POST['id']);

// Delete the package from the database
$sql = "DELETE FROM travel_packages WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Package deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete package: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
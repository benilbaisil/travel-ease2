<?php

// Example API endpoint
$package_id = $_GET['id'];
$query = "SELECT * FROM travel_packages WHERE id = ? AND active = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Package not found or unavailable'
    ]);
    exit();
} 
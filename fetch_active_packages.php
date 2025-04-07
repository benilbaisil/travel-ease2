<?php
session_start();
require_once 'config.php';

// Fetch only active packages
$sql = "SELECT * FROM travel_packages WHERE active = 1 ORDER BY id DESC";
$result = $conn->query($sql);
$packages = [];
$html = '';

if ($result->num_rows > 0) {
    while ($package = $result->fetch_assoc()) {
        // Generate HTML for each package
        $html .= '<div class="package-card">';
        $html .= '<img src="' . htmlspecialchars($package['image_path']) . '" alt="' . htmlspecialchars($package['package_name']) . '" class="package-image">';
        $html .= '<div class="package-details">';
        $html .= '<h3 class="package-name">' . htmlspecialchars($package['package_name']) . '</h3>';
        $html .= '<p class="package-description">' . htmlspecialchars($package['description']) . '</p>';
        $html .= '<p class="package-price">₹' . number_format($package['price']) . '</p>';
        // Add more package details as needed
        $html .= '</div></div>';
    }
}

echo json_encode([
    'success' => true,
    'html' => $html
]);
?> 
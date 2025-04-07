<?php
session_start();

// Check if user is logged in and is an admin or staff
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'Staff')) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'config.php';

// Packages to delete
$packages_to_delete = [
    'Manali Adventures',
    'Kerala Backwater',
    'Goa Beach Vacation'
];

try {
    // Start transaction
    $conn->begin_transaction();

    // First, check if there are any active bookings for these packages
    $package_names = implode("','", array_map(function($name) use ($conn) {
        return $conn->real_escape_string($name);
    }, $packages_to_delete));

    $check_bookings_sql = "SELECT b.id, b.package_id, tp.package_name 
                          FROM bookings b 
                          JOIN travel_packages tp ON b.package_id = tp.id 
                          WHERE tp.package_name IN ('$package_names')";
    
    $result = $conn->query($check_bookings_sql);
    
    if ($result->num_rows > 0) {
        throw new Exception("Cannot delete packages as they have associated bookings. Please handle existing bookings first.");
    }

    // Delete the packages
    $delete_sql = "DELETE FROM travel_packages WHERE package_name IN ('$package_names')";
    if (!$conn->query($delete_sql)) {
        throw new Exception("Error deleting packages: " . $conn->error);
    }

    // If successful, commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Successfully deleted the specified packages.";

} catch (Exception $e) {
    // If there's an error, rollback changes
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Close connection
$conn->close();

// Redirect back to packages page
header("Location: packages.php");
exit();
?> 
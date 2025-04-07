<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get and validate date parameters
$startDate = isset($_GET['start']) ? $_GET['start'] : null;
$endDate = isset($_GET['end']) ? $_GET['end'] : null;

if (!$startDate || !$endDate) {
    echo json_encode(['error' => 'Start and end dates are required']);
    exit();
}

try {
    // Get package details for the date range
    $packageQuery = "
        SELECT 
            tp.id,
            tp.package_name,
            tp.price,
            tp.duration,
            COUNT(b.id) as booking_count,
            SUM(CASE WHEN b.booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN b.booking_status = 'Confirmed' THEN tp.price ELSE 0 END) as total_revenue
        FROM travel_packages tp
        LEFT JOIN bookings b ON tp.id = b.package_id
        WHERE b.created_at BETWEEN ? AND ?
        GROUP BY tp.id
        ORDER BY booking_count DESC
    ";

    $stmt = $conn->prepare($packageQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $packageResult = $stmt->get_result();

    $packages = [];
    $totalRevenue = 0;
    $totalBookings = 0;
    $totalPrice = 0;

    while ($row = $packageResult->fetch_assoc()) {
        $packages[] = $row;
        $totalRevenue += $row['total_revenue'];
        $totalBookings += $row['booking_count'];
        $totalPrice += $row['price'];
    }

    // Get booking details with user information
    $bookingQuery = "
        SELECT 
            b.id as booking_id,
            b.created_at as booking_date,
            b.booking_status,
            u.name as user_name,
            u.email as user_email,
            tp.package_name,
            tp.price
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN travel_packages tp ON b.package_id = tp.id
        WHERE b.created_at BETWEEN ? AND ?
        ORDER BY b.created_at DESC
    ";

    $stmt = $conn->prepare($bookingQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $bookingResult = $stmt->get_result();

    $bookings = [];
    while ($row = $bookingResult->fetch_assoc()) {
        $bookings[] = $row;
    }

    $response = [
        'packages' => $packages,
        'bookings' => $bookings,
        'totalPackages' => count($packages),
        'totalBookings' => $totalBookings,
        'totalRevenue' => $totalRevenue,
        'avgPrice' => $packages ? $totalPrice / count($packages) : 0,
        'uniqueUsers' => count(array_unique(array_column($bookings, 'user_email')))
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error fetching data: ' . $e->getMessage()]);
}
?> 
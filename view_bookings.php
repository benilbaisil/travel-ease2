<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

// Fetch all bookings for the current user
$sql = "SELECT b.*, tp.package_name, tp.destination, tp.duration 
        FROM bookings b 
        JOIN travel_packages tp ON b.package_id = tp.id 
        WHERE b.user_id = ? 
        ORDER BY b.travel_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - TravelEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation (same as booking.php) -->
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">My Bookings</h1>
        
        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <p class="text-gray-600">You haven't made any bookings yet.</p>
                <a href="userpackages.php" class="inline-block mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Browse Packages
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($bookings as $booking): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($booking['package_name']); ?>
                            </h2>
                            <div class="space-y-2 text-gray-600">
                                <p>
                                    <i class="fas fa-map-marker-alt w-6"></i>
                                    <?php echo htmlspecialchars($booking['destination']); ?>
                                </p>
                                <p>
                                    <i class="far fa-calendar-alt w-6"></i>
                                    <?php echo date('F j, Y', strtotime($booking['travel_date'])); ?>
                                </p>
                                <p>
                                    <i class="fas fa-users w-6"></i>
                                    <?php echo htmlspecialchars($booking['num_guests']); ?> Guests
                                </p>
                                <p>
                                    <i class="fas fa-clock w-6"></i>
                                    <?php echo htmlspecialchars($booking['duration']); ?> Days
                                </p>
                                <p>
                                    <i class="fas fa-receipt w-6"></i>
                                    Booking ID: <?php echo htmlspecialchars($booking['id']); ?>
                                </p>
                                <p>
                                    <i class="fas fa-rupee-sign w-6"></i>
                                    ₹<?php echo number_format($booking['total_amount']); ?>
                                </p>
                                <p class="mt-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm
                                    <?php echo $booking['booking_status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($booking['booking_status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer (same as booking.php) -->
</body>
</html> 
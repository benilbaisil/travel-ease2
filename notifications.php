<?php
// notifications.php

session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role']; // Make sure you have user_role in session

// Fetch booking notifications
$sql = "SELECT b.id, b.travel_date, b.booking_status, b.total_amount, 
               tp.package_name, tp.destination, b.created_at,
               u.name as customer_name
        FROM bookings b
        JOIN travel_packages tp ON b.package_id = tp.id
        JOIN users u ON b.user_id = u.user_id
        WHERE " . ($user_role === 'Staff' ? "1=1" : "b.user_id = ?") . "
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
if ($user_role !== 'Staff') {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
$booking_notifications = [];
while ($row = $result->fetch_assoc()) {
    $booking_notifications[] = $row;
}

// Fetch system notifications
$sql = "SELECT n.*, u.name as sender_name 
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$system_notifications = [];
while ($row = $result->fetch_assoc()) {
    $system_notifications[] = $row;
}

// Mark notifications as read
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - TravelEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notification-card {
            transition: transform 0.2s ease-in-out;
        }
        .notification-card:hover {
            transform: translateY(-2px);
        }
        .unread {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Notifications</h1>
            <button onclick="window.history.back();" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </button>
        </div>

        <!-- Booking Notifications -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">
                <i class="fas fa-calendar-check mr-2 text-blue-500"></i>
                <?php echo $user_role === 'Staff' ? 'Recent Bookings' : 'Your Bookings'; ?>
            </h2>
            
            <?php if (empty($booking_notifications)): ?>
                <p class="text-gray-600 text-center py-4">No booking notifications available.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($booking_notifications as $notification): ?>
                        <div class="notification-card bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start">
                                <div>
                                    <?php if ($user_role === 'Staff'): ?>
                                        <p class="text-sm text-gray-600 mb-1">
                                            Booked by: <?php echo htmlspecialchars($notification['customer_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($notification['package_name']); ?>
                                    </h3>
                                    <p class="text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?php echo htmlspecialchars($notification['destination']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        Travel Date: <?php echo date('F j, Y', strtotime($notification['travel_date'])); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <i class="fas fa-rupee-sign mr-1"></i>
                                        Amount: ₹<?php echo number_format($notification['total_amount'], 2); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        <?php
                                        switch($notification['booking_status']) {
                                            case 'Confirmed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($notification['booking_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="far fa-clock mr-1"></i>
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- System Notifications -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold mb-4">
                <i class="fas fa-bell mr-2 text-blue-500"></i>
                System Notifications
            </h2>
            
            <?php if (empty($system_notifications)): ?>
                <p class="text-gray-600 text-center py-4">No system notifications available.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($system_notifications as $notification): ?>
                        <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?> 
                                    bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between">
                                <div class="flex-grow">
                                    <p class="text-gray-800">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-2">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </p>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">
                                        New
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add animation for new notifications
        document.addEventListener('DOMContentLoaded', function() {
            const unreadNotifications = document.querySelectorAll('.unread');
            unreadNotifications.forEach(notification => {
                notification.style.animation = 'fadeIn 0.5s ease-in';
            });
        });
    </script>
</body>
</html>

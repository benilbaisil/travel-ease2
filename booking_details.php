<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch booking details from database
$sql = "SELECT b.*, p.package_name, p.destination, p.duration, p.price, 
        DATE(b.created_at) as booking_date
        FROM bookings b 
        JOIN travel_packages p ON b.package_id = p.id 
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

// If booking not found or doesn't belong to user, redirect
if (!$booking) {
    $_SESSION['error'] = 'Booking not found or unauthorized access.';
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - TravelEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .details-container {
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)),
                        url('/travel%20ease/img/travel-concept-with-landmarks.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .details-card {
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .status-confirmed {
            background-color: #DEF7EC;
            color: #03543F;
        }

         .print-btn {
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            transform: translateY(-2px);
        } 

        /* Dropdown animations */
        .group:hover .hidden {
            display: block;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Notification badge animation */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .bg-red-500 {
            animation: pulse 2s infinite;
        }

        /* Hover effects */
        .hover\:bg-blue-50:hover {
            background-color: #EBF5FF;
        }

        .hover\:bg-red-50:hover {
            background-color: #FEF2F2;
        }

        /* Transition for dropdown arrow */
        .group:hover .group-hover\:rotate-180 {
            transform: rotate(180deg);
        }

        /* Navigation Bar */
        nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 2rem;
            padding: 0.5rem 2rem;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }

        .logo-container {
            position: relative;
            padding: 0.5rem 0;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .logo-text {
            background: linear-gradient(135deg, #8B5CF6, #6D28D9, #5B21B6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -1px;
            animation: glow 3s ease-in-out infinite alternate;
            position: relative;
        }

        .logo-underline {
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #8B5CF6, #6D28D9, #5B21B6, transparent);
            border-radius: 2px;
            animation: shimmer 2s infinite;
        }

        .nav-links-container {
            background: rgba(243, 244, 246, 0.7);
            padding: 0.75rem;
            border-radius: 1rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            transform-style: preserve-3d;
        }

        .nav-link {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
        }

        .nav-link:hover {
            color: #6D28D9;
            background: rgba(109, 40, 217, 0.1);
            transform: translateY(-2px) translateZ(10px);
            box-shadow: 0 4px 12px rgba(109, 40, 217, 0.15);
        }

        .nav-link i {
            transition: transform 0.3s ease;
        }

        .nav-link:hover i {
            transform: scale(1.2) rotate(-5deg) translateZ(20px);
        }

        .active-nav {
            color: #6D28D9;
            background: rgba(109, 40, 217, 0.15);
            box-shadow: 0 2px 8px rgba(109, 40, 217, 0.2);
        }

        /* Enhanced 3D Animations */
        @keyframes glow {
            0% {
                text-shadow: 0 0 5px rgba(139, 92, 246, 0.2);
                transform: translateZ(0);
            }
            100% {
                text-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
                transform: translateZ(10px);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% center;
                transform: translateZ(5px);
            }
            100% {
                background-position: 200% center;
                transform: translateZ(0);
            }
        }

        /* Floating animation with 3D effect */
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateZ(0) rotateX(0);
            }
            50% {
                transform: translateY(-5px) translateZ(10px) rotateX(5deg);
            }
        }

        .logo-container {
            animation: float 3s ease-in-out infinite;
        }

        /* Enhanced hover effects with 3D */
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(139, 92, 246, 0.1),
                transparent
            );
            transition: 0.5s;
            transform: translateZ(-1px);
        }

        .nav-link:hover::before {
            left: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-text {
                font-size: 2.5rem;
            }

            .nav-links-container {
                flex-direction: column;
                width: 100%;
                padding: 0.5rem;
            }

            .nav-link {
                width: 100%;
                margin: 0.25rem 0;
                justify-content: center;
            }
        }

        /* Pulse animation with 3D effect */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(109, 40, 217, 0.4);
                transform: translateZ(0);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(109, 40, 217, 0);
                transform: translateZ(5px);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(109, 40, 217, 0);
                transform: translateZ(0);
            }
        }

        .active-nav {
            animation: pulse 2s infinite;
        }

        /* Add sparkle effect */
        @keyframes sparkle {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        .logo-text::after {
            content: '✨';
            position: absolute;
            right: -20px;
            top: 0;
            font-size: 1.5rem;
            animation: sparkle 2s infinite;
        }

        /* Add subtle background pattern */
        .nav-links-container {
            background-image: linear-gradient(45deg, rgba(139, 92, 246, 0.05) 25%, transparent 25%),
                              linear-gradient(-45deg, rgba(139, 92, 246, 0.05) 25%, transparent 25%),
                              linear-gradient(45deg, transparent 75%, rgba(139, 92, 246, 0.05) 75%),
                              linear-gradient(-45deg, transparent 75%, rgba(139, 92, 246, 0.05) 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col items-center h-auto py-4">
                <!-- Enhanced Logo -->
                <div class="logo-container mb-4">
                    <div class="text-4xl font-bold logo-text">Booking Details</div>
                    <div class="logo-underline"></div>
                </div>
                
                <!-- Enhanced Navigation -->
                <div class="hidden md:flex space-x-6 nav-links-container">
                    <a href="home.php" class="nav-link">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="userpackages.php" class="nav-link">
                        <i class="fas fa-box mr-2"></i> Packages
                    </a>
                    <a href="my_bookings.php" class="nav-link">
                        <i class="fas fa-bookmark mr-2"></i> My Bookings
                    </a>
                    <a href="home.php#about" class="nav-link">
                        <i class="fas fa-info-circle mr-2"></i> About Us
                    </a>
                    <a href="home.php#enquiry" class="nav-link">
                        <i class="fas fa-envelope mr-2"></i> Contact
                    </a>
                    <!-- <div class="relative">
                        <a href="notifications.php" class="nav-link">
                            <i class="fas fa-bell mr-2"></i> Notifications
                            <span class="notification-badge absolute -top-1 -right-1 inline-block w-4 h-4 bg-red-600 text-white text-xs rounded-full text-center hidden"></span>
                        </a>
                    </div> -->
                </div>
            </div>
        </div>
    </nav>

    <!-- Booking Details Section -->
    <div class="details-container min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4">
            <div class="details-card bg-white rounded-xl shadow-xl p-8">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Booking Details</h1>
                    <div class="space-x-4">
                        <!-- <button onclick="window.print()" class="print-btn bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                            <i class="fas fa-print mr-2"></i>Print
                        </button> -->
                        <a href="generate_pdf.php?booking_id=<?php echo $booking['id']; ?>" class="print-btn bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 inline-block">
                            <i class="fas fa-download mr-2"></i>Download PDF
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Booking Information -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking Information</h2>
                            <div class="space-y-3">
                                <p class="text-gray-600">
                                    <span class="font-semibold">Booking ID:</span> 
                                    #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Booking Date:</span> 
                                    <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Travel Date:</span> 
                                    <?php echo date('F j, Y', strtotime($booking['travel_date'])); ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Number of Guests:</span> 
                                    <?php echo isset($booking['num_guests']) ? $booking['num_guests'] : 'N/A'; ?>
                                </p>
                                <div>
                                    <span class="font-semibold text-gray-600">Status:</span>
                                    <span class="status-badge status-confirmed ml-2">
                                        <i class="fas fa-check-circle mr-1"></i>Confirmed
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Information</h2>
                            <div class="space-y-3">
                                <p class="text-gray-600">
                                    <span class="font-semibold">Payment ID:</span> 
                                    <?php echo $booking['payment_id']; ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Amount Paid:</span> 
                                    <span class="text-green-600 font-semibold">
                                        ₹<?php echo number_format($booking['price'] * $booking['num_guests']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Package Information -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Package Details</h2>
                            <div class="space-y-3">
                                <p class="text-gray-600">
                                    <span class="font-semibold">Package Name:</span><br>
                                    <?php echo htmlspecialchars($booking['package_name']); ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Destination:</span><br>
                                    <?php echo htmlspecialchars($booking['destination']); ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Duration:</span><br>
                                    <?php echo htmlspecialchars($booking['duration']); ?> Days
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Price per Person:</span><br>
                                    ₹<?php echo number_format($booking['price']); ?>
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold">Total Amount:</span><br>
                                    ₹<?php echo number_format($booking['price'] * $booking['num_guests']); ?>
                                    (<?php echo $booking['num_guests']; ?> guests)
                                </p>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Contact Information</h2>
                            <div class="space-y-3">
                                <p class="text-gray-600">
                                    <span class="font-semibold">Phone:</span><br>
                                    <?php echo htmlspecialchars($booking['phone']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="userpackages.php" class="inline-block bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Packages
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center">
                <p>© 2024 TravelEase. All rights reserved.</p>
                <p class="mt-2">For support, contact: support@travelease.com</p>
            </div>
        </div>
    </footer>

    <!-- Add this right before the closing </body> tag -->
    <script>
        // Function to fetch notification count
        function fetchNotificationCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const notificationBadge = document.querySelector('.notification-badge');
                    if (notificationBadge) {
                        if (data.count > 0) {
                            notificationBadge.textContent = data.count;
                            notificationBadge.classList.remove('hidden');
                        } else {
                            notificationBadge.classList.add('hidden');
                        }
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));
        }

        // Fetch notification count when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotificationCount();
            
            // Refresh notification count every 30 seconds
            setInterval(fetchNotificationCount, 30000);
        });
    </script>
</body>
</html> 
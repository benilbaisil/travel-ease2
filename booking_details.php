<?php
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">
                    <i class="fas fa-plane-departure mr-2"></i>TravelEase
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="home.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="userpackages.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-box mr-1"></i> Packages
                    </a>
                    <a href="my_bookings.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-calendar-check mr-1"></i> My Bookings
                    </a>
                    <a href="home.php#about" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-info-circle mr-1"></i> About
                    </a>
                    <a href="home.php#contact" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-envelope mr-1"></i> Contact
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="notifications.php" class="text-gray-700 hover:text-blue-600 relative">
                        <i class="fas fa-bell text-xl"></i>
                        <?php
                        // Get unread notifications count
                        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $count = $result->fetch_assoc()['count'];
                        
                        if ($count > 0) {
                            echo '<span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">' . $count . '</span>';
                        }
                        ?>
                    </a>
                    
                    <!-- User Menu -->
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-blue-600">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                            <i class="fas fa-chevron-down text-sm transition-transform duration-200 group-hover:rotate-180"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block z-50">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">Signed in as</p>
                                <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                            </div>
                            <a href="my_bookings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
                                <i class="fas fa-calendar mr-2"></i> My Bookings
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
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
</body>
</html> 
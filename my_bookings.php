<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

// Fetch all bookings for the current user
$sql = "SELECT b.*, p.package_name, p.destination, p.duration, p.price,
        DATE(b.created_at) as booking_date
        FROM bookings b 
        JOIN travel_packages p ON b.package_id = p.id 
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-image: url('/travel-ease/img/cool-girl-sunglasses-beret-stylish-coat-smiles-softly-sits-suitcase-isolated-brunette-woman-denim-pants-poses-holds-map-yellow-background.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            position: relative;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.85);
            z-index: -1;
        }
        .hero {
            background-image: url('https://example.com/hero-image.jpg');
            background-size: cover;
            background-position: center;
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .hero h1 {
            font-size: 3rem;
            animation: fadeInDown 1s;
        }
        .btn-gradient {
            background: linear-gradient(to right, #ff7e5f, #feb47b);
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: scale(1.05);
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        /* New Styles */
        .card {
            border: 2px solid #e2e8f0;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .nav-link {
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: #ff7e5f;
        }
        .footer {
            background: linear-gradient(to right, #2c3e50, #4ca1af);
        }
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
        }
        .logo-text {
            background: linear-gradient(135deg, #3b82f6, #2563eb, #1d4ed8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -1px;
            animation: glow 3s ease-in-out infinite alternate;
        }
        .logo-underline {
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #3b82f6, #2563eb, #1d4ed8, transparent);
            border-radius: 2px;
            animation: shimmer 2s infinite;
        }
        .nav-links-container {
            background: rgba(243, 244, 246, 0.7);
            padding: 0.75rem;
            border-radius: 1rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
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
        }
        .nav-link:hover {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        .nav-link i {
            transition: transform 0.3s ease;
        }
        .nav-link:hover i {
            transform: scale(1.2) rotate(-5deg);
        }
        .active-nav {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.15);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }
        @keyframes glow {
            0% {
                text-shadow: 0 0 5px rgba(59, 130, 246, 0.2);
            }
            100% {
                text-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
            }
        }
        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }
            100% {
                background-position: 200% center;
            }
        }
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }
        .logo-container {
            animation: float 3s ease-in-out infinite;
        }
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
                rgba(59, 130, 246, 0.1),
                transparent
            );
            transition: 0.5s;
        }
        .nav-link:hover::before {
            left: 100%;
        }
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
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(37, 99, 235, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
            }
        }
        .active-nav {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Hero Section -->
    <!-- Removed the hero section -->
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col items-center h-auto py-4">
                <!-- Enhanced Logo -->
                <div class="logo-container mb-4">
                    <div class="text-4xl font-bold logo-text">My Bookings</div>
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
                    <a href="my_bookings.php" class="nav-link active-nav">
                        <i class="fas fa-bookmark mr-2"></i> My Bookings
                    </a>
                    <a href="home.php#about" class="nav-link">
                        <i class="fas fa-info-circle mr-2"></i> About Us
                    </a>
                    <a href="home.php#enquiry" class="nav-link">
                        <i class="fas fa-envelope mr-2"></i> Contact
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Back Button above the Bookings List Section -->
    <div class="max-w-7xl mx-auto px-4 py-4">
        <a href="javascript:history.back()" class="inline-block bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 transform hover:scale-105">
            Back
        </a>
    </div>

    <!-- Bookings List Section -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-8 text-center animate__animated animate__fadeIn">
            My Travel Adventures
        </h1>
        <?php if (empty($bookings)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center max-w-2xl mx-auto animate__animated animate__fadeIn">
            <i class="fas fa-suitcase-rolling text-6xl text-gray-400 mb-4"></i>
            <p class="text-gray-600 text-lg">You haven't made any bookings yet.</p>
            <a href="userpackages.php" class="inline-block mt-6 btn-gradient text-white px-8 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                Explore Packages
            </a>
        </div>
        <?php else: ?>
        <div class="grid gap-8">
            <?php foreach ($bookings as $booking): ?>
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 animate__animated animate__fadeIn card">
                <div class="flex flex-col md:flex-row justify-between">
                    <div class="flex-1">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                            <?php echo htmlspecialchars($booking['package_name']); ?>
                        </h2>
                        <div class="space-y-3">
                            <p class="text-gray-600 flex items-center">
                                <i class="fas fa-map-marker-alt w-8 text-green-500"></i>
                                <?php echo htmlspecialchars($booking['destination']); ?>
                            </p>
                            <p class="text-gray-600 flex items-center">
                                <i class="fas fa-calendar w-8 text-blue-500"></i>
                                Travel Date: <?php echo date('F j, Y', strtotime($booking['travel_date'])); ?>
                            </p>
                            <p class="text-gray-600 flex items-center">
                                <i class="fas fa-users w-8 text-purple-500"></i>
                                Guests: <?php echo $booking['num_guests']; ?>
                            </p>
                        </div>
                    </div>
                    <div class="md:text-right mt-6 md:mt-0 md:ml-8">
                        <p class="text-gray-500">
                            Booking ID: #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>
                        </p>
                        <p class="text-2xl font-bold text-green-600 mt-3">
                            ₹<?php echo number_format($booking['price'] * $booking['num_guests']); ?>
                        </p>
                        <a href="booking_details.php?id=<?php echo $booking['id']; ?>" 
                           class="inline-block mt-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 transform hover:scale-105">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer text-white py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center">
                <p>© 2024 TravelEase. All rights reserved.</p>
                <p class="mt-2">For support, contact: support@travelease.com</p>
            </div>
        </div>
    </footer>
</body>
</html> 
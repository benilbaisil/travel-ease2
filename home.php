<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    // Update session with latest user data
    $_SESSION['name'] = $user_data['name'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['phone'] = $user_data['phone_number'];
    $_SESSION['created_at'] = $user_data['created_at'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelEase - Book Your Dream Vacation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        
:root {
    --primary-color: #202020;
    --primary-hover: #535654;
    --text-dark: #1f2937;
    --text-light: #4b5563;
}

/* Reset and base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
}

/* Navigation */
.nav-link {
    position: relative;
    transition: color 0.3s ease;
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: var(--primary-color);
    transition: width 0.3s ease;
}

.nav-link:hover::after {
    width: 100%;
}

/* Hero section */
.hero {
    position: relative;
    min-height: 100vh;
    animation: fadeIn 1s ease-in;
}

.search-form {
    backdrop-filter: blur(8px);
    transition: transform 0.3s ease;
}

.search-form:hover {
    transform: translateY(-5px);
}

/* Form elements */
input, select {
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input:focus, select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

/* Destination cards */
.destination-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.destination-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.destination-card img {
    transition: transform 0.5s ease;
}

.destination-card:hover img {
    transform: scale(1.1);
}

/* Buttons */
.btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn:active::after {
    width: 300px;
    height: 300px;
}

/* Footer */
.footer-link {
    transition: color 0.3s ease;
    position: relative;
}

.footer-link::before {
    content: '→';
    position: absolute;
    left: -20px;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.footer-link:hover::before {
    opacity: 1;
    transform: translateX(10px);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .hero {
        min-height: 60vh;
    }
    
    .search-form {
        padding: 1rem;
    }
    
    .destination-card:hover {
        transform: translateY(-5px);
    }
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-hover);
}

.home-container {
    background-image: url('/travel%20ease/img/travel-concept-with-landmarks.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 100vh;
    width: 100%;
    position: relative;
}

/* Optional overlay to ensure text remains readable */
.overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3); /* Semi-transparent dark overlay */
}

/* Modern Color Scheme */
:root {
    --primary-color: #2563eb;
    --secondary-color: #3b82f6;
    --accent-color: #60a5fa;
    --background-light: #f0f9ff;
    --text-dark: #1e3a8a;
}

/* Enhanced Hero Section */
.hero {
    position: relative;
    min-height: 100vh;
    background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
        url('/travel%20ease/img/travel-concept-with-landmarks.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: white;
}

.hero h1 {
    font-size: 4rem;
    font-weight: 800;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 1s ease-out;
}

/* Enhanced Navigation */
.nav-link {
    position: relative;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: var(--primary-color);
    transform: translateY(-2px);
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background-color: var(--primary-color);
    transition: width 0.3s ease;
}

.nav-link:hover::after {
    width: 100%;
}

/* Enhanced User Menu */
.user-menu-button {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    transition: all 0.3s ease;
}

.user-menu-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Enhanced Cards */
.card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

/* Enhanced About Section */
#about {
    background: linear-gradient(135deg, var(--background-light) 0%, white 100%);
    padding: 6rem 0;
}

.about-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.about-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

/* Enhanced Contact Form */
.contact-form input,
.contact-form textarea {
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.contact-form input:focus,
.contact-form textarea:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Enhanced Buttons */
.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 9999px;
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Enhanced Footer */
footer {
    background: linear-gradient(135deg, var(--text-dark) 0%, #1e40af 100%);
    color: white;
    padding: 4rem 0;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Enhanced Notification Badge */
.notification-badge {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Enhanced Modal Styles */
.modal {
    backdrop-filter: blur(8px);
}

.modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Responsive Design Improvements */
@media (max-width: 768px) {
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .nav-link {
        padding: 0.25rem 0.5rem;
    }
    
    .about-card {
        margin-bottom: 1rem;
    }
}

/* 3D Card Effects */
.card-3d {
    perspective: 1000px;
    transform-style: preserve-3d;
    transition: transform 0.5s;
}

.card-3d:hover {
    transform: rotateY(5deg) rotateX(5deg) translateZ(10px);
    box-shadow: 
        -20px 20px 30px rgba(0, 0, 0, 0.2),
        0 0 20px rgba(0, 0, 0, 0.1);
}

/* 3D Button Effects */
.btn-3d {
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.3s, box-shadow 0.3s;
}

.btn-3d:hover {
    transform: translateY(-4px) translateZ(10px);
    box-shadow: 
        0 4px 12px rgba(0, 0, 0, 0.2),
        0 0 20px rgba(37, 99, 235, 0.2);
}

.btn-3d:active {
    transform: translateY(2px) translateZ(5px);
}

/* 3D Navigation Effects */
.nav-3d {
    transform-style: preserve-3d;
    perspective: 1000px;
}

.nav-link-3d {
    position: relative;
    transition: transform 0.3s;
    transform-style: preserve-3d;
}

.nav-link-3d:hover {
    transform: translateZ(20px);
}

.nav-link-3d::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
    transform: scaleX(0);
    transition: transform 0.3s;
    transform-origin: center;
}

.nav-link-3d:hover::after {
    transform: scaleX(1);
}

/* 3D Hero Section */
.hero-3d {
    position: relative;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.hero-content {
    transform: translateZ(50px);
    transition: transform 0.5s;
}

.hero-3d:hover .hero-content {
    transform: translateZ(70px);
}

/* 3D About Cards */
.about-card-3d {
    transform-style: preserve-3d;
    transition: transform 0.5s, box-shadow 0.5s;
}

.about-card-3d:hover {
    transform: translateZ(20px) rotateX(5deg);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.1),
        0 0 20px rgba(37, 99, 235, 0.2);
}

.about-icon-3d {
    transform: translateZ(30px);
    transition: transform 0.3s;
}

.about-card-3d:hover .about-icon-3d {
    transform: translateZ(40px) scale(1.1);
}

/* 3D Form Elements */
.input-3d {
    transform: translateZ(0);
    transition: transform 0.3s, box-shadow 0.3s;
}

.input-3d:focus {
    transform: translateZ(10px);
    box-shadow: 
        0 8px 16px rgba(0, 0, 0, 0.1),
        0 0 20px rgba(37, 99, 235, 0.2);
}

/* 3D Footer */
.footer-3d {
    position: relative;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.footer-content {
    transform: translateZ(20px);
}

/* Floating Animation */
@keyframes float {
    0% { transform: translateZ(0) translateY(0); }
    50% { transform: translateZ(20px) translateY(-10px); }
    100% { transform: translateZ(0) translateY(0); }
}

.float-animation {
    animation: float 3s ease-in-out infinite;
}

/* Back button styles */
.btn-3d {
    padding: 0.5rem;
    border-radius: 0.5rem;
    background-color: #f3f4f6;
    transition: all 0.3s ease;
}

.btn-3d:hover {
    transform: translateY(-2px);
    background-color: #e5e7eb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.btn-3d:active {
    transform: translateY(0);
}

/* Modal animation */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#enquiryResponsesModal .bg-white {
    animation: modalSlideIn 0.3s ease-out;
}
    </style>
</head>
<body class="font-sans">
    <!-- Navigation -->
    <nav class="nav-3d bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">TravelEase</div>
                <div class="hidden md:flex space-x-8">
                    <a href="#" class="nav-link-3d text-gray-700 hover:text-blue-600">Home</a>
                    <a href="userpackages.php" class="text-gray-700 hover:text-blue-600">Packages</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="my_bookings.php" class="text-gray-700 hover:text-blue-600">My Bookings</a>
                    <?php endif; ?>
                    <a href="#about" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600">Contact</a>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Icon -->
                    <div class="relative">
                        <a href="notifications.php" class="notification-button text-gray-600 hover:text-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="notification-badge absolute top-0 right-0 inline-block w-4 h-4 bg-red-600 text-white text-xs rounded-full text-center hidden"></span>
                        </a>
                        <!-- Notification Dropdown -->
                        <div id="notificationDropdown" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg py-2 hidden z-50">
                            <div id="notificationContent" class="p-4 text-gray-700">
                                <!-- Booking details will be loaded here -->
                                <p class="text-center text-gray-500">No new notifications</p>
                            </div>
                        </div>
                    </div>
                    <?php if(isset($_SESSION['name'])): ?>
                        <div class="relative group">
                            <button id="userMenuButton" class="btn-3d user-menu-button flex items-center space-x-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                    </div>
                                    <span class="ml-2 text-gray-800"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>
                            <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
                                <a href="my_bookings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    My Bookings
                                </a>
                                <button onclick="showEnquiryResponses()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    My Enquiries
                                    <span id="unreadResponsesBadge" class="hidden ml-2 px-2 py-1 text-xs bg-red-500 text-white rounded-full"></span>
                                </button>
                                <button onclick="openProfileModal()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    My Profile
                                </button>
                                <div class="border-t border-gray-100"></div>
                                <form action="logout.php" method="POST" id="logoutForm">
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-gray-900">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero hero-3d">
        <div class="hero-content max-w-7xl mx-auto px-4 text-center relative z-10">
            <h1 class="text-5xl md:text-6xl font-extrabold text-white mb-6 leading-tight float-animation">
                Discover Your Next Adventure
            </h1>
            <p class="text-xl md:text-2xl text-white mb-8 opacity-90">
                Experience the world's most beautiful destinations with TravelEase
            </p>
            <a href="userpackages.php" class="btn-3d btn-primary inline-block">
                Explore Packages
            </a>
        </div>
    </div>

    <!-- Popular Destinations -->
    <!-- <div class="py-16 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Budget Friendly packages</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Destination Cards -->
                <!-- <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <img src="/travel%20ease/img/kovalam-beach-3.jpg" alt="Kerala - Kovalam Beach" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Kerala</h3>
                        <p class="text-gray-600 mb-4">Experience gods own country</p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold">From ₹7000</span>
                            <div class="space-x-2">
                                <a href="booking.php?destination=kerala" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Book Now</a>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                            </div> -->
                        </div>
                    </div>
                </div> -->

                <!-- <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <img src="/travel%20ease/img/cubbon-park-bengaluru%20ka.jpg" alt="Karnataka - Cubbon Park" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Karnataka</h3>
                        <p class="text-gray-600 mb-4">explore the nature</p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold">From ₹5000</span>
                            <div class="space-x-2">
                                <a href="booking.php?destination=karnataka" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Book Now</a>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                            </div> -->
                        </div>
                    </div>
                </div>

                <!-- <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <img src="/travel%20ease/img/jaipur%20ka.jpg" alt="Rajasthan - Jaipur" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Rajasthan</h3>
                        <p class="text-gray-600 mb-4"> explore the royal city</p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold">From ₹6500</span>
                            <div class="space-x-2">
                                <a href="booking.php?destination=rajasthan" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Book Now</a>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div id="about" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">About TravelEase</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <h3 class="text-2xl font-semibold text-gray-800">Your Journey, Our Passion</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Founded in 2025, TravelEase has become one of India's leading travel platforms, 
                        dedicated to making your travel dreams a reality. We believe that every journey 
                        tells a story, and we're here to help you write yours.
                    </p>
                    <div class="grid grid-cols-2 gap-6 mt-8">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-3xl font-bold text-blue-600">10K+</h4>
                            <p class="text-gray-600">Happy Travelers</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-3xl font-bold text-blue-600">500+</h4>
                            <p class="text-gray-600">Destinations</p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div class="about-card-3d bg-white p-6 rounded-lg shadow-lg">
                            <div class="about-icon-3d">
                                <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold mb-2">Best Price Guarantee</h4>
                            <p class="text-gray-600">We ensure the most competitive prices for all our travel packages.</p>
                        </div>
                        <div class="about-card-3d bg-white p-6 rounded-lg shadow-lg">
                            <div class="about-icon-3d">
                                <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold mb-2">Secure Booking</h4>
                            <p class="text-gray-600">Your payments and personal information are always protected.</p>
                        </div>
                    </div>
                    <div class="space-y-4 mt-8">
                        <div class="about-card-3d bg-white p-6 rounded-lg shadow-lg">
                            <div class="about-icon-3d">
                                <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold mb-2">24/7 Support</h4>
                            <p class="text-gray-600">Our travel experts are always here to help you.</p>
                        </div>
                        <div class="about-card-3d bg-white p-6 rounded-lg shadow-lg">
                            <div class="about-icon-3d">
                                <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold mb-2">Fast Booking</h4>
                            <p class="text-gray-600">Quick and hassle-free booking process.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div id="contact" class="py-16 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Contact Us</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Contact Information -->
                <div class="col-span-1">
                    <div class="space-y-6">
                        <!-- Phone -->
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <div class="flex items-center space-x-4">
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Phone</h3>
                                    <p class="text-gray-600">+1 234 567 8900</p>
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <div class="flex items-center space-x-4">
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Email</h3>
                                    <p class="text-gray-600">info@travelease.com</p>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <div class="flex items-center space-x-4">
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Address</h3>
                                    <p class="text-gray-600">123 Travel Street</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enquiry Form -->
                <div class="col-span-2">
                    <div class="bg-white rounded-lg shadow-lg p-8">
                        <h3 class="text-2xl font-semibold mb-6">Send us a Message</h3>
                        <form id="enquiryForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="enquiryName">
                                        Your Name
                                    </label>
                                    <input type="text" id="enquiryName" name="name" required
                                        class="input-3d w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="enquiryEmail">
                                        Email Address
                                    </label>
                                    <input type="email" id="enquiryEmail" name="email" required
                                        class="input-3d w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">
                                    Subject
                                </label>
                                <input type="text" id="subject" name="subject" required
                                    class="input-3d w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                                    Message
                                </label>
                                <textarea id="message" name="message" rows="4" required
                                    class="input-3d w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Write your message here..."></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-3d bg-gray-800 text-white py-12">
        <div class="footer-content max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">TravelEase</h3>
                    <p>Making travel dreams come true</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#about" class="hover:text-blue-400">About Us</a></li>
                        <li><a href="#contact" class="hover:text-blue-400">Contact</a></li>
                        <li><a href="#" class="hover:text-blue-400">Terms</a></li>
                        <li><a href="#" class="hover:text-blue-400">Privacy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li>Email: info@travelease.com</li>
                        <li>Phone: +1 234 567 8900</li>
                        <li>Address: 123 Travel Street</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Newsletter</h3>
                    <div class="flex">
                        <input type="email" placeholder="Your email" class="p-2 rounded-l text-gray-800">
                        <button class="bg-blue-600 px-4 py-2 rounded-r hover:bg-blue-700">Subscribe</button>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Registration Modal -->
    <div id="registerModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="modal-content bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Create Account</h2>
                <button onclick="closeRegisterModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="registerForm" class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="registerName">Full Name</label>
                    <input type="text" id="registerName" name="name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" name="email" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                    Register
                </button>
            </form>
        </div>
    </div>

    <!-- Profile Update Modal -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Profile</h2>
                <button onclick="closeProfileModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Profile Information Section -->
            <div id="profileInfo" class="mb-8">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center text-white text-4xl font-bold">
                        <?php echo isset($_SESSION['name']) ? strtoupper(substr($_SESSION['name'], 0, 1)) : ''; ?>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600 font-medium">Name:</span>
                        <span class="text-gray-800"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?></span>
                    </div>
                    <?php if(isset($_SESSION['email'])): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600 font-medium">Email:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- <?php if(isset($_SESSION['phone'])): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600 font-medium">Phone:</span>
                        <span class="text-gray-800"><?php echo htmlspecialchars($_SESSION['phone']); ?></span>
                    </div>
                    <?php endif; ?> -->
                    <?php if(isset($_SESSION['created_at'])): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600 font-medium">Member Since:</span>
                        <span class="text-gray-800"><?php echo date('F j, Y', strtotime($_SESSION['created_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600 font-medium">Account Status:</span>
                        <span class="text-green-600 font-semibold">Active</span>
                    </div>
                    <button onclick="showEditProfile()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                        Edit Profile
                    </button>
                </div>
            </div>

            <!-- Edit Profile Form (Hidden by default) -->
            <div id="editProfileForm" class="mb-8 hidden">
                <form id="updateProfileForm" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editName">Name</label>
                        <input type="text" id="editName" name="name" value="<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editEmail">Email</label>
                        <input type="email" id="editEmail" name="email" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                   

                    <!-- New Password Change Section -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h3 class="text-lg font-semibold mb-4">Change Password</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="currentPassword">Current Password</label>
                                <input type="password" id="currentPassword" name="currentPassword"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <p id="currentPasswordError" class="mt-1 text-red-500 text-sm hidden"></p>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="newPassword"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <p id="newPasswordError" class="mt-1 text-red-500 text-sm hidden"></p>
                                <ul class="mt-2 text-sm text-gray-600 space-y-1">
                                    <li id="lengthCheck" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        At least 8 characters long
                                    </li>
                                    <li id="numberCheck" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Contains at least one number
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmNewPassword">Confirm New Password</label>
                                <input type="password" id="confirmNewPassword" name="confirmNewPassword"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <p id="confirmPasswordError" class="mt-1 text-red-500 text-sm hidden"></p>
                            </div>
                        </div>
                    </div>
                   
                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                            Save Changes
                        </button>
                        <button type="button" onclick="hideEditProfile()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition duration-300">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enquiry Responses Modal -->
    <div id="enquiryResponsesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 m-4 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <!-- Header with back button -->
            <div class="flex items-center justify-between mb-6 border-b pb-4">
                <div class="flex items-center space-x-4">
                    <button onclick="closeEnquiryResponses()" class="btn-3d flex items-center text-gray-700 hover:text-gray-900 transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">My Enquiries & Responses</h2>
                </div>
            </div>

            <!-- Content area -->
            <div id="enquiryResponsesList" class="space-y-4">
                <!-- Enquiries will be loaded here -->
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['booking_success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Booking Successful!',
                    text: '<?php echo $_SESSION['booking_success']; ?>',
                    confirmButtonColor: '#10B981'
                });
            });
        </script>
        <?php unset($_SESSION['booking_success']); ?>
    <?php endif; ?>

    <script>
        function openRegisterModal() {
            document.getElementById('registerModal').classList.remove('hidden');
            document.getElementById('registerModal').classList.add('flex');
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
            document.getElementById('registerModal').classList.remove('flex');
        }

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Add your registration logic here
            alert('Registration submitted!');
            closeRegisterModal();
        });

        function openProfileModal() {
            document.getElementById('profileModal').classList.remove('hidden');
            document.getElementById('profileModal').classList.add('flex');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
            document.getElementById('profileModal').classList.remove('flex');
        }

        // Logout form handler
        document.getElementById('logoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('logout.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = 'login.php';
                } else {
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback: redirect to login page even if there's an error
                window.location.href = 'login.php';
            });
        });

        // Replace the existing dropdown JavaScript with this updated version
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');

            if (userMenuButton && userDropdown) {
                // Toggle dropdown on button click
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.add('hidden');
                    }
                });

                // Close dropdown when pressing Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userDropdown.classList.add('hidden');
                    }
                });
            }
        });

        function showEditProfile() {
            document.getElementById('profileInfo').classList.add('hidden');
            document.getElementById('editProfileForm').classList.remove('hidden');
        }

        function hideEditProfile() {
            document.getElementById('profileInfo').classList.remove('hidden');
            document.getElementById('editProfileForm').classList.add('hidden');
        }

        // Add event listener for form submission
        document.getElementById('updateProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    // Refresh the page to show updated information
                    window.location.reload();
                } else {
                    alert('Error updating profile: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the profile');
            });
        });

        // Function to update the notification count
        function updateNotificationCount(count) {
            const notificationBadge = document.querySelector('.notification-badge');
            if (count > 0) {
                notificationBadge.textContent = count;
                notificationBadge.classList.remove('hidden');
            } else {
                notificationBadge.classList.add('hidden');
            }
        }

        // Function to fetch and display booking details in the notification dropdown
        function fetchBookingDetails() {
            fetch('fetch_bookings.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Booking data:', data);
                const notificationContent = document.getElementById('notificationContent');
                if (data.bookings && data.bookings.length > 0) {
                    notificationContent.innerHTML = data.bookings.map(booking => `
                        <div class="p-2 border-b border-gray-200">
                            <p class="font-semibold">${booking.destination}</p>
                            <p class="text-sm text-gray-600">Date: ${booking.date}</p>
                            <p class="text-sm text-gray-600">Status: ${booking.status}</p>
                            ${booking.status === 'successful' ? '<p class="text-sm text-green-600">Booking successful!</p>' : ''}
                        </div>
                    `).join('');
                    // Update notification count for successful bookings
                    const successfulBookings = data.bookings.filter(booking => booking.status === 'successful').length;
                    updateNotificationCount(successfulBookings);
                } else {
                    notificationContent.innerHTML = '<p class="text-center text-gray-500">No new notifications</p>';
                    updateNotificationCount(0);
                }
            })
            .catch(error => {
                console.error('Error fetching bookings:', error);
            });
        }

        // Periodically check for new successful bookings
        setInterval(fetchBookingDetails, 60000); // Check every 60 seconds

        document.addEventListener('DOMContentLoaded', function() {
            const notificationButton = document.querySelector('.notification-button');
            const notificationDropdown = document.getElementById('notificationDropdown');

            if (notificationButton && notificationDropdown) {
                // Toggle dropdown on button click
                notificationButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('hidden');
                    fetchBookingDetails(); // Fetch booking details when dropdown is opened
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!notificationButton.contains(e.target) && !notificationDropdown.contains(e.target)) {
                        notificationDropdown.classList.add('hidden');
                    }
                });

                // Close dropdown when pressing Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        notificationDropdown.classList.add('hidden');
                    }
                });
            }
        });

        document.getElementById('enquiryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Disable submit button and show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

            fetch('process_enquiry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Thank you!',
                        text: 'Your enquiry has been submitted successfully. We will get back to you soon.',
                        confirmButtonColor: '#3B82F6'
                    });
                    // Reset form
                    this.reset();
                } else {
                    throw new Error(data.message || 'Something went wrong!');
                }
            })
            .catch(error => {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: error.message,
                    confirmButtonColor: '#EF4444'
                });
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });

        function showEnquiryResponses() {
            const modal = document.getElementById('enquiryResponsesModal');
            const responsesList = document.getElementById('enquiryResponsesList');
            
            // Show loading state
            responsesList.innerHTML = `
                <div class="flex justify-center items-center py-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-gray-600">Loading enquiries...</span>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Fetch enquiries and responses
            fetch('fetch_enquiry_responses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.enquiries.length === 0) {
                            responsesList.innerHTML = `
                                <div class="text-center py-8">
                                    <p class="text-gray-500">No enquiries found</p>
                                </div>
                            `;
                            return;
                        }

                        responsesList.innerHTML = data.enquiries.map(enquiry => `
                            <div class="bg-gray-50 rounded-lg p-4 ${!enquiry.is_read ? 'border-l-4 border-blue-500' : ''} hover:shadow-md transition-shadow duration-300">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold">${enquiry.subject}</h3>
                                    <span class="text-sm text-gray-500">${new Date(enquiry.created_at).toLocaleDateString()}</span>
                                </div>
                                <p class="text-gray-600 mb-2">${enquiry.message}</p>
                                ${enquiry.status === 'Responded' ? `
                                    <div class="bg-blue-50 p-3 rounded mt-2">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-semibold text-blue-800">Staff Response:</span>
                                            <span class="text-xs text-gray-500">${new Date(enquiry.responded_at).toLocaleString()}</span>
                                        </div>
                                        <p class="text-blue-900">${enquiry.response}</p>
                                    </div>
                                ` : `
                                    <div class="text-sm text-yellow-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Awaiting Response
                                    </div>
                                `}
                            </div>
                        `).join('');
                    } else {
                        responsesList.innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-red-500">Error loading enquiries</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    responsesList.innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-red-500">Error loading enquiries</p>
                        </div>
                    `;
                });
        }

        function closeEnquiryResponses() {
            const modal = document.getElementById('enquiryResponsesModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEnquiryResponses();
            }
        });
    </script>
</body>
</html>
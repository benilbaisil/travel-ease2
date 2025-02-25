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
    </style>
</head>
<body class="font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">TravelEase</div>
                <div class="hidden md:flex space-x-8">
                    <a href="#" class="text-gray-700 hover:text-blue-600">Home</a>
                    <a href="userpackages.php" class="text-gray-700 hover:text-blue-600">Packages</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600">Contact</a>
                </div>
                <div class="flex items-center">
                    <?php if(isset($_SESSION['name'])): ?>
                        <div class="relative group">
                            <button id="userMenuButton" class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
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
    <div class="hero h-screen flex items-center home-container">
        <div class="overlay"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <h1 class="text-5xl font-bold text-white mb-6">Discover Your Next Adventure</h1>   
        </div>
    </div>

    <!-- Popular Destinations -->
    <div class="py-16 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Popular Destinations</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Destination Cards -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <img src="/travel%20ease/img/kovalam-beach-3.jpg" alt="Kerala - Kovalam Beach" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Kerala</h3>
                        <p class="text-gray-600 mb-4">Experience gods own country</p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold">From ₹7000</span>
                            <div class="space-x-2">
                                <a href="booking.php?destination=kerala" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Book Now</a>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <img src="/travel%20ease/img/cubbon-park-bengaluru%20ka.jpg" alt="Karnataka - Cubbon Park" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Karnataka</h3>
                        <p class="text-gray-600 mb-4">explore the nature</p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold">From ₹5000</span>
                            <div class="space-x-2">
                                <a href="booking.php?destination=karnataka" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Book Now</a>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <img src="/travel%20ease/img/jaipur%20ka.jpg" alt="Rajasthan - Jaipur" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Rajasthan</h3>
                        <p class="text-gray-600 mb-4"> explore the royal city</p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold">From ₹6500</span>
                            <div class="space-x-2">
                                <a href="booking.php?destination=rajasthan" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Book Now</a>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                            </div>
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
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h4 class="text-xl font-semibold mb-2">Best Price Guarantee</h4>
                            <p class="text-gray-600">We ensure the most competitive prices for all our travel packages.</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <h4 class="text-xl font-semibold mb-2">Secure Booking</h4>
                            <p class="text-gray-600">Your payments and personal information are always protected.</p>
                        </div>
                    </div>
                    <div class="space-y-4 mt-8">
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h4 class="text-xl font-semibold mb-2">24/7 Support</h4>
                            <p class="text-gray-600">Our travel experts are always here to help you.</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
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
                <!-- Phone -->
                <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                    <div class="flex justify-center mb-4">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Phone</h3>
                    <p class="text-gray-600">+1 234 567 8900</p>
                </div>

                <!-- Email -->
                <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                    <div class="flex justify-center mb-4">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Email</h3>
                    <p class="text-gray-600">info@travelease.com</p>
                </div>

                <!-- Address -->
                <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                    <div class="flex justify-center mb-4">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Address</h3>
                    <p class="text-gray-600">123 Travel Street</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
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
    <div id="registerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
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
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Full Name</label>
                    <input type="text" id="name" name="name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                    <input type="email" id="email" name="email" required
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

        document.addEventListener('DOMContentLoaded', function() {
            const currentPassword = document.getElementById('currentPassword');
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmNewPassword');
            
            const lengthCheck = document.getElementById('lengthCheck');
            const numberCheck = document.getElementById('numberCheck');
            
            function updatePasswordStrength(password) {
                // Check length
                if(password.length >= 8) {
                    lengthCheck.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
                    lengthCheck.classList.add('text-green-600');
                } else {
                    lengthCheck.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
                    lengthCheck.classList.remove('text-green-600');
                }
                
                // Check for numbers
                if(/\d/.test(password)) {
                    numberCheck.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
                    numberCheck.classList.add('text-green-600');
                } else {
                    numberCheck.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
                    numberCheck.classList.remove('text-green-600');
                }
            }
            
            newPassword.addEventListener('input', function() {
                updatePasswordStrength(this.value);
                
                // Check if passwords match when typing new password
                if(confirmPassword.value) {
                    if(this.value !== confirmPassword.value) {
                        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                        document.getElementById('confirmPasswordError').classList.remove('hidden');
                    } else {
                        document.getElementById('confirmPasswordError').classList.add('hidden');
                    }
                }
            });
            
            confirmPassword.addEventListener('input', function() {
                if(this.value !== newPassword.value) {
                    document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                    document.getElementById('confirmPasswordError').classList.remove('hidden');
                } else {
                    document.getElementById('confirmPasswordError').classList.add('hidden');
                }
            });
            
            // Add validation before form submission
            document.getElementById('updateProfileForm').addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate current password
                if(!currentPassword.value) {
                    document.getElementById('currentPasswordError').textContent = 'Current password is required';
                    document.getElementById('currentPasswordError').classList.remove('hidden');
                    isValid = false;
                }
                
                // Validate new password
                if(newPassword.value) {
                    if(newPassword.value.length < 8 || !/\d/.test(newPassword.value)) {
                        document.getElementById('newPasswordError').textContent = 'Password must be at least 8 characters long and contain at least one number';
                        document.getElementById('newPasswordError').classList.remove('hidden');
                        isValid = false;
                    }
                }
                
                // Validate password match
                if(newPassword.value !== confirmPassword.value) {
                    document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                    document.getElementById('confirmPasswordError').classList.remove('hidden');
                    isValid = false;
                }
                
                if(!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
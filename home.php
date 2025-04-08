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

// Function to format timestamps in a human-readable way
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "Yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
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
    
    // Fetch unread notifications for the user
    $notification_sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bind_param("i", $user_id);
    $notification_stmt->execute();
    $notifications_result = $notification_stmt->get_result();
    $notifications = [];
    $unread_count = $notifications_result->num_rows;
    
    // Store notifications in array for display
    while ($notification = $notifications_result->fetch_assoc()) {
        $notifications[] = $notification;
    }
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
                    <a href="my_bookings.php" class="text-gray-700 hover:text-blue-600">My Bookings</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="#enquiry" class="text-gray-700 hover:text-blue-600">Contact</a>
                    
                </div>
                <div class="flex items-center">
                    <?php if(isset($_SESSION['name'])): ?>
                        <div class="relative group mr-4">
                            <!-- Notification Bell Icon -->
                            <button id="notificationButton" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <!-- Notification Badge - Add 'hidden' class when no notifications -->
                                <span id="notificationBadge" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full <?php echo ($unread_count == 0) ? 'hidden' : ''; ?>"><?php echo $unread_count; ?></span>
                            </button>
                            <!-- Notification Dropdown -->
                            <div id="notificationDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 hidden z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <?php if (isset($notifications) && count($notifications) > 0): ?>
                                        <?php foreach ($notifications as $notification): ?>
                                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out notification-item" data-id="<?php echo $notification['id']; ?>">
                                                <div class="flex items-start">
                                                    <div class="flex-shrink-0 bg-blue-500 rounded-full p-1">
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3 w-0 flex-1">
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                        <p class="text-xs text-gray-400 mt-1"><?php echo timeAgo($notification['created_at']); ?></p>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="px-4 py-6 text-center text-gray-500">
                                            <p>No new notifications</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="px-4 py-2 border-t border-gray-100 text-center">
                                    <a href="notifications.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all notifications</a>
                                </div>
                            </div>
                        </div>
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
                                <a href="my_enquiries.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    My Enquiries
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

    <!-- Replace the Contact Us section with this Enquiry section -->
    <section id="enquiry" class="py-16 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-gray-800 mb-4">Make an Enquiry</h2>
                    <p class="text-gray-600">Have questions about our travel packages? Send us an enquiry and we'll get back to you soon.</p>
                </div>

                <form id="enquiryForm" class="bg-white rounded-2xl shadow-xl p-8">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Your Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Your Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-6">
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300"
                            placeholder="What would you like to know about?">
                    </div>

                    <div class="mb-6">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="4" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300"
                            placeholder="Please provide details about your enquiry..."></textarea>
                    </div>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-6 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition duration-300 transform hover:-translate-y-1 hover:shadow-lg">
                            Submit Enquiry
                        </button>
                    <?php else: ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-600 mb-2">Please log in to submit an enquiry</p>
                            <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">Login here</a>
                        </div>
                    <?php endif; ?>
                </form>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="mt-8 text-center">
                        <button onclick="showEnquiryResponses()" 
                            class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                            <span>View My Previous Enquiries</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
                                    <li id="uppercaseCheck" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Contains at least one uppercase letter
                                    </li>
                                    <li id="lowercaseCheck" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Contains at least one lowercase letter
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

    <!-- Success Popup Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2" id="successModalTitle">Success!</h3>
                <div class="mt-2">
                    <p class="text-gray-600" id="successModalMessage">Your enquiry has been submitted successfully.</p>
                </div>
                <div class="mt-6">
                    <button type="button" onclick="closeSuccessModal()" 
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-300">
                        OK
                    </button>
                </div>
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

        // Add notification dropdown toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const notificationButton = document.getElementById('notificationButton');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationButton && notificationDropdown) {
                // Toggle dropdown on button click
                notificationButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('hidden');
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
            
            // Existing notification item click code
            const notificationItems = document.querySelectorAll('.notification-item');
            
            notificationItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const notificationId = this.getAttribute('data-id');
                    
                    // Send AJAX request to mark notification as read
                    fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'notification_id=' + notificationId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the notification item or update UI as needed
                            this.classList.add('bg-gray-50');
                            
                            // Update the badge count
                            const badge = document.getElementById('notificationBadge');
                            let count = parseInt(badge.textContent) - 1;
                            
                            if (count <= 0) {
                                badge.classList.add('hidden');
                            } else {
                                badge.textContent = count;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
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
            
            // Password validation
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;
            
            // Reset error messages
            document.getElementById('currentPasswordError').classList.add('hidden');
            document.getElementById('newPasswordError').classList.add('hidden');
            document.getElementById('confirmPasswordError').classList.add('hidden');
            
            // Validate current password if provided
            if (currentPassword && currentPassword.length < 8) {
                document.getElementById('currentPasswordError').textContent = 'Current password must be at least 8 characters long';
                document.getElementById('currentPasswordError').classList.remove('hidden');
                return;
            }
            
            // Validate new password if provided
            if (newPassword) {
                if (newPassword.length < 8) {
                    document.getElementById('newPasswordError').textContent = 'New password must be at least 8 characters long';
                    document.getElementById('newPasswordError').classList.remove('hidden');
                    return;
                }
                
                if (!/\d/.test(newPassword)) {
                    document.getElementById('newPasswordError').textContent = 'New password must contain at least one number';
                    document.getElementById('newPasswordError').classList.remove('hidden');
                    return;
                }
                
                if (!/[A-Z]/.test(newPassword)) {
                    document.getElementById('newPasswordError').textContent = 'New password must contain at least one uppercase letter';
                    document.getElementById('newPasswordError').classList.remove('hidden');
                    return;
                }
                
                if (!/[a-z]/.test(newPassword)) {
                    document.getElementById('newPasswordError').textContent = 'New password must contain at least one lowercase letter';
                    document.getElementById('newPasswordError').classList.remove('hidden');
                    return;
                }
                
                if (newPassword !== confirmNewPassword) {
                    document.getElementById('confirmPasswordError').textContent = 'New passwords do not match';
                    document.getElementById('confirmPasswordError').classList.remove('hidden');
                    return;
                }
            }
            
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

        // Add real-time password validation
        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const lengthCheck = document.getElementById('lengthCheck');
            const numberCheck = document.getElementById('numberCheck');
            const uppercaseCheck = document.getElementById('uppercaseCheck');
            const lowercaseCheck = document.getElementById('lowercaseCheck');
            
            // Update check icons
            if (password.length >= 8) {
                lengthCheck.querySelector('svg').setAttribute('stroke', 'green');
                lengthCheck.querySelector('path').setAttribute('d', 'M5 13l4 4L19 7');
            } else {
                lengthCheck.querySelector('svg').setAttribute('stroke', 'currentColor');
                lengthCheck.querySelector('path').setAttribute('d', 'M6 18L18 6M6 6l12 12');
            }
            
            if (/\d/.test(password)) {
                numberCheck.querySelector('svg').setAttribute('stroke', 'green');
                numberCheck.querySelector('path').setAttribute('d', 'M5 13l4 4L19 7');
            } else {
                numberCheck.querySelector('svg').setAttribute('stroke', 'currentColor');
                numberCheck.querySelector('path').setAttribute('d', 'M6 18L18 6M6 6l12 12');
            }
            
            if (/[A-Z]/.test(password)) {
                uppercaseCheck.querySelector('svg').setAttribute('stroke', 'green');
                uppercaseCheck.querySelector('path').setAttribute('d', 'M5 13l4 4L19 7');
            } else {
                uppercaseCheck.querySelector('svg').setAttribute('stroke', 'currentColor');
                uppercaseCheck.querySelector('path').setAttribute('d', 'M6 18L18 6M6 6l12 12');
            }
            
            if (/[a-z]/.test(password)) {
                lowercaseCheck.querySelector('svg').setAttribute('stroke', 'green');
                lowercaseCheck.querySelector('path').setAttribute('d', 'M5 13l4 4L19 7');
            } else {
                lowercaseCheck.querySelector('svg').setAttribute('stroke', 'currentColor');
                lowercaseCheck.querySelector('path').setAttribute('d', 'M6 18L18 6M6 6l12 12');
            }
        });

        function showEnquiryResponses() {
            window.location.href = 'my_enquiries.php';
        }

        function showSuccessModal(title, message) {
            // Set modal content
            document.getElementById('successModalTitle').textContent = title || 'Success!';
            document.getElementById('successModalMessage').textContent = message || 'Operation completed successfully.';
            
            // Show modal
            const modal = document.getElementById('successModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Add event listener for enquiry form submission
        document.addEventListener('DOMContentLoaded', function() {
            const enquiryForm = document.getElementById('enquiryForm');
            if (enquiryForm) {
                enquiryForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('submit_enquiry.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Replace alert with custom modal
                            showSuccessModal('Enquiry Submitted', 'Your enquiry has been submitted successfully. We will get back to you soon.');
                            enquiryForm.reset();
                        } else {
                            alert('Error submitting enquiry: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while submitting your enquiry');
                    });
                });
            }
        });
    </script>
</body>
</html>
<?php
session_start();
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
                <div class="text-2xl font-bold text-#b5f4b5-600">TravelEase</div>
                <div class="hidden md:flex space-x-8">
                    <a href="#" class="text-gray-700 hover:text-blue-600">Home</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600">Packages</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600">Contact</a>
                </div>
                <div class="flex items-center">
                    <?php if(isset($_SESSION['name'])): ?>
                        <span class="text-gray-800 mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        <a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a>
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
                            <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
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
                            <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
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
                            <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View Details</button>
                        </div>
                    </div>
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
                        <li><a href="#" class="hover:text-blue-400">About Us</a></li>
                        <li><a href="#" class="hover:text-blue-400">Contact</a></li>
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
    </script>
</body>
</html>
<!-- Navigation -->
<nav class="nav-3d bg-white shadow-lg fixed w-full top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="text-2xl font-bold text-green-600">TravelEase</div>
            <div class="hidden md:flex space-x-8">
                <a href="home.php" class="nav-link-3d text-gray-700 hover:text-blue-600">
                    <i class="fas fa-home mr-1"></i>Home
                </a>
                <a href="userpackages.php" class="nav-link-3d text-gray-700 hover:text-blue-600">
                    <i class="fas fa-box mr-1"></i>Packages
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="my_bookings.php" class="nav-link-3d text-gray-700 hover:text-blue-600 active">
                        <i class="fas fa-calendar-check mr-1"></i>My Bookings
                    </a>
                <?php endif; ?>
                <a href="#about" class="nav-link-3d text-gray-700 hover:text-blue-600">
                    <i class="fas fa-info-circle mr-1"></i>About
                </a>
                <a href="#contact" class="nav-link-3d text-gray-700 hover:text-blue-600">
                    <i class="fas fa-envelope mr-1"></i>Contact
                </a>
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
                </div>
                
                <!-- User Menu -->
                <?php if(isset($_SESSION['name'])): ?>
                    <div class="relative group">
                        <button id="userMenuButton" class="btn-3d user-menu-button flex items-center space-x-2">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                </div>
                                <span class="ml-2 text-white"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
                            <a href="my_bookings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-calendar-check mr-2"></i>My Bookings
                            </a>
                            <button onclick="showEnquiryResponses()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-envelope mr-2"></i>My Enquiries
                            </button>
                            <button onclick="openProfileModal()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>My Profile
                            </button>
                            <div class="border-t border-gray-100"></div>
                            <form action="logout.php" method="POST" id="logoutForm">
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
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

<script>
    // Function to fetch notification count
    function fetchNotificationCount() {
        fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const notificationBadge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    notificationBadge.textContent = data.count;
                    notificationBadge.classList.remove('hidden');
                } else {
                    notificationBadge.classList.add('hidden');
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
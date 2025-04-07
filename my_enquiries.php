<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's enquiries
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

$sql = "SELECT * FROM enquiries WHERE email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enquiries - TravelEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">TravelEase</div>
                <div class="hidden md:flex space-x-8">
                    <a href="home.php" class="text-gray-700 hover:text-blue-600">Home</a>
                    <a href="userpackages.php" class="text-gray-700 hover:text-blue-600">Packages</a>
                    <a href="my_bookings.php" class="text-gray-700 hover:text-blue-600">My Bookings</a>
                    <a href="home.php#about" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="home.php#enquiry" class="text-gray-700 hover:text-blue-600">Contact</a>
                </div>
                <div class="flex items-center">
                    <?php if(isset($_SESSION['name'])): ?>
                        <div class="relative group">
                            <button id="userMenuButton" class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                </div>
                                <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">My Enquiries</h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="grid gap-6">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($row['subject']); ?></h3>
                                <p class="text-gray-500 text-sm">
                                    Submitted on <?php echo date('F j, Y, g:i a', strtotime($row['created_at'])); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?php 
                                echo $row['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                    ($row['status'] === 'Responded' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); 
                            ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-600 mb-2">Your Message:</h4>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                        </div>

                        <?php if ($row['response']): ?>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-800 mb-2">Response:</h4>
                                <p class="text-blue-900"><?php echo nl2br(htmlspecialchars($row['response'])); ?></p>
                                <p class="text-sm text-blue-700 mt-2">
                                    Responded on <?php echo date('F j, Y \a\t g:i a', strtotime($row['responded_at'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-inbox text-4xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-700 mb-2">No Enquiries Yet</h3>
                <p class="text-gray-500">You haven't submitted any enquiries yet.</p>
                <a href="home.php#enquiry" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                    Submit an Enquiry
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html> 
<?php
session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['is_staff']) || $_SESSION['is_staff'] != 1) {
    header("Location: login.php");
    exit();
}

// Database connection (you'll need to add your database credentials)
$conn = new mysqli("localhost", "root", "", "travel_ease");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all bookings with user details
$query = "SELECT 
            bookings.*, 
            users.name as user_name, 
            users.email as user_email
          FROM bookings 
          JOIN users ON bookings.user_id = users.id 
          ORDER BY bookings.booking_date DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - TravelEase Staff</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-table th, .booking-table td {
            padding: 1rem;
        }
        .status-pending { background-color: #FEF3C7; }
        .status-confirmed { background-color: #D1FAE5; }
        .status-cancelled { background-color: #FEE2E2; }
        .booking-card:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">TravelEase Staff Portal</div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4">
            <h1 class="text-3xl font-bold text-gray-800">Booking Management</h1>
        </div>
    </div>

    <!-- Booking Statistics -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 booking-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Bookings</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $result->num_rows; ?></p>
                    </div>
                </div>
            </div>
            <!-- Add more statistics cards as needed -->
        </div>

        <!-- Bookings Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">Recent Bookings</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full booking-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left text-gray-600">Booking ID</th>
                            <th class="text-left text-gray-600">Customer</th>
                            <th class="text-left text-gray-600">Package</th>
                            <th class="text-left text-gray-600">Travel Date</th>
                            <th class="text-left text-gray-600">Guests</th>
                            <th class="text-left text-gray-600">Status</th>
                            <th class="text-left text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while($booking = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="text-gray-800 font-medium">
                                #<?php echo htmlspecialchars($booking['id']); ?>
                            </td>
                            <td>
                                <div class="text-gray-800"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                <div class="text-gray-600 text-sm"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                            </td>
                            <td class="text-gray-800">
                                <?php echo htmlspecialchars($booking['package_name']); ?>
                            </td>
                            <td class="text-gray-800">
                                <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?>
                            </td>
                            <td class="text-gray-800">
                                <?php echo htmlspecialchars($booking['guests']); ?>
                            </td>
                            <td>
                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                    <?php 
                                    echo match($booking['status']) {
                                        'pending' => 'status-pending text-yellow-800',
                                        'confirmed' => 'status-confirmed text-green-800',
                                        'cancelled' => 'status-cancelled text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateBookingStatus(<?php echo $booking['id']; ?>)" 
                                            class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" 
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="p-6" id="bookingDetailsContent">
                <!-- Modal content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function viewBookingDetails(bookingId) {
            // Add AJAX call to fetch booking details
            const modal = document.getElementById('bookingModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function updateBookingStatus(bookingId) {
            // Add AJAX call to update booking status
            if(confirm('Confirm this booking?')) {
                // Add your update logic here
            }
        }

        function cancelBooking(bookingId) {
            // Add AJAX call to cancel booking
            if(confirm('Are you sure you want to cancel this booking?')) {
                // Add your cancellation logic here
            }
        }

        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
            }
        });
    </script>
</body>
</html> 
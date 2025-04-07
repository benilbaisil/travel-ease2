<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Fetch bookings with user names and package names
$sql = "SELECT bookings.*, users.name AS user_name, travel_packages.package_name 
        FROM bookings 
        JOIN users ON bookings.user_id = users.user_id
        JOIN travel_packages ON bookings.package_id = travel_packages.id 
        ORDER BY bookings.id DESC"; // Added ORDER BY clause
$result = $conn->query($sql);
$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Modify the generateCSV function to handle a single booking
function generateCSV($booking) {
    $filename = "booking_" . $booking['id'] . "_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Booking ID', 'User Name', 'Package Name', 'Travel Date', 'Number of Guests', 'Phone', 'Total Amount', 'Status'));

    fputcsv($output, array(
        $booking['id'],
        $booking['user_name'],
        $booking['package_name'],
        "\t" . ($booking['travel_date'] ?? 'N/A'),
        $booking['num_guests'] ?? 'N/A',
        "\t" . ($booking['phone'] ?? 'N/A'),
        $booking['total_amount'] ?? 'N/A',
        $booking['booking_status'] ?? 'N/A'
    ));
    fclose($output);
    exit();
}

// Function to generate CSV for all bookings
function generateAllBookingsCSV($bookings) {
    $filename = "all_bookings_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Booking ID', 'User Name', 'Package Name', 'Travel Date', 'Number of Guests', 'Phone', 'Total Amount', 'Status'));

    foreach ($bookings as $booking) {
        fputcsv($output, array(
            $booking['id'],
            $booking['user_name'],
            $booking['package_name'],
            "\t" . ($booking['travel_date'] ?? 'N/A'),
            $booking['num_guests'] ?? 'N/A',
            "\t" . ($booking['phone'] ?? 'N/A'),
            $booking['total_amount'] ?? 'N/A',
            $booking['booking_status'] ?? 'N/A'
        ));
    }
    fclose($output);
    exit();
}

// Check if download request is made for a specific booking
if (isset($_GET['download']) && $_GET['download'] == 'csv' && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    foreach ($bookings as $booking) {
        if ($booking['id'] == $booking_id) {
            generateCSV($booking);
        }
    }
}

// Check if download request is made for all bookings
if (isset($_GET['download']) && $_GET['download'] == 'csv' && !isset($_GET['booking_id'])) {
    generateAllBookingsCSV($bookings);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f3f4f6;
        min-height: 100vh;
    }

    /* Dashboard Layout */
    .dashboard-layout {
        display: flex;
        min-height: 100vh;
        position: relative;
    }

    /* Enhanced Sidebar Styles */
    .sidebar {
        width: 280px;
        background: linear-gradient(165deg, #2D3250 0%, #1a237e 100%);
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.2);
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        overflow-y: auto;
        z-index: 1000;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header {
        padding: 2rem;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 1rem;
    }

    .sidebar-header h2 {
        color: white;
        font-size: 1.8rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .sidebar-header h2::before {
        content: '';
        width: 8px;
        height: 24px;
        background: linear-gradient(to bottom, #00f2fe, #4facfe);
        border-radius: 4px;
    }

    .nav-links {
        padding: 0.5rem;
        list-style: none;
        margin: 0;
    }

    .nav-links li {
        margin: 0.5rem 0;
    }

    .nav-links li a, .logout-btn {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        border-radius: 12px;
        margin: 0.3rem 0.8rem;
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-links li a:hover, .logout-btn:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .nav-links li a.active {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .nav-links li a i, .logout-btn i {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1.2rem;
        background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        transition: transform 0.3s ease;
    }

    .nav-links li a:hover i, .logout-btn:hover i {
        transform: scale(1.2);
    }

    .logout-btn {
        margin-top: 2rem;
        color: #ff6b6b;
        border: 1px solid rgba(255, 107, 107, 0.3);
        width: calc(100% - 1.6rem);
        text-align: left;
        cursor: pointer;
        font-size: 1rem;
        background: none;
    }

    .logout-btn:hover {
        background: rgba(255, 107, 107, 0.1);
        border-color: rgba(255, 107, 107, 0.5);
    }

    .logout-btn i {
        background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        margin-left: 280px; /* Same as sidebar width */
        padding: 2rem;
        min-height: 100vh;
        width: calc(100% - 280px);
        position: relative;
        background-color: #f3f4f6;
    }

    /* Hide the old navbar */
    .navbar {
        display: none;
    }

    /* Adjust container width */
    .container {
        max-width: 100%;
        padding: 0 1rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-layout {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 0.5rem;
        }

        .nav-links li {
            width: calc(50% - 1rem);
            margin: 0.5rem;
        }

        .nav-links li a, .logout-btn {
            margin: 0;
            padding: 0.8rem;
            justify-content: center;
        }

        .nav-links li a i, .logout-btn i {
            margin-right: 8px;
        }

        .main-content {
            margin-left: 0;
            width: 100%;
        }
    }

    /* Footer Adjustment */
    .footer {
        margin-left: 280px;
        width: calc(100% - 280px);
    }

    @media (max-width: 768px) {
        .footer {
            margin-left: 0;
            width: 100%;
        }
    }

    /* Enhanced Card Styles */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: white;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-title {
        color: #2D3250;
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 0.75rem;
    }

    .card-text {
        margin-bottom: 0.75rem;
        color: #555;
        font-size: 0.95rem;
    }

    .card-text i {
        color: #4facfe;
        width: 20px;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .status-badge.confirmed {
        background-color: #e3fcef;
        color: #0d9488;
    }

    .status-badge.pending {
        background-color: #fef3c7;
        color: #d97706;
    }

    .status-badge.cancelled {
        background-color: #fee2e2;
        color: #dc2626;
    }

    /* Header Styles */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem 1.5rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .header h1 {
        font-size: 1.75rem;
        color: #2D3250;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .header h1 i {
        color: #4facfe;
        margin-right: 0.75rem;
    }

    /* Download Button Styles */
    .btn-download {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        font-weight: 500;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .btn-download:hover {
        background: linear-gradient(135deg, #4facfe 20%, #00f2fe 80%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
        color: white;
    }

    .btn-card {
        background: #f8fafc;
        color: #2D3250;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        width: 100%;
        text-align: center;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }

    .btn-card:hover {
        background: #f1f5f9;
        color: #4facfe;
        text-decoration: none;
    }

    /* Footer Styles */
    .footer {
        text-align: center;
        padding: 1.5rem;
        color: #64748b;
        background: white;
        border-radius: 15px;
        margin-top: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    /* Container Spacing */
    .container {
        padding: 1.5rem;
    }

    .row {
        margin: 0 -0.75rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .btn-download {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>
<body>
    <!-- First, wrap everything in a dashboard layout container -->
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Staff Panel</h2>
            </div>
            <ul class="nav-links">
                <li>
                    <a href="staff_dashboard.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_bookings.php" class="active">
                        <i class="fas fa-calendar-check"></i>
                        Bookings
                    </a>
                </li>
                <li>
                    <a href="packages_view.php">
                        <i class="fas fa-box"></i>
                        Packages
                    </a>
                </li>
                <li>
                    <a href="enquiry.php">
                        <i class="fas fa-question-circle"></i>
                        Enquiries
                    </a>
                </li>
                <li>
                    <button onclick="window.location.href='logout.php'" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">
                        <i class="fas fa-plane-departure mr-2"></i>TravelEase Admin
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ml-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="staff_dashboard.php">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="manage_bookings.php">
                                    <i class="fas fa-calendar-check mr-2"></i>Bookings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="packages_view.php">
                                    <i class="fas fa-box-open mr-2"></i>Packages
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="header">
                <h1><i class="fas fa-calendar-alt mr-3"></i>Manage Bookings</h1>
                <a href="?download=csv" class="btn btn-download">
                    <i class="fas fa-download mr-2"></i>Download All Bookings
                </a>
            </div>

            <div class="container">
                <div class="row">
                    <?php foreach ($bookings as $booking): ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-ticket-alt mr-2"></i>
                                    Booking #<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?>
                                </h5>
                                <p class="card-text">
                                    <i class="fas fa-user mr-2"></i>
                                    <strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <strong>Package:</strong> <?php echo htmlspecialchars($booking['package_name']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-calendar mr-2"></i>
                                    <strong>Travel Date:</strong> <?php echo htmlspecialchars($booking['travel_date'] ?? 'N/A'); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-users mr-2"></i>
                                    <strong>Guests:</strong> <?php echo htmlspecialchars($booking['num_guests'] ?? 'N/A'); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-phone mr-2"></i>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-rupee-sign mr-2"></i>
                                    <strong>Amount:</strong> ₹<?php echo number_format($booking['total_amount'] ?? 0, 2); ?>
                                </p>
                                <p class="card-text">
                                    <span class="status-badge <?php echo strtolower($booking['booking_status'] ?? 'pending'); ?>">
                                        <i class="fas fa-circle mr-1"></i>
                                        <?php echo htmlspecialchars($booking['booking_status'] ?? 'Pending'); ?>
                                    </span>
                                </p>
                                <a href="?download=csv&booking_id=<?php echo $booking['id']; ?>" class="btn btn-card">
                                    <i class="fas fa-download mr-2"></i>Download Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> TravelEase. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    function editBooking(bookingId) {
        // Removed edit functionality
    }

    function deleteBooking(bookingId) {
        // Removed delete functionality
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Add click event for menu items
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
    </script>
</body>
</html> 
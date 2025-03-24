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
        JOIN travel_packages ON bookings.package_id = travel_packages.id"; // Adjust the query as needed
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
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #3498db;
        --accent-color: #e74c3c;
        --background-color: #f8f9fa;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
        color: var(--primary-color);
        min-height: 100vh;
        padding-bottom: 60px;
    }

    .navbar {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        padding: 1rem 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: white !important;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500;
        margin: 0 10px;
        padding: 8px 16px;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    .header {
        text-align: center;
        padding: 2rem 0;
        background: linear-gradient(135deg, rgba(44, 62, 80, 0.1) 0%, rgba(52, 152, 219, 0.1) 100%);
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
    }

    .header h1 {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 1.5rem;
        font-size: 2.5rem;
    }

    .btn-download {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 25px;
        border: none;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        transition: all 0.3s ease;
    }

    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
        color: white;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        background: white;
        margin-bottom: 2rem;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-title {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.75rem;
    }

    .card-text {
        margin-bottom: 0.75rem;
        color: #6c757d;
    }

    .card-text strong {
        color: var(--primary-color);
        font-weight: 600;
    }

    .btn-card {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        border: none;
        transition: all 0.3s ease;
        margin-top: 1rem;
        width: 100%;
    }

    .btn-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        color: white;
    }

    .footer {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        text-align: center;
        padding: 1rem 0;
        position: fixed;
        bottom: 0;
        width: 100%;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-block;
    }

    .status-confirmed {
        background-color: #2ecc71;
        color: white;
    }

    .status-pending {
        background-color: #f1c40f;
        color: white;
    }

    /* Animation */
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

    .card {
        animation: fadeInUp 0.5s ease-out;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <a class="navbar-brand" href="#">
            <i class="fas fa-plane-departure mr-2"></i>TravelEase
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
    </script>
</body>
</html> 
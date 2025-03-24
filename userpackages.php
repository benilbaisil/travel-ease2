<?php
session_start();
require_once 'config.php';

// Add error message display if redirected from booking.php
$errorMessage = '';
if (isset($_SESSION['booking_error'])) {
    $errorMessage = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']); // Clear the error message after displaying
}

// Add search functionality
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
}

// Modify SQL query to include search condition
$sql = "SELECT * FROM travel_packages WHERE package_name LIKE ? ORDER BY id";
$stmt = $conn->prepare($sql);
$searchTerm = '%' . $searchQuery . '%';
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Travel Packages - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --white: #ffffff;
            --highlight-color: #ff5722; /* New highlight color */
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f6f7f9 0%, #e9edf4 100%);
            transition: background 0.3s ease; /* Smooth transition for background */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .page-header h1 {
            color: var(--text-dark);
            font-size: 2.8rem;
            margin: 0;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .package-card {
            background: var(--white);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--highlight-color); /* Highlight border on hover */
        }

        .package-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.5s ease, filter 0.5s ease; /* Add filter transition */
        }

        .package-card:hover .package-image {
            transform: scale(1.1);
            filter: brightness(0.9); /* Darken image slightly on hover */
        }

        .package-content {
            padding: 2rem;
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .package-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0 0 1rem 0;
            line-height: 1.3;
        }

        .package-destination {
            color: var(--text-light);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .package-destination i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .package-details {
            display: flex;
            justify-content: space-around;
            margin: 1.5rem 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            background: linear-gradient(to right, rgba(37, 99, 235, 0.1), rgba(59, 130, 246, 0.1));
            border-radius: 0.75rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0 1rem;
        }

        .detail-label {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .package-description {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.8;
            margin: 1.5rem 0;
            text-align: left;
            padding: 1rem;
            background: var(--bg-light);
            border-radius: 0.75rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .book-btn {
            margin-top: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            padding: 1.2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
            background: var(--highlight-color); /* Change background color on hover */
        }

        .book-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }

        .book-btn:hover::before {
            left: 100%;
        }

        .error-message {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }

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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .packages-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                padding: 0.5rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .package-content {
                padding: 1.5rem;
            }

            .package-description {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }

        /* Navigation Bar Styles */
        nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 2rem;
            padding: 0 2rem;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        nav li {
            margin: 0 15px;
            position: relative;
        }

        nav a {
            color: #4b5563; /* Text color similar to home.php */
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            padding: 10px 0;
            transition: color 0.3s ease;
            position: relative;
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #202020; /* Primary color similar to home.php */
            transition: width 0.3s ease;
        }

        nav a:hover {
            color: #202020; /* Primary color on hover */
        }

        nav a:hover::after {
            width: 100%;
        }

        .active-nav {
            color: #202020; /* Active link color */
        }

        .active-nav::after {
            width: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            nav ul {
                height: auto;
                flex-direction: column;
                padding: 1rem 0;
            }

            nav li {
                margin: 10px 0;
            }
        }

        /* Add smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Add animation for package cards */
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

        /* Add staggered animation delay for cards */
        .package-card:nth-child(1) { animation-delay: 0.1s; }
        .package-card:nth-child(2) { animation-delay: 0.2s; }
        .package-card:nth-child(3) { animation-delay: 0.3s; }
        .package-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body class="font-sans">
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="text-2xl font-bold text-green-600">TravelEase</div>
                    <div class="hidden md:flex space-x-8">
                        <a href="home.php" class="text-gray-700 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active-nav' : ''; ?>">Home</a>
                        <a href="userpackages.php" class="text-gray-700 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'userpackages.php') ? 'active-nav' : ''; ?>">Packages</a>
                        <a href="my_bookings.php" class="text-gray-700 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'my_bookings.php') ? 'active-nav' : ''; ?>">My Bookings</a>
                        <a href="home.php#about" class="text-gray-700 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active-nav' : ''; ?>">About Us</a>
                        <a href="home.php#contact" class="text-gray-700 hover:text-blue-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active-nav' : ''; ?>">Contact</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Back Button -->
                        <button onclick="window.history.back();" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Back</button>
                    </div>
                </div>
            </div>
        </nav>
        <!-- End of Navigation Bar -->

        <!-- Search Form -->
        <form method="GET" action="userpackages.php" style="text-align: center; margin-bottom: 2rem;">
            <input type="text" name="search" placeholder="Search packages..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="padding: 0.5rem; width: 300px; border-radius: 0.5rem; border: 1px solid #ccc;">
            <button type="submit" style="padding: 0.5rem 1rem; background-color: var(--primary-color); color: var(--white); border: none; border-radius: 0.5rem;">Search</button>
        </form>
        <!-- End of Search Form -->

        <div class="page-header">
            <h1>Explore Our Travel Packages</h1>
        </div>
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="packages-grid">
            <?php
            // Reset the result pointer
            $result->data_seek(0);
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>
                <div class="package-card">
                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['package_name']); ?>" class="package-image">
                    <div class="package-content">
                        <h2 class="package-title"><?php echo htmlspecialchars($row['package_name']); ?></h2>
                        <div class="package-destination">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($row['destination']); ?>
                        </div>
                        
                        <div class="package-details">
                            <div class="detail-item">
                                <span class="detail-label">Duration</span>
                                <span class="detail-value"><?php echo htmlspecialchars($row['duration']); ?> Days</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Price</span>
                                <span class="detail-value">₹<?php echo number_format($row['price']); ?></span>
                            </div>
                        </div>

                        <p class="package-description">
                            <?php 
                            // Split the description by "Day" and format each day on a new line
                            $description = $row['description'];
                            $days = preg_split('/(Day\s*\d+:)/', $description, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                            
                            if (count($days) > 1) {
                                for ($i = 0; $i < count($days); $i += 2) {
                                    if (isset($days[$i + 1])) {
                                        echo htmlspecialchars(trim($days[$i]) . trim($days[$i + 1])) . "<br>";
                                    }
                                }
                            } else {
                                // If no "Day" format is found, display as is
                                echo htmlspecialchars(trim($description));
                            }
                            ?>
                        </p>
                        
                        <a href="booking.php?package_id=<?php echo $row['id']; ?>" class="book-btn">Book Now</a>
                    </div>
                </div>
            <?php
                }
            } else {
                echo "<p style='text-align: center; grid-column: 1/-1;'>No packages available at the moment.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>  
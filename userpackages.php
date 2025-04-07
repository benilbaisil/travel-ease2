l<?php
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

// Fetch packages based on search query or show all active packages
if (!empty($searchQuery)) {
    $searchParam = "%" . $searchQuery . "%";
    $sql = "SELECT * FROM travel_packages WHERE active = 1 AND (package_name LIKE ? OR destination LIKE ? OR description LIKE ?) ORDER BY id DESC";
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // If no search, fetch all active packages
    $sql = "SELECT * FROM travel_packages WHERE active = 1 ORDER BY id DESC";
    $result = $conn->query($sql);
}

$travel_packages = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $travel_packages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Packages - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f6f7f9 0%, #e9edf4 100%);
            min-height: 100vh;
        }

        /* Enhanced Header Container */
        .header-container {
            background: linear-gradient(135deg, #ffffff, #f3f4f6);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        /* Logo Styles */
        .logo-container {
            text-align: center;
            padding: 1rem 0;
            position: relative;
            z-index: 1;
        }

        .logo-text {
            font-size: 2.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #22c55e, #16a34a, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            position: relative;
            letter-spacing: -1px;
            animation: glow 3s ease-in-out infinite alternate;
        }

        .logo-text::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #22c55e, #16a34a, #059669, transparent);
            border-radius: 2px;
            animation: shimmer 2s infinite;
        }

        /* Navigation Menu */
        .nav-menu {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            padding: 1rem;
            margin: 1rem auto;
            max-width: 800px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .nav-link {
            color: #4b5563;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .nav-link:hover {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        .nav-link:hover i {
            transform: scale(1.2);
        }

        .nav-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* Search Container */
        .search-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 1rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-button {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* Page Title */
        .page-title {
            text-align: center;
            margin: 3rem 0;
            font-size: 2.5rem;
            color: #1f2937;
            font-weight: bold;
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            border-radius: 2px;
        }

        /* Animations */
        @keyframes glow {
            0% { text-shadow: 0 0 5px rgba(34, 197, 94, 0.2); }
            100% { text-shadow: 0 0 20px rgba(34, 197, 94, 0.4); }
        }

        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                padding: 1rem 0;
            }

            .logo-text {
                font-size: 2.2rem;
            }

            .nav-menu {
                flex-direction: column;
                padding: 0.5rem;
                gap: 0.5rem;
                margin: 1rem;
            }

            .nav-link {
                width: 100%;
                justify-content: center;
            }

            .search-container {
                flex-direction: column;
                margin: 1rem;
            }

            .search-button {
                width: 100%;
            }
        }

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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 2rem;
            padding: 0.5rem 2rem;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }

        .logo-container {
            position: relative;
            padding: 0.5rem 0;
        }

        .logo-underline {
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #22c55e, #16a34a, #059669, transparent);
            border-radius: 2px;
            animation: shimmer 2s infinite;
        }

        .nav-links-container {
            background: rgba(243, 244, 246, 0.7);
            padding: 0.75rem;
            border-radius: 1rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .nav-link {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .nav-link i {
            transition: transform 0.3s ease;
        }

        .nav-link:hover i {
            transform: scale(1.2);
        }

        .active-nav {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.15);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        /* Animations */
        @keyframes glow {
            0% {
                text-shadow: 0 0 5px rgba(34, 197, 94, 0.2);
            }
            100% {
                text-shadow: 0 0 20px rgba(34, 197, 94, 0.4);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }
            100% {
                background-position: 200% center;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-text {
                font-size: 2.5rem;
            }

            .nav-links-container {
                flex-direction: column;
                width: 100%;
                padding: 0.5rem;
            }

            .nav-link {
                width: 100%;
                margin: 0.25rem 0;
                justify-content: center;
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

        .btn-3d {
            transform-style: preserve-3d;
            transition: all 0.3s ease;
        }

        .btn-3d:hover {
            transform: translateY(-2px) translateZ(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-3d:active {
            transform: translateY(1px) translateZ(5px);
        }

        .group:hover svg {
            transform: translateX(-4px);
        }

        @media (max-width: 768px) {
            .container .mb-6 {
                padding: 0 1rem;
            }
        }

        .back-btn {
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            transform: translateZ(0);
        }

        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .back-btn:hover::before {
            transform: translateX(100%);
        }

        .back-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #ffffff, transparent);
            transform: scaleX(0);
            transform-origin: center;
            transition: transform 0.3s ease;
        }

        .back-btn:hover::after {
            transform: scaleX(1);
        }

        /* Add pulsing animation for the arrow */
        @keyframes pulse {
            0% { transform: translateX(0); }
            50% { transform: translateX(-4px); }
            100% { transform: translateX(0); }
        }

        .back-btn:hover svg {
            animation: pulse 1s ease-in-out infinite;
        }

        /* Add subtle scale effect on click */
        .back-btn:active {
            transform: translateY(2px) scale(0.98);
        }

        /* Add responsive adjustments */
        @media (max-width: 640px) {
            .back-btn {
                padding: 0.75rem 1.25rem;
                font-size: 0.875rem;
            }
            
            .back-btn svg {
                width: 1rem;
                height: 1rem;
            }
        }

        /* Back Button Styles */
        .nav-link.back-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .nav-link.back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }

        .nav-link.back-btn i {
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }

        .nav-link.back-btn:hover i {
            transform: translateX(-3px);
        }

        /* Responsive adjustment for back button */
        @media (max-width: 768px) {
            .header-container {
                position: relative;
                padding-top: 4rem;
            }
            
            .nav-link.back-btn {
                position: absolute;
                top: 1rem;
                left: 1rem;
                z-index: 10;
            }
        }
    </style>
</head>
<body class="font-sans">
    <!-- Header Container -->
    <div class="header-container">
        <!-- Existing Logo -->
        <div class="logo-container">
            <h1 class="logo-text">TravelEase</h1>
        </div>

        <!-- Existing Navigation Menu -->
        <nav class="nav-menu">
            <a href="home.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="userpackages.php" class="nav-link active">
                <i class="fas fa-box"></i>
                <span>Packages</span>
            </a>
            <a href="my_bookings.php" class="nav-link">
                <i class="fas fa-calendar-check"></i>
                <span>My Bookings</span>
            </a>
            <a href="home.php#about" class="nav-link">
                <i class="fas fa-info-circle"></i>
                <span>About Us</span>
            </a>
            <a href="home.php#enquiry" class="nav-link">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </a>
        </nav>
    </div>

    <!-- Back Button - Aligned with header -->
    <div style="max-width: 1200px; margin: 1rem auto; padding: 0 2rem;">
        <a href="javascript:history.back()" class="nav-link" style="
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.2);
            width: fit-content;">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
    </div>

    <!-- Search Container -->
    <form action="userpackages.php" method="GET" class="search-container">
        <input type="text" 
               name="search" 
               class="search-input" 
               placeholder="Search for travel packages..." 
               value="<?php echo htmlspecialchars($searchQuery); ?>">
        <button type="submit" class="search-button">
            <i class="fas fa-search"></i> Search
        </button>
    </form>

    <!-- Page Title -->
    <h1 class="page-title">Explore Our Travel Packages</h1>

    <div class="container">
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
<?php
session_start();
require_once 'config.php';

// Add error message display if redirected from booking.php
$errorMessage = '';
if (isset($_SESSION['booking_error'])) {
    $errorMessage = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']); // Clear the error message after displaying
}

// Fetch all packages from the database
$sql = "SELECT * FROM travel_packages ORDER BY id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Travel Packages - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --white: #ffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
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
            font-size: 2.5rem;
            margin: 0;
            font-weight: 600;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .package-card {
            background: var(--white);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }

        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .package-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .package-content {
            padding: 1.5rem;
        }

        .package-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0 0 0.5rem 0;
        }

        .package-destination {
            color: var(--text-light);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .package-destination i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .package-details {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
            padding: 0.5rem 0;
            border-top: 1px solid var(--bg-light);
            border-bottom: 1px solid var(--bg-light);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .package-description {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 1rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            text-decoration: none;
            text-align: center;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: transform 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .book-btn:hover {
            transform: translateY(-2px);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .packages-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }

        /* Animation delays for cards */
        <?php if ($result->num_rows > 0): 
            $delay = 0;
            while($row = $result->fetch_assoc()):
        ?>
        .package-card:nth-child(<?php echo $delay + 1; ?>) {
            animation-delay: <?php echo $delay * 0.1; ?>s;
        }
        <?php 
            $delay++;
            endwhile;
        endif; ?>

        .error-message {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
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

                        <p class="package-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        
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
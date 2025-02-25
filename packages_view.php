<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Fetch all packages from the database
$sql = "SELECT * FROM travel_packages ORDER BY id DESC";
$result = $conn->query($sql);
$travel_packages = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $travel_packages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Packages - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --white: #ffffff;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 2rem 0;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-links li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links li a i {
            margin-right: 1rem;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .add-package-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .add-package-btn:hover {
            background-color: var(--secondary-color);
        }

        /* Package Grid Styles */
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .package-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .package-card:hover {
            transform: translateY(-5px);
        }

        .package-image {
            height: 200px;
            overflow: hidden;
        }

        .package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .package-details {
            padding: 1.5rem;
        }

        .package-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .package-info {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .package-price {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .package-actions {
            display: flex;
            gap: 1rem;
        }

        .edit-btn, .delete-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .edit-btn {
            background-color: var(--accent-color);
            color: white;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .edit-btn:hover {
            background-color: var(--secondary-color);
        }

        .delete-btn:hover {
            background-color: #dc2626;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .packages-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
                    <a href="manage_bookings.php">
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
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Manage Packages</h1>
                <a href="manage_packages.php" class="add-package-btn">
                    <i class="fas fa-plus"></i>
                    Add New Package
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="packages-grid">
                <?php foreach ($travel_packages as $package): ?>
                    <div class="package-card">
                        <div class="package-image">
                            <?php if (!empty($package['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($package['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($package['package_name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No image available</div>
                            <?php endif; ?>
                        </div>
                        <div class="package-details">
                            <h3 class="package-name"><?php echo htmlspecialchars($package['package_name']); ?></h3>
                            <div class="package-info">
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($package['destination']); ?></p>
                                <p><i class="fas fa-clock"></i> <?php echo htmlspecialchars($package['duration']); ?> days</p>
                            </div>
                            <div class="package-price">
                                ₹<?php echo number_format($package['price'], 2); ?>
                            </div>
                            <div class="package-actions">
                                <button onclick="location.href='update_package.php?id=<?php echo $package['id']; ?>'" 
                                        class="edit-btn">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button onclick="deletePackage(<?php echo $package['id']; ?>)" 
                                        class="delete-btn">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function deletePackage(packageId) {
            console.log('Package ID to delete:', packageId); // Log the package ID
            if (confirm('Are you sure you want to delete this package?')) {
                // Create form data
                const formData = new FormData();
                formData.append('id', packageId);

                fetch('delete_package.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(data); // Log the response for debugging
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to delete package');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the package. Please try again. Error: ' + error.message);
                });
            }
        }
    </script>
</body>
</html> 
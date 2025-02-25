<?php
session_start();

// Check if user is logged in and is an admin or staff
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'Staff')) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "travel_booking";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all packages
$packages_query = "SELECT * FROM travel_packages ORDER BY package_name";
$packages_result = $conn->query($packages_query);
$packages = [];
while ($row = $packages_result->fetch_assoc()) {
    $packages[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Packages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --secondary-color: #858796;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
            overflow-x: hidden;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-color), var(--info-color));
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links {
            list-style: none;
            padding: 20px 0;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-links li a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(10px);
        }

        .nav-links li i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background-color: #f8f9fc;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-in;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: white;
            font-size: 2em;
            font-weight: 600;
            margin: 0;
        }

        .package-form-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: linear-gradient(135deg, var(--info-color), var(--primary-color));
        }

        .section-header h2 {
            color: white;
            margin: 0;
        }

        .add-package-form {
            padding: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .packages-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px 0;
        }

        .package-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .package-card:hover {
            transform: translateY(-5px);
        }

        .package-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .package-details {
            padding: 20px;
        }

        .package-name {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .package-info {
            margin-bottom: 15px;
            color: var(--secondary-color);
        }

        .package-price {
            font-size: 1.3em;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .package-actions {
            display: flex;
            gap: 10px;
        }

        .edit-btn,
        .delete-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: var(--warning-color);
            color: white;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .toggle-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            background-color: #169b6b;
            transform: translateY(-2px);
        }

        .success-message,
        .error-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
            font-weight: 500;
        }

        .success-message {
            background: linear-gradient(135deg, var(--success-color), #169b6b);
        }

        .error-message {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
        }

        @media screen and (max-width: 1024px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
            }

            .dashboard-container {
                padding: 0 15px;
            }

            .packages-container {
                grid-template-columns: 1fr;
            }
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

        .default-image {
            width: 100%;
            height: 200px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .default-image i {
            font-size: 3em;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul class="nav-links">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="manage_bookings.php">
                        <i class="fas fa-calendar-check"></i>
                        Bookings
                    </a>
                </li>
                <li>
                    <a href="packages.php">
                        <i class="fas fa-box"></i>
                        Packages
                    </a>
                </li>
                <li>
                    <a href="view_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <form action="logout.php" method="POST">
                        <button type="submit" style="width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; display: flex; align-items: center;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span style="margin-left: 10px;">Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <div class="main-content">
            <div class="dashboard-container">
                <div class="header">
                    <h1>Manage Packages</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="package-form-section">
                    <div class="section-header">
                        <h2>Add New Package</h2>
                        <button id="togglePackageForm" class="toggle-btn">Show/Hide Form</button>
                    </div>
                    <div id="packageForm" style="display: none;">
                        <form action="add_package.php" method="POST" enctype="multipart/form-data" class="add-package-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="package_name">Package Name:</label>
                                    <input type="text" id="package_name" name="package_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="destination">Destination:</label>
                                    <input type="text" id="destination" name="destination" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" required></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="price">Price:</label>
                                    <input type="number" id="price" name="price" min="0" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="duration">Duration (days):</label>
                                    <input type="number" id="duration" name="duration" min="1" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="package_image">Package Image:</label>
                                <input type="file" id="package_image" name="package_image" accept="image/jpeg,image/png,image/gif,image/jpg" required>
                                <small style="color: #666; display: block; margin-top: 5px;">Accepted formats: JPG, JPEG, PNG, GIF</small>
                            </div>
                            <button type="submit" class="toggle-btn">Add Package</button>
                        </form>
                    </div>
                </div>

                <div class="packages-container">
                    <?php foreach ($packages as $package): ?>
                    <div class="package-card">
                        <?php if (!empty($package['image_path']) && file_exists($package['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($package['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($package['package_name']); ?>" 
                                 class="package-image">
                        <?php else: ?>
                            <div class="package-image default-image">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <div class="package-details">
                            <div class="package-name"><?php echo htmlspecialchars($package['package_name']); ?></div>
                            <div class="package-info">
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($package['destination']); ?></p>
                                <p><i class="fas fa-clock"></i> <?php echo htmlspecialchars($package['duration']); ?> days</p>
                            </div>
                            <div class="package-price">$<?php echo number_format($package['price'], 2); ?></div>
                            <div class="package-actions">
                                <button onclick="editPackage(<?php echo $package['id']; ?>)" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="deletePackage(<?php echo $package['id']; ?>)" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePackageForm').addEventListener('click', function() {
            const packageForm = document.getElementById('packageForm');
            const button = this;
            
            if (packageForm.style.display === 'none' || !packageForm.style.display) {
                packageForm.style.display = 'block';
                packageForm.style.opacity = '0';
                packageForm.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    packageForm.style.transition = 'all 0.3s ease';
                    packageForm.style.opacity = '1';
                    packageForm.style.transform = 'translateY(0)';
                }, 10);
                
                button.textContent = 'Hide Form';
            } else {
                packageForm.style.opacity = '0';
                packageForm.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    packageForm.style.display = 'none';
                }, 300);
                
                button.textContent = 'Show Form';
            }
        });

        function editPackage(packageId) {
            window.location.href = 'edit_package.php?id=' + packageId;
        }

        function deletePackage(packageId) {
            // Create a modal for confirmation
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal-overlay">
                    <div class="modal-content">
                        <p>Do you really want to delete this package?</p>
                        <button id="confirmDelete" class="toggle-btn">Yes</button>
                        <button id="cancelDelete" class="toggle-btn">No</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Center the modal
            const overlay = document.querySelector('.modal-overlay');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '1001';

            const content = document.querySelector('.modal-content');
            content.style.backgroundColor = 'white';
            content.style.padding = '20px';
            content.style.borderRadius = '8px';
            content.style.textAlign = 'center';

            document.getElementById('confirmDelete').onclick = function() {
                const formData = new FormData();
                formData.append('id', packageId);

                fetch('delete_package.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting package');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting package. Please try again.');
                });

                document.body.removeChild(modal); // Remove modal after confirmation
            };

            document.getElementById('cancelDelete').onclick = function() {
                document.body.removeChild(modal); // Remove modal on cancel
            };
        }

        // Animate success/error messages
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                message.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    </script>
</body>
</html> 
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

// Get all package names for JavaScript validation
$package_names = array_map(function($package) {
    return strtolower($package['package_name']);
}, $packages);
$package_names_json = json_encode($package_names);
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
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
        }

        .toggle-btn.active {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #15803d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3),
                        0 0 0 2px rgba(34, 197, 94, 0.1),
                        inset 0 -3px 0 rgba(0, 0, 0, 0.1);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .toggle-btn.inactive {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3),
                        0 0 0 2px rgba(220, 38, 38, 0.1),
                        inset 0 -3px 0 rgba(0, 0, 0, 0.1);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .toggle-btn:hover.active {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 50%, #166534 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4),
                        0 0 0 3px rgba(34, 197, 94, 0.2),
                        inset 0 -4px 0 rgba(0, 0, 0, 0.2);
        }

        .toggle-btn:hover.inactive {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4),
                        0 0 0 3px rgba(220, 38, 38, 0.2),
                        inset 0 -4px 0 rgba(0, 0, 0, 0.2);
        }

        .toggle-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.6),
                transparent
            );
            transition: 0.6s;
        }

        .toggle-btn:hover::before {
            left: 100%;
        }

        .toggle-btn i {
            font-size: 1.2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.1));
        }

        .toggle-btn:hover i {
            transform: rotate(180deg) scale(1.2);
        }

        .toggle-btn.active i {
            color: #ecfdf5;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .toggle-btn.inactive i {
            color: #fef2f2;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .status-text {
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 700;
            position: relative;
        }

        .toggle-btn:hover .status-text {
            transform: translateX(3px);
            letter-spacing: 1px;
        }

        .toggle-btn::after {
            content: '';
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 8px;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .toggle-btn.active::after {
            background-color: #4ade80;
            box-shadow: 0 0 12px #4ade80,
                       0 0 20px rgba(74, 222, 128, 0.4);
        }

        .toggle-btn.inactive::after {
            background-color: #f87171;
            box-shadow: 0 0 12px #f87171,
                       0 0 20px rgba(248, 113, 113, 0.4);
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes toggleAnimation {
            0% { transform: scale(1) translateY(0); }
            40% { transform: scale(0.95) translateY(3px); }
            80% { transform: scale(1.05) translateY(-2px); }
            100% { transform: scale(1) translateY(0); }
        }

        .toggle-btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15),
                       inset 0 -2px 0 rgba(0, 0, 0, 0.2);
        }

        .toggle-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.6),
                       0 0 0 6px rgba(var(--focus-color, 34, 197, 94), 0.4);
        }

        @keyframes loading {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .toggle-btn.loading::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: loading 0.8s linear infinite;
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

        .status-btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .status-btn.active {
            background: linear-gradient(145deg, #10b981, #059669);
            color: white;
        }

        .status-btn.inactive {
            background: linear-gradient(145deg, #ef4444, #dc2626);
            color: white;
        }

        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .status-btn:active {
            transform: translateY(0);
        }

        .status-btn i {
            font-size: 1.1em;
            transition: transform 0.3s ease;
        }

        .status-btn:hover i {
            transform: scale(1.1);
        }

        .status-btn.active:hover {
            background: linear-gradient(145deg, #059669, #047857);
        }

        .status-btn.inactive:hover {
            background: linear-gradient(145deg, #dc2626, #b91c1c);
        }

        /* Custom Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }

        .modal-container {
            background-color: white;
            border-radius: 15px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: scale(0.9);
            animation: scaleIn 0.3s ease forwards;
        }

        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .close-btn:hover {
            transform: scale(1.2);
        }

        .modal-content {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #eee;
        }

        .cancel-btn, .confirm-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .cancel-btn {
            background-color: #e2e8f0;
            color: #475569;
        }

        .cancel-btn:hover {
            background-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .confirm-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 115, 223, 0.3);
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
                                    <small id="nameError" style="color: red; display: none;">Package name is required.</small>
                                </div>
                                <div class="form-group">
                                    <label for="destination">Destination:</label>
                                    <input type="text" id="destination" name="destination" required>
                                    <small id="destinationError" style="color: red; display: none;">Destination is required.</small>
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
                                    <small id="priceError" style="color: red; display: none;">Price must be a positive number.</small>
                                </div>
                                <div class="form-group">
                                    <label for="duration">Duration (days):</label>
                                    <input type="number" id="duration" name="duration" min="1" required>
                                    <small id="durationError" style="color: red; display: none;">Duration must be at least 1 day.</small>
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
                            <div class="package-price">₹<?php echo number_format($package['price'], 2, '.', ','); ?></div>
                            <div class="package-actions">
                                <button onclick="editPackage(<?php echo $package['id']; ?>)" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="togglePackageStatus(<?php echo $package['id']; ?>, <?php echo $package['active']; ?>)" 
                                        class="status-btn <?php echo $package['active'] ? 'active' : 'inactive'; ?>">
                                    <i class="fas fa-toggle-<?php echo $package['active'] ? 'on' : 'off'; ?>"></i>
                                    <?php echo $package['active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add custom popup modal -->
    <div id="confirmationModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Confirm Action</h3>
                <button class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-content">
                <p>Are you sure you want to change this package status?</p>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button id="confirmButton" class="confirm-btn">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        const existingPackageNames = <?php echo $package_names_json; ?>;
        
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

        function togglePackageStatus(packageId, currentStatus) {
            // Store current package info for later use
            const packageData = {
                id: packageId,
                status: currentStatus
            };
            
            // Show the custom modal
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'flex';
            
            // Setup confirm button action
            const confirmBtn = document.getElementById('confirmButton');
            confirmBtn.onclick = function() {
                closeModal();
                
                const formData = new FormData();
                formData.append('package_id', packageData.id);
                formData.append('status', packageData.status === 1 ? 0 : 1);

                fetch('toggle_package_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to update package status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating package status');
                });
            };
        }
        
        function closeModal() {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'none';
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

        document.querySelector('.add-package-form').addEventListener('submit', function(e) {
            const packageName = document.getElementById('package_name').value.trim().toLowerCase();
            
            if (existingPackageNames.includes(packageName)) {
                e.preventDefault();
                alert('A package with this name already exists. Please choose a different name.');
                document.getElementById('nameError').textContent = 'This package name already exists.';
                document.getElementById('nameError').style.display = 'block';
                return false;
            }
        });

        document.getElementById('package_name').addEventListener('input', function() {
            const nameError = document.getElementById('nameError');
            const packageName = this.value.trim().toLowerCase();
            
            if (packageName === '') {
                nameError.textContent = 'Package name is required.';
                nameError.style.display = 'block';
            } else if (existingPackageNames.includes(packageName)) {
                nameError.textContent = 'This package name already exists.';
                nameError.style.display = 'block';
            } else {
                nameError.style.display = 'none';
            }
        });

        document.getElementById('destination').addEventListener('input', function() {
            const destinationError = document.getElementById('destinationError');
            destinationError.style.display = this.value.trim() === '' ? 'block' : 'none';
        });

        document.getElementById('price').addEventListener('input', function() {
            const priceError = document.getElementById('priceError');
            priceError.style.display = this.value <= 0 ? 'block' : 'none';
        });

        document.getElementById('duration').addEventListener('input', function() {
            const durationError = document.getElementById('durationError');
            durationError.style.display = this.value < 1 ? 'block' : 'none';
        });
    </script>
</body>
</html> 
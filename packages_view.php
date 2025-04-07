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

// At the top of the file, after session_start()
if (isset($_SESSION['success'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('" . addslashes($_SESSION['success']) . "', 'success');
        });
    </script>";
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('" . addslashes($_SESSION['error']) . "', 'warning');
        });
    </script>";
    unset($_SESSION['error']);
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

        .nav-links li a::after, .logout-btn::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.1), transparent);
            transition: width 0.3s ease;
        }

        .nav-links li a:hover::after, .logout-btn:hover::after {
            width: 100%;
        }

        .logout-btn {
            margin-top: 2rem;
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.3);
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 1rem;
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

        /* Add smooth scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Active menu item indicator */
        .nav-links li a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(to bottom, #00f2fe, #4facfe);
            border-radius: 0 2px 2px 0;
        }

        /* Adjust main content margin */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
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

        .edit-btn, .status-btn {
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

        .status-btn {
            background-color: var(--accent-color);
            color: white;
        }

        .activate-btn, .deactivate-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .activate-btn {
            background-color: var(--success-color);
            color: white;
        }

        .deactivate-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .edit-btn:hover {
            background-color: var(--secondary-color);
        }

        .activate-btn:hover {
            background-color: #059669;
        }

        .deactivate-btn:hover {
            background-color: #dc2626;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
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
            }
        }

        /* Updated toast notification styles */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            border-left: 4px solid;
            min-width: 300px;
        }

        .toast-success {
            border-left-color: var(--success-color);
        }

        .toast-warning {
            border-left-color: var(--danger-color);
        }

        .toast-icon {
            font-size: 1.25rem;
        }

        .toast-success .toast-icon {
            color: var(--success-color);
        }

        .toast-warning .toast-icon {
            color: var(--danger-color);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        /* Add loading animation styles */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: currentColor;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .inactive-package {
            opacity: 0.7;
            position: relative;
        }

        .inactive-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ef4444;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            z-index: 1;
        }

        .status-btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .deactivate-btn {
            background-color: #ef4444;
            color: white;
        }

        .activate-btn {
            background-color: #10b981;
            color: white;
        }

        .status-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-dark);
        }

        .close-modal {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-light);
            cursor: pointer;
        }

        .close-modal:hover {
            color: var(--danger-color);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid #e5e7eb;
        }

        .modal-btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .cancel-btn {
            background-color: #e5e7eb;
            color: var(--text-dark);
        }

        .confirm-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .cancel-btn:hover {
            background-color: #d1d5db;
        }

        .confirm-btn:hover {
            background-color: var(--secondary-color);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
                    <a href="packages_view.php" class="active">
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
                    <div class="package-card <?php echo !$package['active'] ? 'inactive-package' : ''; ?>">
                        <?php if (!$package['active']): ?>
                            <div class="inactive-badge">Inactive</div>
                        <?php endif; ?>
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
                                <button onclick="togglePackageStatus(<?php echo $package['id']; ?>, <?php echo $package['active']; ?>)" 
                                        class="status-btn <?php echo $package['active'] ? 'deactivate-btn' : 'activate-btn'; ?>">
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

    <!-- Add this div at the end of the body but before the closing body tag -->
    <div id="toast-container"></div>
    
    <!-- Custom Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Action</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="modal-message">Are you sure you want to change this package status?</p>
            </div>
            <div class="modal-footer">
                <button id="modal-cancel" class="modal-btn cancel-btn">Cancel</button>
                <button id="modal-confirm" class="modal-btn confirm-btn">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        function showToast(message, type = 'success', isLoading = false) {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            
            let icon;
            if (isLoading) {
                icon = '<div class="loading-spinner"></div>';
            } else {
                icon = type === 'success' ? 
                    '<i class="fas fa-check-circle toast-icon"></i>' : 
                    '<i class="fas fa-exclamation-circle toast-icon"></i>';
            }
            
            toast.innerHTML = `
                ${icon}
                <span>${message}</span>
            `;
            
            const container = document.getElementById('toast-container');
            container.appendChild(toast);
            
            if (!isLoading) {
                // Remove the toast after 3 seconds if it's not a loading toast
                setTimeout(() => {
                    toast.style.animation = 'fadeOut 0.3s ease-out forwards';
                    setTimeout(() => {
                        container.removeChild(toast);
                    }, 300);
                }, 3000);
            }
            
            return toast; // Return the toast element for later reference
        }

        // Variables to store pending action data
        let pendingPackageId = null;
        let pendingStatus = null;

        // Function to show the confirmation modal
        function showConfirmationModal(packageId, currentStatus) {
            // Store the values for use when confirmed
            pendingPackageId = packageId;
            pendingStatus = currentStatus;
            
            // Set modal message based on current status
            const action = currentStatus === 1 ? 'deactivate' : 'activate';
            document.getElementById('modal-message').textContent = 
                `Are you sure you want to ${action} this package?`;
            
            // Show the modal
            document.getElementById('confirmationModal').style.display = 'flex';
        }

        // Function to hide the modal
        function hideConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        // Process the status change
        function processStatusChange() {
            if (pendingPackageId === null) return;
            
            const formData = new FormData();
            formData.append('package_id', pendingPackageId);
            formData.append('status', pendingStatus === 1 ? 0 : 1);

            fetch('toggle_package_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the page to show updated status
                    location.reload();
                } else {
                    showToast(data.message || 'Failed to update package status', 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error updating package status', 'warning');
            });
            
            // Reset pending values
            pendingPackageId = null;
            pendingStatus = null;
        }

        function togglePackageStatus(packageId, currentStatus) {
            showConfirmationModal(packageId, currentStatus);
        }
        
        // Set up event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking the X button
            document.querySelector('.close-modal').addEventListener('click', hideConfirmationModal);
            
            // Cancel button closes the modal
            document.getElementById('modal-cancel').addEventListener('click', hideConfirmationModal);
            
            // Confirm button processes the action and closes the modal
            document.getElementById('modal-confirm').addEventListener('click', function() {
                processStatusChange();
                hideConfirmationModal();
            });
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === document.getElementById('confirmationModal')) {
                    hideConfirmationModal();
                }
            });
        });
    </script>
</body>
</html> 
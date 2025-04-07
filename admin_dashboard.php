<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
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

// Fetch total number of users
$users_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Fetch all users
$users_result = $conn->query("SELECT name, email FROM users ORDER BY name");
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Update the packages count query to use travel_packages table
$package_count = $conn->query("SELECT COUNT(*) as total FROM travel_packages");
$total_packages = $package_count->fetch_assoc()['total'];

// Add this after the query to debug
if (!$package_count) {
    echo "Error: " . $conn->error;
}

// Update the packages query to get all packages
$packages_result = $conn->query("SELECT * FROM travel_packages");
$packages = [];
while ($row = $packages_result->fetch_assoc()) {
    $packages[] = $row;
}

// Add admin check for package actions
$enable_package_actions = isset($_SESSION['enable_package_actions']) && $_SESSION['user_role'] === 'Admin' 
    ? $_SESSION['enable_package_actions'] 
    : false;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Add these styles to your existing CSS -->
    <style>
        /* Modern Color Scheme */
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --secondary-color: #858796;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --secondary-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --accent-gradient: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
        }

        /* Enhanced Dashboard Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        /* Modernized Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-color), var(--info-color));
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 5px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 20px;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav-links {
            list-style: none;
            padding: 20px 0;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }

        .nav-links li a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #10b981;
            transform: translateX(5px);
        }

        .nav-links li a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 1.25rem;
        }

        /* Enhanced Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background-color: #f8f9fc;
            min-height: 100vh;
        }

        /* Modern Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                       0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-gradient);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                       0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-card p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Enhanced Tables */
        .users-list {
            background: var(--card-background);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .users-table th {
            background: #f8fafc;
            padding: 1rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .users-table td {
            padding: 1rem;
            color: var(--text-primary);
            border-bottom: 1px solid #e2e8f0;
        }

        .users-table tr:hover td {
            background: #f1f5f9;
        }

        /* Modern Action Buttons */
        .action-button {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -2px rgba(99, 102, 241, 0.3);
        }

        .action-button i {
            font-size: 1rem;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header h2 i {
            color: #6366f1;
        }

        /* Add animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .stats-container > *, .users-list {
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .stats-container > *:nth-child(1) { animation-delay: 0.1s; }
        .stats-container > *:nth-child(2) { animation-delay: 0.2s; }
        .stats-container > *:nth-child(3) { animation-delay: 0.3s; }
    </style>

    <!-- Add this in your HTML head section -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <div class="section-header">
                    <h2>
                        <i class="fas fa-chart-line"></i>
                        Dashboard Overview
                    </h2>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <h3>
                            <i class="fas fa-users" style="color: #6366f1;"></i>
                            Total Users
                        </h3>
                        <p><?php echo $users_count; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>
                            <i class="fas fa-box" style="color: #8b5cf6;"></i>
                            Total Packages
                        </h3>
                        <p><?php echo $total_packages; ?></p>
                    </div>
                    
                    <!-- <div class="stat-card">
                        <h3>
                            <i class="fas fa-chart-bar" style="color: #10b981;"></i>
                            Active Packages
                        </h3>
                        <p><?php echo count(array_filter($packages, function($p) { 
                            return isset($p['status']) && $p['status'] === 'active'; 
                        })); ?></p>
                    </div> -->
                </div>

                <!-- Add this new section for user list -->
                <div class="users-list" id="usersSection" style="display: none;">
                    <div class="section-header">
                        <h2>Registered Users</h2>
                        <button id="toggleUsersList" class="toggle-btn">Hide Users</button>
                    </div>
                    <div id="usersTableContainer" style="display: block;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Packages List Section -->
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <div class="users-list" id="packagesSection">
                    <div class="section-header">
                        <h2>Travel Packages</h2>
                        <!-- <button id="togglePackageActions" class="toggle-btn" style="margin-right: 10px;">
                            <?php echo $enable_package_actions ? 'Disable Actions' : 'Enable Actions'; ?>
                        </button> -->
                    </div>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Destination</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <!-- <th>Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr data-package-id="<?php echo $package['id']; ?>">
                                <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                <td><?php echo htmlspecialchars($package['destination']); ?></td>
                                <td>₹<?php 
                                    $formatted_price = preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $package['price']);
                                    echo $formatted_price; 
                                ?></td>
                                <td><?php echo htmlspecialchars($package['duration']); ?> days</td>
                                <!-- <td>
                                    <button class="edit-btn package-action" onclick="editPackage(<?php echo $package['id']; ?>)" <?php echo !$enable_package_actions ? 'disabled' : ''; ?>>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="delete-btn package-action" onclick="deletePackage(<?php echo $package['id']; ?>)" <?php echo !$enable_package_actions ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td> -->
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="actions-container">
                    <button class="action-button" onclick="location.href='manage_users.php'">
                        Manage Users
                    </button>
                    <button class="action-button" onclick="location.href='packages.php'">
                        Manage Packages
                    </button>
                    <button class="action-button" onclick="location.href='view_reports.php'">
                        View Reports
                    </button>
                </div>

                <!-- Add Package Form Section -->
                <!-- <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <div class="package-form-section">
                    <div class="section-header">
                        <h2>Add New Travel Package</h2>
                        <button id="togglePackageForm" class="toggle-btn">Show/Hide Form</button>
                    </div>
                    <div id="packageForm" style="display: none;">
                        <form action="add_package.php" method="POST" enctype="multipart/form-data" class="add-package-form">
                            <div class="form-group">
                                <label for="package_name">Package Name:</label>
                                <input type="text" id="package_name" name="package_name" required>
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
                            </div> -->
                            <!-- <div class="form-group">
                                <label for="destination">Destination:</label>
                                <input type="text" id="destination" name="destination" required>
                            </div>
                            <div class="form-group">
                                <label for="package_image">Package Image:</label>
                                <input type="file" id="package_image" name="package_image" accept="image/*" required>
                            </div>
                            <button type="submit" class="submit-btn">Add Package</button>
                        </form>
                    </div>
                </div> -->
                <?php endif; ?>

                <!-- Add User Form Section -->
                <!-- <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <div class="package-form-section">
                    <div class="section-header">
                        <h2>Add New User</h2>
                        <button id="toggleUserForm" class="toggle-btn">Show/Hide Form</button>
                    </div>
                    <div id="userForm" style="display: none;">
                        <form action="add_user.php" method="POST" class="add-package-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name:</label>
                                    <input type="text" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password">Password:</label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                                <div class="form-group">
                                    <label for="user_role">User Role:</label>
                                    <select id="user_role" name="user_role" required>
                                        <option value="Client">Client</option>
                                        <option value="Staff">Staff</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="submit-btn">Add User</button>
                        </form>
                    </div> -->
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add this new script for package form toggle
        document.getElementById('togglePackageForm').addEventListener('click', function() {
            const packageForm = document.getElementById('packageForm');
            if (packageForm.style.display === 'none') {
                packageForm.style.display = 'block';
                this.textContent = 'Hide Form';
            } else {
                packageForm.style.display = 'none';
                this.textContent = 'Show Form';
            }
        });

        function editPackage(packageId) {
            if (confirm('Do you want to edit this package?')) {
                // Store the current scroll position in session storage
                sessionStorage.setItem('scrollPosition', window.scrollY);
                window.location.href = 'edit_package.php?id=' + packageId;
            }
        }

        function deletePackage(packageId) {
            console.log('Package ID to delete:', packageId); // Log the package ID
            if (confirm('Are you sure you want to delete this package?')) {
                // Create form data
                const formData = new FormData();
                formData.append('package_id', packageId);

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

        // Add this function to handle navigation
        function showSection(sectionId) {
            // Hide all sections first
            const sections = ['packagesSection', 'usersSection'];
            sections.forEach(section => {
                document.getElementById(section).style.display = 'none';
            });

            // Show the selected section
            document.getElementById(sectionId).style.display = 'block';
        }

        // Add click event listener to users link
        document.querySelector('a[href="manage_users.php"]').addEventListener('click', function(e) {
            e.preventDefault();
            showSection('usersSection');
        });

        // Updated script for users list toggle
        document.getElementById('toggleUsersList').addEventListener('click', function() {
            const usersTable = document.getElementById('usersTableContainer');
            const toggleBtn = document.getElementById('toggleUsersList');
            
            if (usersTable.style.display === 'block') {
                usersTable.style.display = 'none';
                toggleBtn.textContent = 'Show Users';
            } else {
                usersTable.style.display = 'block';
                toggleBtn.textContent = 'Hide Users';
            }
        });

        // Add this new script for package actions toggle
        document.getElementById('togglePackageActions').addEventListener('click', function() {
            const actionButtons = document.querySelectorAll('.package-action');
            const isCurrentlyEnabled = !actionButtons[0].disabled;
            
            actionButtons.forEach(button => {
                button.disabled = isCurrentlyEnabled;
            });
            
            this.textContent = isCurrentlyEnabled ? 'Enable Actions' : 'Disable Actions';
            
            // Optional: Save the state to session using AJAX
            fetch('toggle_package_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'enabled=' + (!isCurrentlyEnabled)
            });
        });

        document.getElementById('toggleUserForm').addEventListener('click', function() {
            const userForm = document.getElementById('userForm');
            if (userForm.style.display === 'none') {
                userForm.style.display = 'block';
                this.textContent = 'Hide Form';
            } else {
                userForm.style.display = 'none';
                this.textContent = 'Show Form';
            }
        });
    </script>
</body>
</html>

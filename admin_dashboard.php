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

// Fetch packages - with admin check and error handling
$packages = [];
if ($_SESSION['user_role'] === 'Admin') {
    $packages_result = $conn->query("SHOW TABLES LIKE 'packages'");
    if ($packages_result->num_rows > 0) {
        $packages_result = $conn->query("SELECT id, package_name, description, price, duration, destination FROM packages ORDER BY package_name");
        while ($row = $packages_result->fetch_assoc()) {
            $packages[] = $row;
        }
    }
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
<style>
    /* Main Layout */
body {
    margin: 0;
    font-family: 'Arial', sans-serif;
    background-color: #f5f6fa;
}

.dashboard-layout {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.sidebar {
    width: 250px;
    background-color: #2c3e50;
    color: white;
    padding: 20px 0;
    position: fixed;
    height: 100vh;
}

.sidebar-header {
    padding: 0 20px;
    margin-bottom: 30px;
}

.nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-links li a {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: white;
    text-decoration: none;
    transition: background-color 0.3s;
}

.nav-links li a:hover {
    background-color: #34495e;
}

.nav-links i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

/* Main Content Area */
.main-content {
    flex: 1;
    margin-left: 250px;
    padding: 20px;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
}

.header {
    margin-bottom: 30px;
}

.header h1 {
    color: #2c3e50;
    margin: 0;
}

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #7f8c8d;
    font-size: 16px;
}

.stat-card p {
    margin: 0;
    color: #2c3e50;
    font-size: 24px;
    font-weight: bold;
}

/* Users List Table */
.users-list {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.users-table th,
.users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.users-table th {
    background-color: #f8f9fa;
    color: #2c3e50;
    font-weight: 600;
}

.users-table tr:hover {
    background-color: #f8f9fa;
}

/* Action Buttons */
.actions-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.action-button {
    padding: 12px 20px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s;
}

.action-button:hover {
    background-color: #2980b9;
}

/* Package Form Section */
.package-form-section {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.toggle-btn {
    padding: 8px 16px;
    background-color: #2ecc71;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.toggle-btn:hover {
    background-color: #27ae60;
}

.add-package-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
}

.form-group input,
.form-group textarea {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.submit-btn {
    padding: 12px 24px;
    background-color: #2ecc71;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
    align-self: flex-start;
}

.submit-btn:hover {
    background-color: #27ae60;
}

/* Package Actions Buttons */
.edit-btn, .delete-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
    color: white;
}

.edit-btn {
    background-color: #3498db;
}

.delete-btn {
    background-color: #e74c3c;
}

.edit-btn:hover {
    background-color: #2980b9;
}

.delete-btn:hover {
    background-color: #c0392b;
}

/* Add these styles to your existing CSS */
.package-action:disabled {
    background-color: #ccc;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Add these styles to your existing CSS */
select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 100%;
}

.form-group select {
    height: 40px;
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
                    <h1>Dashboard Overview</h1>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p><?php echo $users_count; ?></p>
                    </div>
                    <!-- Add more stat cards as needed -->
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
                        <button id="togglePackageActions" class="toggle-btn" style="margin-right: 10px;">
                            <?php echo $enable_package_actions ? 'Disable Actions' : 'Enable Actions'; ?>
                        </button>
                    </div>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Destination</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr data-package-id="<?php echo $package['id']; ?>">
                                <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                <td><?php echo htmlspecialchars($package['destination']); ?></td>
                                <td>$<?php echo htmlspecialchars($package['price']); ?></td>
                                <td><?php echo htmlspecialchars($package['duration']); ?> days</td>
                                <td>
                                    <button class="edit-btn package-action" onclick="editPackage(<?php echo $package['id']; ?>)" <?php echo !$enable_package_actions ? 'disabled' : ''; ?>>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="delete-btn package-action" onclick="deletePackage(<?php echo $package['id']; ?>)" <?php echo !$enable_package_actions ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
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
                    <button class="action-button" onclick="location.href='manage_bookings.php'">
                        Manage Bookings
                    </button>
                    <button class="action-button" onclick="location.href='packages.php'">
                        Manage Packages
                    </button>
                    <button class="action-button" onclick="location.href='view_reports.php'">
                        View Reports
                    </button>
                </div>

                <!-- Add Package Form Section -->
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
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
                            </div>
                            <div class="form-group">
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
                </div>
                <?php endif; ?>

                <!-- Add User Form Section -->
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
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
                    </div>
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

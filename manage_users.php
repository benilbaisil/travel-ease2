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

// Fetch all users
$users_result = $conn->query("SELECT user_id, name, email, user_role FROM users ORDER BY name");
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset and Base Styles */
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

        /* Dashboard Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styles */
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

        /* Main Content Area */
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

        /* Header Section */
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

        /* Form Section */
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
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            font-size: 14px;
        }

        /* Users Table */
        .users-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .users-table th {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #e3e6f0;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        /* Buttons */
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

        .action-buttons {
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

        /* Messages */
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

        /* Responsive Design */
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

            .action-buttons {
                flex-direction: column;
            }

            .users-table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Animations */
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Copy the sidebar from admin_dashboard.php -->
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
                    <h1>Manage Users</h1>
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

                <!-- Add User Form Section -->
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

                <!-- Users List -->
                <div class="users-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <select class="user-role-select" onchange="updateUserRole(<?php echo $user['user_id']; ?>, this.value)">
                                        <option value="Client" <?php echo $user['user_role'] === 'Client' ? 'selected' : ''; ?>>Client</option>
                                        <option value="Staff" <?php echo $user['user_role'] === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="Admin" <?php echo $user['user_role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="delete-btn" onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show/hide form with animation
        document.getElementById('toggleUserForm').addEventListener('click', function() {
            const userForm = document.getElementById('userForm');
            const button = this;
            
            if (userForm.style.display === 'none' || !userForm.style.display) {
                userForm.style.display = 'block';
                userForm.style.opacity = '0';
                userForm.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    userForm.style.transition = 'all 0.3s ease';
                    userForm.style.opacity = '1';
                    userForm.style.transform = 'translateY(0)';
                }, 10);
                
                button.textContent = 'Hide Form';
            } else {
                userForm.style.opacity = '0';
                userForm.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    userForm.style.display = 'none';
                }, 300);
                
                button.textContent = 'Show Form';
            }
        });

        // Add hover effect to table rows
        document.querySelectorAll('.users-table tr').forEach(row => {
            row.addEventListener('mouseover', function() {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'all 0.3s ease';
            });
            
            row.addEventListener('mouseout', function() {
                this.style.transform = 'scale(1)';
            });
        });

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

        function editUser(userId) {
            // Redirect to edit user page
            window.location.href = 'edit_user.php?id=' + userId;
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + userId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting user');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting user. Please try again.');
                });
            }
        }

        function updateUserRole(userId, newRole) {
            fetch('update_user_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + userId + '&role=' + newRole
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Error updating user role');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating user role. Please try again.');
                location.reload();
            });
        }
    </script>
</body>
</html> 
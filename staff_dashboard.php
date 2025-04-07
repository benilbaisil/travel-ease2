<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Add this code block to fetch unread notifications count
$unread_count = 0; // Default value
$sql_notifications = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql_notifications);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $unread_count = $result->fetch_assoc()['count'];
    }
    $stmt->close();
}

// Handle package updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_package'])) {
    $travel_packages_id = $_POST['package_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];

    $sql = "UPDATE packages SET name = ?, description = ?, price = ?, duration = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsi", $name, $description, $price, $duration, $travel_packages_id);
    
    if ($stmt->execute()) {
        $success_message = "Package updated successfully!";
    } else {
        $error_message = "Error updating package: " . $conn->error;
    }
}

// Fetch packages
$sql = "SELECT * FROM travel_packages";
$result = $conn->query($sql);
$travel_packagess = [];
while ($row = $result->fetch_assoc()) {
    $travel_packagess[] = $row;
}

// Fetch users count and details - removing the role check since the column doesn't exist
$sql_users = "SELECT * FROM users";  // Simplified query without WHERE clause
$result_users = $conn->query($sql_users);
$users = [];
$users_count = 0;
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $users_count = count($users);
}

// Replace Python-style function definitions with PHP function definitions
function upload_aadhaar($user_id, $document) {
    // Save the document for verification
    save_document($user_id, $document);
    notify_staff_for_verification($user_id);
}

function verify_aadhaar($user_id) {
    // Staff verifies the document
    mark_document_as_verified($user_id);
    notify_user_for_payment($user_id);
}

function make_payment($user_id) {
    if (is_document_verified($user_id)) {
        process_payment($user_id);
    } else {
        throw new Exception("Document not verified yet.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* Enhanced Modern Design */
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
        color: var(--text-dark);
    }

    /* Enhanced Sidebar Styles */
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
    }

    /* Enhanced Stats Cards */
    .stats-container {
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .stat-card {
        background: var(--white);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
    }

    .stat-card h3 {
        color: var(--text-light);
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-card p {
        color: var(--primary-color);
        font-size: 2rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    /* Improved Table Design */
    .users-list {
        background: var(--white);
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin: 2rem 0;
        overflow: hidden;
    }

    .section-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .users-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .users-table th {
        background-color: var(--bg-light);
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }

    .users-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .users-table tr:hover {
        background-color: rgba(37, 99, 235, 0.05);
    }

    /* Enhanced Buttons */
    .action-button {
        background: var(--primary-color);
        color: var(--white);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        border: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .action-button:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
    }

    .edit-btn {
        background: var(--accent-color);
        color: var(--white);
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        margin-right: 0.5rem;
        transition: all 0.3s ease;
    }

    .delete-btn {
        background: var(--danger-color);
        color: var(--white);
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        transition: all 0.3s ease;
    }

    /* Enhanced Modal Design */
    .modal-content {
        background: var(--white);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .form-group label {
        color: var(--text-dark);
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .dashboard-container {
        animation: fadeIn 0.5s ease-out;
    }

    /* Loading States */
    .loading {
        position: relative;
        opacity: 0.7;
    }

    .loading::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        border: 2px solid var(--primary-color);
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Responsive Design Improvements */
    @media (max-width: 768px) {
        .dashboard-layout {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            position: static;
            height: auto;
        }

        .main-content {
            margin-left: 0;
            padding: 1rem;
        }

        .stats-container {
            grid-template-columns: 1fr;
        }
    }

    /* Improved Layout Styles */
    .dashboard-layout {
        display: flex;
        min-height: 100vh;
    }

    /* Main Content Alignment */
    .main-content {
        flex: 1;
        margin-left: 260px;
        padding: 2rem;
        background-color: var(--bg-light);
        min-height: 100vh;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header Improvements */
    .header {
        margin-bottom: 2rem;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .header h1 {
        font-size: 1.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    /* Stats Container Grid Layout */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Actions Container Layout */
    .actions-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }

    /* Responsive Improvements */
    @media (max-width: 1024px) {
        .sidebar {
            width: 220px;
        }
        
        .main-content {
            margin-left: 220px;
        }
    }

    /* Add these new styles */
    .manage-packages-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin: 2rem 0;
        padding: 2rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .section-header h2 {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-dark);
        margin: 0;
    }

    .package-form {
        background: var(--bg-light);
        border-radius: 10px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .package-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

    .package-content {
        padding: 1.5rem;
    }

    .package-details {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin: 1rem 0;
    }

    .package-details span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-light);
    }

    .package-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .submit-btn, .cancel-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .submit-btn {
        background: var(--primary-color);
        color: white;
    }

    .cancel-btn {
        background: var(--danger-color);
        color: white;
    }

    /* Add this CSS to ensure smooth transitions */
    .package-form {
        transition: opacity 0.3s ease, transform 0.3s ease;
        opacity: 0;
        transform: translateY(-20px);
    }

    /* User Table Styles */
    .users-table-container {
        overflow-x: auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
    }

    .users-table th,
    .users-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--bg-light);
    }

    .users-table th {
        background-color: var(--bg-light);
        font-weight: 600;
        color: var(--text-dark);
    }

    .users-table tr:hover {
        background-color: var(--bg-light);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .user-info i {
        color: var(--text-light);
        font-size: 1.2rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-badge.active {
        background-color: var(--success-color);
        color: white;
    }

    .status-badge.inactive {
        background-color: var(--danger-color);
        color: white;
    }

    @media (max-width: 768px) {
        .users-table-container {
            margin: 0 -1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 0.75rem;
        }
    }

    /* Add these to your existing styles */
    .notification-icon {
        position: relative;
        cursor: pointer;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #ef4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        min-width: 20px;
        text-align: center;
    }

    .notification-dropdown {
        position: absolute;
        right: 0;
        top: 45px;
        width: 320px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
        z-index: 1000;
        display: none;
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
    }

    .notification-header h3 {
        font-weight: 600;
        color: #374151;
    }

    .mark-all-read {
        color: #3b82f6;
        font-size: 0.875rem;
        cursor: pointer;
    }

    .notification-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.2s;
    }

    .notification-item:hover {
        background-color: #f3f4f6;
    }

    .notification-item.unread {
        background-color: #f0f9ff;
    }

    .notification-item p {
        color: #374151;
        font-size: 0.875rem;
        margin-bottom: 4px;
    }

    .notification-time {
        color: #6b7280;
        font-size: 0.75rem;
    }

    .no-notifications {
        padding: 15px;
        text-align: center;
        color: #6b7280;
    }

    /* Add these new styles */
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .notification-wrapper {
        position: relative;
    }

    .notification-bell {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #f3f4f6;
        transition: background-color 0.3s ease;
    }

    .notification-bell:hover {
        background-color: #e5e7eb;
    }

    .notification-bell i {
        font-size: 1.25rem;
        color: #4b5563;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #ef4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        min-width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .notification-dropdown {
        position: absolute;
        right: 0;
        top: 45px;
        width: 320px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
        z-index: 1000;
        display: none;
    }
    </style>
</head>
<body>
    <div class="dashboard-layout">
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
                    <a href="enquiry.php">
                        <i class="fas fa-file-alt"></i>
                        Enquiry
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
        
        <div class="main-content">
            <div class="dashboard-container">
                <div class="header">
                    <div class="header-content">
                        <h1>Staff Dashboard</h1>
                        <div class="notification-wrapper">
                            <button type="button" onclick="toggleNotifications(event)" class="notification-bell">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notificationDropdown" class="notification-dropdown" style="display: none;">
                                <div class="notification-header">
                                    <h3>Notifications</h3>
                                    <?php if ($unread_count > 0): ?>
                                        <a href="mark_all_read.php" class="mark-all-read">Mark all as read</a>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-list">
                                    <?php
                                    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    
                                    if (empty($notifications)): ?>
                                        <p class="no-notifications">No notifications</p>
                                    <?php else:
                                        foreach ($notifications as $notification): ?>
                                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <span class="notification-time">
                                                    <?php echo date('M d, H:i', strtotime($notification['created_at'])); ?>
                                                </span>
                                            </div>
                                        <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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

                <!-- Stats Overview Section -->
                <div class="stats-container">
                    <div class="stat-card">
                        <i class="fas fa-box-open"></i>
                        <h3>Total Packages</h3>
                        <p><?php echo count($travel_packagess); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3>Total Users</h3>
                        <p><?php echo $users_count; ?></p>
                    </div>
                </div>

                Manage Packages Section
                <div class="manage-packages-section">
                    <div class="section-header">
                        <h2><i class="fas fa-box-open"></i> Manage Packages</h2>
                        <button id="togglePackageForm" class="action-button" onclick="window.location.href='packages_view.php'">
                            <i class="fas fa-plus"></i> Add New Package
                        </button>
                    </div>

                    <!-- Package Form (Hidden by default) -->
                    <div id="packageForm" class="package-form" style="display: none;">
                        <form action="manage_packages.php" method="POST" enctype="multipart/form-data" class="add-package-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="package_name">Package Name</label>
                                    <input type="text" id="package_name" name="package_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="destination">Destination</label>
                                    <input type="text" id="destination" name="destination" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="price">Price (₹)</label>
                                    <input type="number" id="price" name="price" min="0" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="duration">Duration (days)</label>
                                    <input type="number" id="duration" name="duration" min="1" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="package_image">Package Image</label>
                                    <input type="file" id="package_image" name="package_image" accept="image/*" required>
                                    <small>Accepted formats: JPG, JPEG, PNG, GIF (Max size: 5MB)</small>
                                </div>
                                <div class="form-actions full-width">
                                    <button type="submit" class="submit-btn">
                                        <i class="fas fa-save"></i> Save Package
                                    </button>
                                    <button type="button" class="cancel-btn" onclick="togglePackageForm()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Packages List -->
                    <div class="packages-grid">
                        <?php foreach ($travel_packagess as $travel_packages): ?>
                        <div class="package-card">
                            <div class="package-image">
                                <?php if (!empty($travel_packages['image_path']) && file_exists($travel_packages['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($travel_packages['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($travel_packages['package_name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="package-content">
                                <h3><?php echo htmlspecialchars($travel_packages['package_name']); ?></h3>
                                <div class="package-details">
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($travel_packages['destination']); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($travel_packages['duration']); ?> days</span>
                                    <span><i class="fas fa-rupee-sign"></i> <?php echo number_format($travel_packages['price'], 2); ?></span>
                                </div>
                                <div class="package-actions">
                                    <!-- <button onclick="editPackage(<?php echo $travel_packages['id']; ?>)" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </button> -->
                                    <!-- <button onclick="deletePackage(<?php echo $travel_packages['id']; ?>)" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button> -->
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Management Section -->
                <div class="manage-packages-section">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> User Management</h2>
                    </div>

                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <!-- <th>Phone</th> -->
                                    <th>Registration Date</th>
                                    <!-- <th>Status</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <i class="fas fa-user-circle"></i>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        
                                        
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Edit Package</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="editForm" method="POST" class="space-y-4">
                <input type="hidden" name="package_id" id="edit_package_id">
                <input type="hidden" name="update_package" value="1">
                
                <div class="form-group">
                    <label for="edit_name">Package Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_price">Price</label>
                        <input type="number" id="edit_price" name="price" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_duration">Duration (days)</label>
                        <input type="number" id="edit_duration" name="duration" required>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <button type="button" onclick="closeEditModal()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-150 flex items-center">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-150 flex items-center">
                        <i class="fas fa-save mr-2"></i> Update Package
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePackageForm() {
            const form = document.getElementById('packageForm');
            const button = document.getElementById('togglePackageForm');
            
            if (form.style.display === 'none') {
                // Show form with animation
                form.style.display = 'block';
                setTimeout(() => {
                    form.style.opacity = '1';
                    form.style.transform = 'translateY(0)';
                }, 10);
                button.textContent = 'Hide Form';
            } else {
                // Hide form with animation
                form.style.opacity = '0';
                form.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    form.style.display = 'none';
                }, 300);
                button.textContent = 'Show Form';
            }
        }

        function openEditModal(package) {
            document.getElementById('edit_package_id').value = package.id || '';
            document.getElementById('edit_name').value = package.package_name || '';
            document.getElementById('edit_description').value = package.description || '';
            document.getElementById('edit_price').value = package.price || '';
            document.getElementById('edit_duration').value = package.duration || '';
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        function deletePackage(packageId) {
            if (confirm('Are you sure you want to delete this package?')) {
                fetch('delete_package.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'package_id=' + packageId
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
            }
        }

        function editPackage(packageId) {
            window.location.href = 'edit_package.php?id=' + packageId;
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

        function validatePackageForm() {
            const packageName = document.getElementById('package_name').value;
            const description = document.getElementById('description').value;
            const price = document.getElementById('price').value;
            const duration = document.getElementById('duration').value;
            const image = document.getElementById('package_image').files[0];

            if (packageName.trim() === '') {
                alert('Please enter a package name');
                return false;
            }

            if (description.trim() === '') {
                alert('Please enter a description');
                return false;
            }

            if (price <= 0) {
                alert('Price must be greater than 0');
                return false;
            }

            if (duration <= 0) {
                alert('Duration must be at least 1 day');
                return false;
            }

            if (image) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!allowedTypes.includes(image.type)) {
                    alert('Please upload an image file (JPG, JPEG, PNG, or GIF)');
                    return false;
                }

                if (image.size > 5 * 1024 * 1024) {
                    alert('Image file size must be less than 5MB');
                    return false;
                }
            }

            return true;
        }

        // Add form submission handler
        document.querySelector('.add-package-form').addEventListener('submit', function(e) {
            if (!validatePackageForm()) {
                e.preventDefault();
                return;
            }

            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
        });

        function toggleNotifications(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
        }

        // Close dropdown when clicking anywhere else on the page
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notificationDropdown');
            const notificationIcon = event.target.closest('.notification-icon');
            
            if (!notificationIcon && dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            }
        });

        // Prevent dropdown from closing when clicking inside it
        document.getElementById('notificationDropdown').addEventListener('click', function(event) {
            event.stopPropagation();
        });
    </script>
</body>
</html>
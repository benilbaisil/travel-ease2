<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'config.php';

// Initialize statistics array
$stats = [
    'total_users' => 0,
    'total_packages' => 0,
    'total_clients' => 0,
    'total_staff' => 0,
    'active_users' => 0,
    'inactive_users' => 0,
    'total_bookings' => 0,
    'confirmed_bookings' => 0,
    'pending_bookings' => 0,
    'cancelled_bookings' => 0,
    'total_revenue' => 0
];

// Fetch user statistics
$user_stats = $conn->query("
    SELECT 
        user_role,
        COUNT(*) as count
    FROM users 
    GROUP BY user_role
");

while ($row = $user_stats->fetch_assoc()) {
    if ($row['user_role'] === 'Client') {
        $stats['total_clients'] += $row['count'];
    } elseif ($row['user_role'] === 'Staff') {
        $stats['total_staff'] += $row['count'];
    }
    $stats['total_users'] += $row['count'];
}

// Set active users to total users since active column might not exist
$stats['active_users'] = $stats['total_users'];
$stats['inactive_users'] = 0;

// Fetch total packages count
$package_count = $conn->query("SELECT COUNT(*) as total FROM travel_packages");
$stats['total_packages'] = $package_count->fetch_assoc()['total'];

// Modify the query to only include clients
$users_query = $conn->query("
    SELECT 
        name,
        email,
        user_role
    FROM users
    WHERE user_role = 'Client'
    ORDER BY name
");

$users = [];
while ($row = $users_query->fetch_assoc()) {
    $users[] = $row;
}

// Modify the bookings query to use the correct column names
$bookings_query = $conn->query("
    SELECT 
        b.id,  
        b.created_at,
        b.booking_status,
        u.email,
        u.name as user_name,
        tp.package_name,
        tp.price
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN travel_packages tp ON b.package_id = tp.id
    WHERE u.user_role = 'Client'
");

$bookings = [];
while ($row = $bookings_query->fetch_assoc()) {
    $bookings[$row['email']] = $bookings[$row['email']] ?? [];
    $bookings[$row['email']][] = $row;
}

// Get booking statistics
$booking_stats = $conn->query("
    SELECT 
        booking_status,
        COUNT(*) as count
    FROM bookings 
    GROUP BY booking_status
");

while ($row = $booking_stats->fetch_assoc()) {
    $stats['total_bookings'] += $row['count'];
    
    if (strtolower($row['booking_status']) === 'confirmed') {
        $stats['confirmed_bookings'] = $row['count'];
    } else if (strtolower($row['booking_status']) === 'pending') {
        $stats['pending_bookings'] = $row['count'];
    } else if (strtolower($row['booking_status']) === 'cancelled') {
        $stats['cancelled_bookings'] = $row['count'];
    }
}

// Calculate total revenue from confirmed bookings
$revenue_query = $conn->query("
    SELECT 
        SUM(tp.price) as total_revenue
    FROM bookings b
    JOIN travel_packages tp ON b.package_id = tp.id
    WHERE b.booking_status = 'Confirmed'
");

$revenue_result = $revenue_query->fetch_assoc();
$stats['total_revenue'] = $revenue_result['total_revenue'] ?: 0;

// Get popular packages (most booked)
$popular_packages_query = $conn->query("
    SELECT 
        tp.id,
        tp.package_name,
        tp.description,
        tp.price,
        tp.duration,
        COUNT(b.id) as booking_count,
        SUM(CASE WHEN b.booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN b.booking_status = 'Confirmed' THEN tp.price ELSE 0 END) as total_revenue
    FROM travel_packages tp
    LEFT JOIN bookings b ON tp.id = b.package_id
    GROUP BY tp.id
    ORDER BY booking_count DESC
");

$package_stats = [];
$total_package_revenue = 0;
while ($row = $popular_packages_query->fetch_assoc()) {
    $package_stats[] = $row;
    $total_package_revenue += $row['total_revenue'];
}

// Calculate average package price
$avg_price_query = $conn->query("SELECT AVG(price) as avg_price FROM travel_packages");
$avg_price = $avg_price_query->fetch_assoc()['avg_price'];

// Get bookings by month for the current year
$current_year = date('Y');
$monthly_bookings_query = $conn->query("
    SELECT 
        MONTH(b.created_at) as month,
        COUNT(*) as count,
        SUM(tp.price) as revenue
    FROM bookings b
    JOIN travel_packages tp ON b.package_id = tp.id
    WHERE YEAR(b.created_at) = '$current_year'
    AND b.booking_status = 'Confirmed'
    GROUP BY MONTH(b.created_at)
    ORDER BY MONTH(b.created_at)
");

$monthly_data = [
    'labels' => [],
    'bookings' => [],
    'revenue' => []
];

for ($i = 1; $i <= 12; $i++) {
    $monthly_data['labels'][] = date("M", mktime(0, 0, 0, $i, 1));
    $monthly_data['bookings'][] = 0;
    $monthly_data['revenue'][] = 0;
}

while ($row = $monthly_bookings_query->fetch_assoc()) {
    $month_index = (int)$row['month'] - 1;
    $monthly_data['bookings'][$month_index] = (int)$row['count'];
    $monthly_data['revenue'][$month_index] = (float)$row['revenue'];
}

// Add this PHP code after the existing queries
$date_filtered_packages_query = $conn->prepare("
    SELECT 
        tp.id,
        tp.package_name,
        tp.price,
        tp.duration,
        COUNT(b.id) as booking_count,
        SUM(CASE WHEN b.booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN b.booking_status = 'Confirmed' THEN tp.price ELSE 0 END) as total_revenue,
        MIN(b.created_at) as first_booking,
        MAX(b.created_at) as last_booking
    FROM travel_packages tp
    LEFT JOIN bookings b ON tp.id = b.package_id
    WHERE b.created_at BETWEEN ? AND ?
    GROUP BY tp.id
    ORDER BY booking_count DESC
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
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

        .logout-btn {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            margin: 0.3rem 1rem;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .logout-btn i {
            width: 20px;
            margin-right: 12px;
            font-size: 1.2rem;
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
            padding: 0 20px;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header h1 {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 24px;
            margin: 0;
        }

        .export-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .reports-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .report-card h3 {
            margin: 0 0 20px 0;
            color: var(--dark-color);
            font-size: 18px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        .report-card h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border-radius: 3px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: scale(1.05);
        }

        .stat-item .number {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .stat-item .label {
            color: var(--secondary-color);
            font-size: 14px;
            font-weight: 500;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .chart-container h3 {
            margin: 0 0 20px 0;
            color: var(--dark-color);
            font-size: 18px;
            font-weight: 600;
        }

        canvas {
            max-width: 100%;
            height: 300px !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .reports-container {
                grid-template-columns: 1fr;
            }

            .stat-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced Card Styling */
        .report-card {
            background: linear-gradient(145deg, #ffffff, #f5f7fa);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.05),
                       -5px -5px 15px rgba(255, 255, 255, 0.8);
            border: none;
        }

        /* Enhanced Stats Grid */
        .stat-item {
            background: linear-gradient(145deg, #ffffff, #f5f7fa);
            border-radius: 15px;
            padding: 25px 15px;
            box-shadow: 3px 3px 8px rgba(0, 0, 0, 0.05),
                       -3px -3px 8px rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }

        .stat-item .number {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            display: inline-block;
        }

        .stat-item .label {
            color: #555;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Enhanced Header */
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 45%, rgba(255,255,255,0.1) 45%, rgba(255,255,255,0.1) 55%, transparent 55%);
        }

        .header h1 {
            font-size: 28px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        /* Enhanced Export Button */
        .export-btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
            margin-bottom: 35px;
        }

        .export-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
        }

        /* Enhanced Chart Container */
        .chart-container {
            background: linear-gradient(145deg, #ffffff, #f5f7fa);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.05),
                       -5px -5px 15px rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
        }

        .chart-container h3 {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3498db;
            display: inline-block;
        }

        /* Add animation classes */
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 25px;
                margin-bottom: 30px;
            }

            .header h1 {
                font-size: 24px;
            }

            .stat-item .number {
                font-size: 28px;
            }

            .stat-item .label {
                font-size: 14px;
            }
        }

        .footer-logout {
            margin-top: 30px;
            padding: 30px;
            background: linear-gradient(145deg, #ffffff, #f5f7fa);
            border-radius: 20px;
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.05),
                       -5px -5px 15px rgba(255, 255, 255, 0.8);
            text-align: center;
        }

        /* .footer-logout-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        } */
/* 
        .footer-logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }

        .footer-logout-btn i {
            font-size: 18px;
        } */

        .user-cell {
            padding: 8px 15px !important;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .user-name {
            font-weight: 500;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .users-table tr {
            transition: all 0.3s ease;
        }

        .users-table tr:hover {
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .role-badge::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .role-badge.admin {
            background-color: #fecaca;
            color: #dc2626;
        }

        .role-badge.staff {
            background-color: #bbf7d0;
            color: #16a34a;
        }

        .role-badge.client {
            background-color: #fef08a;
            color: #ca8a04;
        }

        .users-table th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
        }

        .users-table td {
            padding: 15px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .user-avatar {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }

            .user-name {
                font-size: 0.9rem;
            }

            .role-badge {
                padding: 4px 8px;
                font-size: 0.8rem;
            }
        }

        /* Popular Packages Table */
        .popular-packages-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .popular-packages-table th,
        .popular-packages-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .popular-packages-table th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .popular-packages-table tr:hover {
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .popularity-bar-container {
            width: 100%;
            background-color: #f1f5f9;
            border-radius: 20px;
            height: 12px;
            position: relative;
        }
        
        .popularity-bar {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #8e44ad);
            border-radius: 20px;
            transition: width 1s ease-in-out;
        }
        
        .popularity-text {
            position: absolute;
            right: -30px;
            top: -3px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }
        
        .no-packages {
            text-align: center;
            padding: 20px;
            color: #64748b;
        }

        /* View Bookings Button */
        .view-bookings-btn {
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .view-bookings-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Bookings Modal */
        .bookings-modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .bookings-modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 900px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            color: #64748b;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close-modal:hover {
            color: #1e293b;
            transform: scale(1.1);
        }
        
        #bookingsModalTitle {
            color: #1e293b;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 12px;
            margin-bottom: 25px;
            font-size: 1.8rem;
            display: inline-block;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .bookings-table th,
        .bookings-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .bookings-table th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .bookings-table tr:hover {
            background-color: #f8fafc;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge.confirmed {
            background-color: #bbf7d0;
            color: #16a34a;
        }
        
        .status-badge.pending {
            background-color: #fef08a;
            color: #ca8a04;
        }
        
        .status-badge.cancelled {
            background-color: #fecaca;
            color: #dc2626;
        }
        
        .no-bookings {
            text-align: center;
            padding: 30px;
            color: #64748b;
            font-size: 1.1rem;
        }

        /* Add these new classes for controlling card widths */
        .full-width-card {
            grid-column: 1 / -1; /* Make the card span the full width */
        }

        .half-width-card {
            grid-column: span 2; /* Make the card span 2 columns if possible */
        }

        @media (max-width: 768px) {
            .half-width-card {
                grid-column: 1 / -1; /* On mobile, make half-width cards full width */
            }
        }

        .payment-history-table {
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .payment-history-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-history-table th {
            background: linear-gradient(135deg, #1f2937, #111827);
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .payment-history-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .payment-history-table tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge.success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.failed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .payment-history-table {
                overflow-x: auto;
            }

            .user-info {
                flex-direction: column;
                gap: 5px;
            }

            .status-badge {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }

        .payment-summary {
            background: white;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .payment-summary h4 {
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .stat-item {
            padding: 10px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .stat-item strong {
            color: #374151;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .toggle-btn {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .toggle-btn i {
            font-size: 0.9rem;
        }

        #paymentSection {
            transition: all 0.3s ease;
        }

        #paymentSection.hidden {
            display: none;
        }
        </style>
    </head>
    <body>
        <div class="dashboard-layout">
            <!-- Sidebar -->
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
                        <a href="view_reports.php" class="active">
                            <i class="fas fa-chart-bar"></i>
                            Reports
                        </a>
                    </li>
                    <li>
                        <form action="logout.php" method="POST">
                            <button type="submit" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>

            <div class="main-content">
                <div class="dashboard-container">
                    <div class="header">
                        <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
                    </div>

                    <button class="export-btn" onclick="exportReport()">
                        <i class="fas fa-download"></i> Export as Text
                    </button>
                    <button class="export-btn" style="background: linear-gradient(135deg, #8e44ad, #9b59b6);" onclick="exportReportPDF()">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </button>

                    <div class="reports-container fade-in">
                        <!-- Users Statistics -->
                        <div class="report-card">
                            <h3>
                                <i class="fas fa-users" style="color: #3498db; margin-right: 10px;"></i>
                                User Statistics
                            </h3>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="number"><?php echo $stats['total_users']; ?></div>
                                    <div class="label">Total Users</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number"><?php echo $stats['total_clients']; ?></div>
                                    <div class="label">Total Clients</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number"><?php echo $stats['total_staff']; ?></div>
                                    <div class="label">Total Staff</div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Statistics -->
                        <div class="report-card">
                            <h3>
                                <i class="fas fa-box" style="color: #2ecc71; margin-right: 10px;"></i>
                                Package Statistics
                            </h3>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="number"><?php echo $stats['total_packages']; ?></div>
                                    <div class="label">Total Packages</div>
                                </div>
                            </div>
                        </div>

                        <!-- User Details -->
                        <div class="report-card users-report fade-in full-width-card">
                            <h3>
                                <i class="fas fa-users" style="color: #8b5cf6; margin-right: 10px;"></i>
                                User Details
                            </h3>
                            <div class="users-table-container">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="4" class="no-users">No users found</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td class="user-cell">
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                        </div>
                                                        <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                            <span class="role-badge <?php echo strtolower($user['user_role']); ?>">
                                                                <?php echo htmlspecialchars($user['user_role']); ?>
                                                            </span>
                                                        </td>
                                                <td>
                                                    <button class="view-bookings-btn" data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                                        <i class="fas fa-calendar-check"></i> View Bookings
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Booking Statistics -->
                        <div class="report-card half-width-card">
                            <h3>
                                <i class="fas fa-calendar-check" style="color: #e74c3c; margin-right: 10px;"></i>
                                Booking Statistics
                            </h3>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="number"><?php echo $stats['total_bookings']; ?></div>
                                    <div class="label">Total Bookings</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number"><?php echo $stats['confirmed_bookings']; ?></div>
                                    <div class="label">Confirmed</div>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Statistics -->
                        <div class="report-card half-width-card">
                            <h3>
                                <i class="fas fa-rupee-sign" style="color: #2ecc71; margin-right: 10px;"></i>
                                Revenue Statistics
                            </h3>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="number">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                                    <div class="label">Total Revenue</div>
                                </div>
                                <div class="stat-item">
                                    <div class="number">₹<?php 
                                        echo $stats['confirmed_bookings'] > 0 ? 
                                            number_format($stats['total_revenue'] / $stats['confirmed_bookings'], 2) : 
                                            '0.00'; 
                                ?></div>
                                    <div class="label">Avg. Booking Value</div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Details -->
                        <div class="report-card full-width-card">
                            <h3>
                                <i class="fas fa-box" style="color: #8b5cf6; margin-right: 10px;"></i>
                                Package Details
                            </h3>
                            <div class="export-buttons" style="margin-bottom: 20px;">
                                <button class="export-btn" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);" onclick="exportPackagesReportPDF()">
                                    <i class="fas fa-file-pdf"></i> Export All Packages
                                </button>
                                <div class="date-range-export" style="display: inline-block; margin-left: 15px;">
                                    <input type="date" id="startDate" class="date-input" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; margin-right: 5px;">
                                    <input type="date" id="endDate" class="date-input" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; margin-right: 5px;">
                                    <button class="export-btn" style="background: linear-gradient(135deg, #1f2937, #111827);" onclick="exportPackagesByDateRange()">
                                        <i class="fas fa-calendar-alt"></i> Export by Date Range
                                    </button>
                                </div>
                            </div>
                            <div class="package-stats-summary">
                                <div class="stat-grid">
                                    <div class="stat-item">
                                        <div class="number"><?php echo $stats['total_packages']; ?></div>
                                        <div class="label">Total Packages</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="number">₹<?php echo number_format($avg_price, 2); ?></div>
                                        <div class="label">Average Price</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="number">₹<?php echo number_format($total_package_revenue, 2); ?></div>
                                        <div class="label">Total Revenue</div>
                                    </div>
                                </div>
                            </div>
                            <div class="package-table-container" style="margin-top: 20px;">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>Package Name</th>
                                            <th>Price</th>
                                            <th>Duration</th>
                                            <th>Total Bookings</th>
                                            <th>Confirmed Bookings</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($package_stats)): ?>
                                        <tr>
                                            <td colspan="6" class="no-packages">No packages found</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($package_stats as $package): ?>
                                            <tr>
                                                <td>
                                                    <div class="package-info">
                                                        <span class="package-name"><?php echo htmlspecialchars($package['package_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td>₹<?php echo number_format($package['price'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($package['duration']); ?></td>
                                                <td><?php echo $package['booking_count']; ?></td>
                                                <td><?php echo $package['confirmed_bookings']; ?></td>
                                                <td>₹<?php echo number_format($package['total_revenue'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="report-card full-width-card">
                            <div class="section-header">
                                <h3>
                                    <i class="fas fa-history" style="color: #10b981; margin-right: 10px;"></i>
                                    Payment History
                                </h3>
                                <button class="toggle-btn" onclick="togglePaymentSection()" id="togglePaymentBtn">
                                    <i class="fas fa-eye-slash"></i> Hide
                                </button>
                            </div>
                            <div id="paymentSection">
                                <div class="export-buttons" style="margin-bottom: 20px;">
                                    <div class="date-range-export">
                                        <input type="date" id="paymentStartDate" class="date-input" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; margin-right: 5px;">
                                        <input type="date" id="paymentEndDate" class="date-input" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; margin-right: 5px;">
                                        <button class="export-btn" style="background: linear-gradient(135deg, #059669, #047857);" onclick="exportPaymentsByDateRange()">
                                            <i class="fas fa-file-invoice-dollar"></i> Export Payment History
                                        </button>
                                    </div>
                                </div>

                                <?php
                                // Fetch payment history without date filtering
                                $paymentQuery = "
                                    SELECT 
                                        p.id as payment_id,
                                        p.amount,
                                        p.mode as payment_method,
                                        p.status as payment_status,
                                        p.transaction_id,
                                        p.created_at as payment_date,
                                        b.id as booking_id,
                                        b.booking_status,
                                        b.travel_date,
                                        u.name as user_name,
                                        u.email as user_email,
                                        tp.package_name,
                                        tp.price as package_price
                                    FROM payment p
                                    JOIN bookings b ON p.booking_id = b.id
                                    JOIN users u ON b.user_id = u.user_id
                                    JOIN travel_packages tp ON b.package_id = tp.id
                                    ORDER BY p.created_at DESC
                                ";

                                $paymentResult = $conn->query($paymentQuery);

                                // Calculate summary statistics
                                $totalPayments = 0;
                                $totalAmount = 0;
                                $successfulPayments = 0;
                                $failedPayments = 0;
                                $uniqueUsers = array();
                                $payments = array();

                                if ($paymentResult && $paymentResult->num_rows > 0) {
                                    while ($row = $paymentResult->fetch_assoc()) {
                                        $payments[] = $row;
                                        $totalPayments++;
                                        $totalAmount += $row['amount'];
                                        
                                        if (strtolower($row['payment_status']) === 'success') {
                                            $successfulPayments++;
                                        } elseif (strtolower($row['payment_status']) === 'failed') {
                                            $failedPayments++;
                                        }
                                        
                                        $uniqueUsers[$row['user_email']] = true;
                                    }

                                    // Display Summary Statistics
                                    echo '<div class="payment-summary" style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px;">';
                                    echo '<h4 style="margin-bottom: 15px;">Payment Summary</h4>';
                                    echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
                                    echo '<div class="stat-item"><strong>Total Payments:</strong> ' . $totalPayments . '</div>';
                                    echo '<div class="stat-item"><strong>Total Amount:</strong> ₹' . number_format($totalAmount, 2) . '</div>';
                                    echo '<div class="stat-item"><strong>Successful Payments:</strong> ' . $successfulPayments . '</div>';
                                    echo '<div class="stat-item"><strong>Failed Payments:</strong> ' . $failedPayments . '</div>';
                                    echo '<div class="stat-item"><strong>Unique Users:</strong> ' . count($uniqueUsers) . '</div>';
                                    echo '</div>';
                                    echo '</div>';

                                    // Display Payment History Table
                                    echo '<div class="payment-history-table" style="overflow-x: auto;">';
                                    echo '<table class="users-table" style="width: 100%;">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Date</th>';
                                    echo '<th>Transaction ID</th>';
                                    echo '<th>Customer</th>';
                                    echo '<th>Package</th>';
                                    echo '<th>Travel Date</th>';
                                    echo '<th>Amount</th>';
                                    echo '<th>Payment Method</th>';
                                    echo '<th>Status</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';

                                    foreach ($payments as $row) {
                                        $statusClass = strtolower($row['payment_status']);
                                        $paymentDate = new DateTime($row['payment_date']);
                                        $travelDate = new DateTime($row['travel_date']);

                                        echo '<tr>';
                                        echo '<td>' . $paymentDate->format('d M Y, h:i A') . '</td>';
                                        echo '<td>' . htmlspecialchars($row['transaction_id']) . '</td>';
                                        echo '<td>';
                                        echo '<div class="user-info">';
                                        echo '<div class="user-avatar">' . strtoupper(substr($row['user_name'], 0, 1)) . '</div>';
                                        echo '<div>';
                                        echo '<div class="user-name">' . htmlspecialchars($row['user_name']) . '</div>';
                                        echo '<div style="font-size: 0.8em; color: #666;">' . htmlspecialchars($row['user_email']) . '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '<td>' . htmlspecialchars($row['package_name']) . '</td>';
                                        echo '<td>' . $travelDate->format('d M Y') . '</td>';
                                        echo '<td>₹' . number_format($row['amount'], 2) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                                        echo '<td><span class="status-badge ' . $statusClass . '">' . $row['payment_status'] . '</span></td>';
                                        echo '</tr>';
                                    }

                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="no-data-message" style="text-align: center; padding: 20px; color: #666;">';
                                    echo '<i class="fas fa-info-circle"></i> No payment records found in the database.<br>';
                                    if ($conn->error) {
                                        echo 'Database Error: ' . $conn->error . '<br>';
                                    }
                                    echo '</div>';
                                }
                                ?>

                                <style>
                                .payment-summary {
                                    background: white;
                                    border: 1px solid #e5e7eb;
                                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                                }
                                
                                .payment-summary h4 {
                                    color: #1f2937;
                                    font-size: 1.1rem;
                                    font-weight: 600;
                                }
                                
                                .stat-item {
                                    padding: 10px;
                                    background: white;
                                    border-radius: 6px;
                                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                                }
                                
                                .stat-item strong {
                                    color: #374151;
                                }

                                .status-badge {
                                    padding: 6px 12px;
                                    border-radius: 20px;
                                    font-size: 0.85rem;
                                    font-weight: 600;
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 6px;
                                }

                                .status-badge.success {
                                    background-color: #d1fae5;
                                    color: #065f46;
                                }

                                .status-badge.pending {
                                    background-color: #fef3c7;
                                    color: #92400e;
                                }

                                .status-badge.failed {
                                    background-color: #fee2e2;
                                    color: #991b1b;
                                }
                                </style>
                            </div>

                            <script>
                            function togglePaymentSection() {
                                const section = document.getElementById('paymentSection');
                                const button = document.getElementById('togglePaymentBtn');
                                
                                if (section.classList.contains('hidden')) {
                                    section.classList.remove('hidden');
                                    button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
                                } else {
                                    section.classList.add('hidden');
                                    button.innerHTML = '<i class="fas fa-eye"></i> Show';
                                }
                            }
                            </script>
                        </div>

                        <!-- Package Performance Chart -->
                        <div class="chart-container full-width-card">
                            <h3><i class="fas fa-chart-bar"></i> Package Performance</h3>
                            <canvas id="packageChart"></canvas>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-pie"></i> User Distribution</h3>
                        <canvas id="userChart"></canvas>
                    </div>

                    <!-- New Logout Section -->
                    <div class="footer-logout fade-in">
                        <!-- <form action="logout.php" method="POST">
                            <button type="submit" class="footer-logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout from Admin Panel</span>
                            </button>
                        </form> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- User Bookings Modal -->
        <div id="bookingsModal" class="bookings-modal">
            <div class="bookings-modal-content">
                <span class="close-modal">&times;</span>
                <h2 id="bookingsModalTitle">User Bookings</h2>
                <button id="downloadUserBookingsPDF" class="export-btn" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); margin-bottom: 20px;">
                    <i class="fas fa-file-pdf"></i> Download User Report
                </button>
                <div id="bookingsModalContent">
                    <!-- Bookings will be loaded here -->
                </div>
            </div>
        </div>

        <script>
        // User Distribution Chart with enhanced styling
        new Chart(document.getElementById('userChart'), {
            type: 'pie',
            data: {
                labels: ['Clients', 'Staff'],
                datasets: [{
                    data: [
                        <?php echo $stats['total_clients']; ?>,
                        <?php echo $stats['total_staff']; ?>
                    ],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14,
                                weight: '600'
                            },
                            color: '#2c3e50'
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Export Report Function
        function exportReport() {
            const date = new Date().toLocaleDateString().replace(/\//g, '-');
            const stats = {
                totalUsers: <?php echo $stats['total_users']; ?>,
                totalClients: <?php echo $stats['total_clients']; ?>,
                totalStaff: <?php echo $stats['total_staff']; ?>,
                totalPackages: <?php echo $stats['total_packages']; ?>,
                totalBookings: <?php echo $stats['total_bookings']; ?>,
                confirmedBookings: <?php echo $stats['confirmed_bookings']; ?>,
                pendingBookings: <?php echo $stats['pending_bookings']; ?>,
                cancelledBookings: <?php echo $stats['cancelled_bookings']; ?>,
                totalRevenue: <?php echo $stats['total_revenue']; ?>
            };

            let reportContent = `Travel Ease System Report (${date})\n\n`;
            
            reportContent += `USER STATISTICS:\n`;
            reportContent += `- Total Users: ${stats.totalUsers}\n`;
            reportContent += `- Total Clients: ${stats.totalClients}\n`;
            reportContent += `- Total Staff: ${stats.totalStaff}\n\n`;
            
            reportContent += `PACKAGE STATISTICS:\n`;
            reportContent += `- Total Packages: ${stats.totalPackages}\n\n`;
            
            reportContent += `BOOKING STATISTICS:\n`;
            reportContent += `- Total Bookings: ${stats.totalBookings}\n`;
            reportContent += `- Confirmed Bookings: ${stats.confirmedBookings}\n`;
            reportContent += `- Pending Bookings: ${stats.pendingBookings}\n`;
            reportContent += `- Cancelled Bookings: ${stats.cancelledBookings}\n\n`;
            
            reportContent += `REVENUE STATISTICS:\n`;
            reportContent += `- Total Revenue: ₹${stats.totalRevenue.toFixed(2)}\n`;
            if (stats.confirmedBookings > 0) {
                reportContent += `- Average Booking Value: ₹${(stats.totalRevenue / stats.confirmedBookings).toFixed(2)}\n`;
            }

            const blob = new Blob([reportContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `travel-ease-report-${date}.txt`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // User Bookings Modal
        const modal = document.getElementById('bookingsModal');
        const modalTitle = document.getElementById('bookingsModalTitle');
        const modalContent = document.getElementById('bookingsModalContent');
        const closeModal = document.querySelector('.close-modal');
        const viewBookingsBtns = document.querySelectorAll('.view-bookings-btn');
        const downloadUserBookingsPDF = document.getElementById('downloadUserBookingsPDF');

        // Store bookings data in JavaScript
        const bookingsData = <?php echo json_encode($bookings); ?>;
        
        // Track current user data for PDF download
        let currentUserData = {
            name: '',
            email: '',
            bookings: []
        };

        // Open modal when view bookings button is clicked
        viewBookingsBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const email = this.getAttribute('data-email');
                const userBookings = bookingsData[email] || [];
                
                // Find the user name
                const userName = this.closest('tr').querySelector('.user-name').textContent;
                
                // Store current user data for PDF download
                currentUserData = {
                    name: userName,
                    email: email,
                    bookings: userBookings
                };
                
                // Set modal title
                modalTitle.textContent = `Bookings for ${userName}`;
                
                // Generate bookings table
                let content = '';
                
                if (userBookings.length === 0) {
                    content = '<div class="no-bookings">No bookings found for this user</div>';
                } else {
                    content = `
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Package</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    userBookings.forEach(booking => {
                        const date = new Date(booking.created_at);
                        const formattedDate = date.toLocaleDateString('en-US', {
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric'
                        });
                        
                        const statusClass = booking.booking_status.toLowerCase();
                        
                        content += `
                            <tr>
                                <td>#${booking.id}</td>
                                <td>${booking.package_name}</td>
                                <td>${formattedDate}</td>
                                <td>₹${parseFloat(booking.price).toFixed(2)}</td>
                                <td>
                                    <span class="status-badge ${statusClass}">
                                        ${booking.booking_status}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });
                    
                    content += `
                            </tbody>
                        </table>
                    `;
                }
                
                modalContent.innerHTML = content;
                modal.style.display = 'block';
            });
        });
        
        // Download User Bookings as PDF
        downloadUserBookingsPDF.addEventListener('click', function() {
            generateUserBookingsPDF(currentUserData);
        });

        // Function to generate PDF for user bookings
        function generateUserBookingsPDF(userData) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const date = new Date().toLocaleDateString().replace(/\//g, '-');
            
            // Add College Header
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text("Travel Ease", 105, 20, { align: "center" });
            doc.setFontSize(12);
            doc.text("Koovappally- 686518, Kanjirappally", 105, 30, { align: "center" });
            
            // Add horizontal line
            doc.setLineWidth(0.5);
            doc.line(15, 35, 195, 35);
            
            // Add Report Title
            doc.setFontSize(14);
            doc.text("USER REPORT", 105, 45, { align: "center" });
            
            // Add Report Details
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const startY = 60;
            const labelX = 20;
            const valueX = 80;
            const lineSpacing = 8;
            
            doc.text("Report Date", labelX, startY);
            doc.text(`: ${date}`, valueX, startY);
            
            doc.text("Generated By", labelX, startY + lineSpacing);
            doc.text(": Admin", valueX, startY + lineSpacing);
            
            doc.text("System", labelX, startY + lineSpacing * 2);
            doc.text(": Travel Ease Management", valueX, startY + lineSpacing * 2);
            
            // Add line before user info
            doc.line(15, startY + lineSpacing * 3, 195, startY + lineSpacing * 3);
            
            // User Information Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text("User Information", 20, startY + lineSpacing * 4);
            
            const userInfo = [
                ["Name", userData.name],
                ["Email", userData.email]
            ];
            
            doc.autoTable({
                startY: startY + lineSpacing * 5,
                head: [["Field", "Value"]],
                body: userInfo,
                theme: 'grid',
                headStyles: { 
                    fillColor: [169, 169, 169],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                }
            });
            
            // Booking Summary Section
            if (userData.bookings.length > 0) {
                // Calculate summary statistics
                let totalSpent = 0;
                let confirmedBookings = 0;
                let pendingBookings = 0;
                let cancelledBookings = 0;
                
                userData.bookings.forEach(booking => {
                    if (booking.booking_status.toLowerCase() === 'confirmed') {
                        totalSpent += parseFloat(booking.price);
                        confirmedBookings++;
                    } else if (booking.booking_status.toLowerCase() === 'pending') {
                        pendingBookings++;
                    } else if (booking.booking_status.toLowerCase() === 'cancelled') {
                        cancelledBookings++;
                    }
                });
                
                doc.setFontSize(12);
                doc.setFont('helvetica', 'bold');
                doc.text("Booking Summary", 20, doc.lastAutoTable.finalY + 15);
                
                const bookingSummary = [
                    ["Total Bookings", userData.bookings.length.toString()],
                    ["Confirmed Bookings", confirmedBookings.toString()],
                    ["Pending Bookings", pendingBookings.toString()],
                    ["Cancelled Bookings", cancelledBookings.toString()],
                    ["Total Amount", `Rs.${formatNumber(totalSpent)}`]
                ];
                
                doc.autoTable({
                    startY: doc.lastAutoTable.finalY + 5,
                    head: [["Metric", "Value"]],
                    body: bookingSummary,
                    theme: 'grid',
                    headStyles: { 
                        fillColor: [169, 169, 169],
                        textColor: [0, 0, 0],
                        fontStyle: 'bold'
                    },
                    styles: {
                        fontSize: 10,
                        cellPadding: 5
                    }
                });
                
                // Booking Details Table
                doc.setFontSize(12);
                doc.setFont('helvetica', 'bold');
                doc.text("Booking Details", 20, doc.lastAutoTable.finalY + 15);
                
                const bookingDetails = userData.bookings.map(booking => {
                    const date = new Date(booking.created_at);
                    const formattedDate = date.toLocaleDateString('en-US', {
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric'
                    });
                    
                    return [
                        `#${booking.id}`,
                        booking.package_name,
                        formattedDate,
                        `Rs.${formatNumber(booking.price)}`,
                        booking.booking_status
                    ];
                });
                
                doc.autoTable({
                    startY: doc.lastAutoTable.finalY + 5,
                    head: [["ID", "Package", "Date", "Amount", "Status"]],
                    body: bookingDetails,
                    theme: 'grid',
                    headStyles: { 
                        fillColor: [169, 169, 169],
                        textColor: [0, 0, 0],
                        fontStyle: 'bold'
                    },
                    styles: {
                        fontSize: 10,
                        cellPadding: 5
                    },
                    columnStyles: {
                        0: { cellWidth: 20 },
                        1: { cellWidth: 60 },
                        2: { cellWidth: 35 },
                        3: { cellWidth: 35 },
                        4: { cellWidth: 30 }
                    }
                });
            } else {
                doc.setFontSize(11);
                doc.setFont('helvetica', 'normal');
                doc.text("No bookings found for this user", 20, doc.lastAutoTable.finalY + 20);
            }
            
            // Add footer line
            doc.setLineWidth(0.5);
            doc.line(15, 275, 195, 275);
            
            // Add footer
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text("Travel Ease System Report", 20, 280);
            doc.text(`Generated on: ${date}`, 105, 280, { align: "center" });
            doc.text("Page 1", 190, 280);
            
            // Save the PDF
            doc.save(`travel-ease-user-report-${userData.name.replace(/\s+/g, '-')}-${date}.pdf`);
        }

        // Add number formatting function
        function formatNumber(number, decimals = 2) {
            const parts = String(number).split('.');
            const whole = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (decimals === 0) return whole;
            const decimal = parts[1] || '0';
            return whole + '.' + decimal.padEnd(decimals, '0').slice(0, decimals);
        }

        // Close modal when close button is clicked
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // PDF Export Function
        function exportReportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const date = new Date().toLocaleDateString().replace(/\//g, '-');
            
            // Add College Header
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text("Travel Ease", 105, 20, { align: "center" });
            doc.setFontSize(12);
            doc.text("Koovappally- 686518, Kanjirappally", 105, 30, { align: "center" });
            
            // Add horizontal line
            doc.setLineWidth(0.5);
            doc.line(15, 35, 195, 35);
            
            // Add Report Title
            doc.setFontSize(14);
            doc.text("SYSTEM REPORT", 105, 45, { align: "center" });
            
            // Add Report Details
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const startY = 60;
            const labelX = 20;
            const valueX = 80;
            const lineSpacing = 8;
            
            doc.text("Report Date", labelX, startY);
            doc.text(`: ${date}`, valueX, startY);
            
            doc.text("Generated By", labelX, startY + lineSpacing);
            doc.text(": Admin", valueX, startY + lineSpacing);
            
            doc.text("System", labelX, startY + lineSpacing * 2);
            doc.text(": Travel Ease Management", valueX, startY + lineSpacing * 2);
            
            // Add line before statistics
            doc.line(15, startY + lineSpacing * 3, 195, startY + lineSpacing * 3);
            
            // User Statistics Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text("User Statistics", 20, startY + lineSpacing * 4);
            
            const userStats = [
                ["Total Users", "<?php echo $stats['total_users']; ?>"],
                ["Total Clients", "<?php echo $stats['total_clients']; ?>"],
                ["Total Staff", "<?php echo $stats['total_staff']; ?>"]
            ];
            
            doc.autoTable({
                startY: startY + lineSpacing * 5,
                head: [["Metric", "Value"]],
                body: userStats,
                theme: 'grid',
                headStyles: { 
                    fillColor: [169, 169, 169],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                }
            });
            
            // Package Statistics Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text("Package Statistics", 20, doc.lastAutoTable.finalY + 15);
            
            const packageStats = [
                ["Total Packages", "<?php echo $stats['total_packages']; ?>"],
                ["Average Package Price", "Rs.<?php echo number_format($avg_price, 2); ?>"],
                ["Total Package Revenue", "Rs.<?php echo number_format($total_package_revenue, 2); ?>"]
            ];
            
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 5,
                head: [["Metric", "Value"]],
                body: packageStats,
                theme: 'grid',
                headStyles: { 
                    fillColor: [169, 169, 169],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                }
            });
            
            // Booking Statistics Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text("Booking Statistics", 20, doc.lastAutoTable.finalY + 15);
            
            const bookingStats = [
                ["Total Bookings", "<?php echo $stats['total_bookings']; ?>"],
                ["Confirmed Bookings", "<?php echo $stats['confirmed_bookings']; ?>"],
                ["Pending Bookings", "<?php echo $stats['pending_bookings']; ?>"],
                ["Cancelled Bookings", "<?php echo $stats['cancelled_bookings']; ?>"]
            ];
            
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 5,
                head: [["Metric", "Value"]],
                body: bookingStats,
                theme: 'grid',
                headStyles: { 
                    fillColor: [169, 169, 169],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                }
            });
            
            // Revenue Statistics Section
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text("Revenue Statistics", 20, doc.lastAutoTable.finalY + 15);
            
            const revenueStats = [
                ["Total Revenue", "Rs.<?php echo number_format($stats['total_revenue'], 2); ?>"],
                ["Average Booking Value", "Rs.<?php echo $stats['confirmed_bookings'] > 0 ? number_format($stats['total_revenue'] / $stats['confirmed_bookings'], 2) : '0.00'; ?>"]
            ];
            
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 5,
                head: [["Metric", "Value"]],
                body: revenueStats,
                theme: 'grid',
                headStyles: { 
                    fillColor: [169, 169, 169],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 10,
                    cellPadding: 5
                }
            });

            // Add footer line
            doc.setLineWidth(0.5);
            doc.line(15, 275, 195, 275);
            
            // Add footer
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text("Travel Ease System Report", 20, 280);
            doc.text(`Generated on: ${date}`, 105, 280, { align: "center" });
            doc.text("Page 1", 190, 280);
            
            // Save the PDF
            doc.save(`travel-ease-system-report-${date}.pdf`);
        }

        // Package Performance Chart
        const packageData = <?php echo json_encode($package_stats); ?>;
        const packageNames = packageData.map(p => p.package_name);
        const bookingCounts = packageData.map(p => p.booking_count);
        const packageRevenues = packageData.map(p => p.total_revenue);

        new Chart(document.getElementById('packageChart'), {
            type: 'bar',
            data: {
                labels: packageNames,
                datasets: [
                    {
                        label: 'Total Bookings',
                        data: bookingCounts,
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (₹)',
                        data: packageRevenues,
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Total Bookings'
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Function to export only packages report
        function exportPackagesReportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const date = new Date().toLocaleDateString().replace(/\//g, '-');
            
            // Add title
            doc.setFontSize(20);
            doc.setTextColor(0, 0, 0);
            doc.text("Travel Ease - Package Report", 105, 20, { align: "center" });
            doc.setFontSize(12);
            doc.text(`Generated on: ${date}`, 105, 30, { align: "center" });
            
            // Package Summary Statistics
            doc.setFontSize(16);
            doc.setTextColor(0, 0, 0);
            doc.text("Package Summary", 20, 45);
            
            const packageSummary = [
                ["Total Packages", "<?php echo $stats['total_packages']; ?>"],
                ["Average Package Price", "₹<?php echo number_format($avg_price, 2); ?>"],
                ["Total Package Revenue", "₹<?php echo number_format($total_package_revenue, 2); ?>"]
            ];
            
            doc.autoTable({
                startY: 50,
                head: [["Metric", "Value"]],
                body: packageSummary,
                theme: 'grid',
                headStyles: { 
                    fillColor: [50, 50, 50],
                    textColor: [255, 255, 255]
                },
                styles: {
                    textColor: [0, 0, 0]
                },
                alternateRowStyles: {
                    fillColor: [240, 240, 240]
                }
            });
            
            // Package Details
            doc.setFontSize(16);
            doc.setTextColor(0, 0, 0);
            doc.text("Package Details", 20, doc.lastAutoTable.finalY + 15);
            
            const packageDetails = <?php echo json_encode($package_stats); ?>.map(package => [
                package.package_name,
                `₹${parseFloat(package.price).toFixed(2)}`,
                package.duration,
                package.booking_count,
                package.confirmed_bookings,
                `₹${parseFloat(package.total_revenue).toFixed(2)}`
            ]);
            
            if (packageDetails.length > 0) {
                doc.autoTable({
                    startY: doc.lastAutoTable.finalY + 20,
                    head: [["Package Name", "Price", "Duration", "Bookings", "Confirmed", "Revenue"]],
                    body: packageDetails,
                    theme: 'grid',
                    headStyles: { 
                        fillColor: [50, 50, 50],
                        textColor: [255, 255, 255]
                    },
                    styles: {
                        fontSize: 8,
                        textColor: [0, 0, 0]
                    },
                    columnStyles: {
                        0: { cellWidth: 40 },
                        1: { cellWidth: 25 },
                        2: { cellWidth: 25 },
                        3: { cellWidth: 25 },
                        4: { cellWidth: 25 },
                        5: { cellWidth: 30 }
                    },
                    alternateRowStyles: {
                        fillColor: [240, 240, 240]
                    }
                });
            }

            // Add footer
            doc.setFontSize(10);
            doc.setTextColor(128, 128, 128);
            doc.text(`Report generated on: ${date}`, 20, 280);
            doc.text("Travel Ease System © <?php echo date('Y'); ?>", 105, 280, { align: "center" });
            
            // Save the PDF
            doc.save(`travel-ease-packages-report-${date}.pdf`);
        }

        function exportPackagesByDateRange() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date must be before end date');
                return;
            }

            // Redirect to the package report generation script with correct parameters
            window.location.href = `get_packages_by_date.php?start=${startDate}&end=${endDate}`;
        }

        function exportPaymentsByDateRange() {
            const startDate = document.getElementById('paymentStartDate').value;
            const endDate = document.getElementById('paymentEndDate').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date must be before or equal to end date.');
                return;
            }

            window.location.href = `get_payments_by_date.php?start_date=${startDate}&end_date=${endDate}`;
        }
    </script>
</body>
</html> 
 
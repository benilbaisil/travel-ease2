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
    'inactive_users' => 0
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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            margin: 0;
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50, #3498db);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: white;
            margin: 0;
            letter-spacing: 1px;
        }

        .nav-links {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            flex-grow: 1;
        }

        .nav-links li {
            margin: 8px 0;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }

        .nav-links li.active a {
            background: rgba(255,255,255,0.1);
            border-left-color: #fff;
        }

        .nav-links li a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #fff;
            transform: translateX(5px);
        }

        .nav-links i {
            width: 20px;
            margin-right: 15px;
            font-size: 18px;
            text-align: center;
        }

        .logout-btn {
            margin: 20px;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 16px;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            margin-left: 280px;
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
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <a href="admin_dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>">
                    <a href="packages.php">
                        <i class="fas fa-box"></i>
                        <span>Packages</span>
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_reports.php' ? 'active' : ''; ?>">
                    <a href="view_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>

        <div class="main-content">
            <div class="dashboard-container">
                <div class="header">
                    <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
                </div>

                <button class="export-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i> Export Report
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
                totalPackages: <?php echo $stats['total_packages']; ?>
            };

            let reportContent = `Travel Ease System Report (${date})\n\n`;
            reportContent += `User Statistics:\n`;
            reportContent += `- Total Users: ${stats.totalUsers}\n`;
            reportContent += `- Total Clients: ${stats.totalClients}\n`;
            reportContent += `- Total Staff: ${stats.totalStaff}\n\n`;
            reportContent += `Package Statistics:\n`;
            reportContent += `- Total Packages: ${stats.totalPackages}\n`;

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
    </script>
</body>
</html> 
 
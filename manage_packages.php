<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_name = $_POST['package_name'];
    $destination = $_POST['destination'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    // Check if package name already exists
    $check_sql = "SELECT COUNT(*) as count FROM travel_packages WHERE package_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $package_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error = "A package with this name already exists. Please choose a different name.";
    } else {
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            }
        }
        
        // Insert package into database
        $sql = "INSERT INTO travel_packages (package_name, destination, duration, price, description, image_path) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidss", $package_name, $destination, $duration, $price, $description, $image_path);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Package added successfully!";
            header('Location: packages_view.php');
            exit();
        } else {
            $error = "Error adding package. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Package - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Updated color palette */
            --primary-color: #3498db; /* Changed to a vibrant blue */
            --secondary-color: #5dade2; /* Changed to a lighter blue */
            --accent-color: #1abc9c; /* Changed to a bright teal */
            --success-color: #2ecc71; /* Changed to a green */
            --danger-color: #e74c3c; /* Changed to a red */
            --text-dark: #2c3e50; /* Changed to a dark blue-gray */
            --text-light: #95a5a6; /* Changed to a light gray */
            --bg-light: #ecf0f1; /* Changed to a light gray */
            --white: #ffffff;
        }

        body {
            background: linear-gradient(135deg, #5dade2, #3498db), 
                        url('img/happy-african-woman-sitting-with-suitcase-isolated-yellow-background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(52, 152, 219, 0.5); /* Adjusted color and opacity for the overlay */
            z-index: -1;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2980b9 0%, #2c3e50 100%);
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .logo {
            display: none;
        }

        .logo img {
            display: none;
        }

        .logo span {
            display: none;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 2rem 0 0 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-links i {
            width: 20px;
            font-size: 1.2rem;
        }

        .nav-links span {
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .page-header {
            position: relative;
            margin-bottom: 2.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(37, 99, 235, 0.1);
        }

        .page-header h1 {
            color: var(--text-dark);
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .form-container {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem;
            border-radius: 1.5rem;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
        }

        .form-group {
            position: relative;
            margin-bottom: 1.8rem;
            animation: fadeInUp 0.6s ease forwards;
            animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .form-group:focus-within label {
            color: var(--primary-color);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: color 0.3s ease;
        }

        .input-wrapper input,
        .input-wrapper textarea {
            width: 100%;
            padding: 1rem 1rem 1rem 2.5rem;
            border: 2px solid rgba(79, 70, 229, 0.1);
            border-radius: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .input-wrapper input:focus,
        .input-wrapper textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }

        .form-group:focus-within i {
            color: var(--primary-color);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            padding-left: 1rem;
        }

        .file-upload {
            background: linear-gradient(145deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.95));
            border: 2px dashed rgba(79, 70, 229, 0.3);
            border-radius: 1rem;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .file-upload::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(79, 70, 229, 0.1), rgba(99, 102, 241, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .file-upload:hover::before {
            opacity: 1;
        }

        .file-upload i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-upload p {
            color: var(--text-light);
            margin: 0;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.2rem 2rem;
            border: none;
            border-radius: 1rem;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.3);
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
        }

        .submit-btn:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }

        .error-message {
            background-color: #fef2f2;
            color: var(--danger-color);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #fee2e2;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Animation for form elements */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }

        /* Add subtle animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <div class="logo">
                <img src="images/logo.png" alt="TravelEase Logo">
                <span>TravelEase</span>
            </div>
            <ul class="nav-links">
                <li>
                    <a href="staff_dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="packages_view.php">
                        <i class="fas fa-suitcase"></i>
                        <span>View Packages</span>
                    </a>
                </li>
                <li>
                    <a href="manage_packages.php" class="active">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Package</span>
                    </a>
                </li>
                <li>
                    <a href="manage_bookings.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="main-content">
            <div class="page-header">
                <h1>Add New Package</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="package_name">Package Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-suitcase"></i>
                            <input type="text" id="package_name" name="package_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="destination">Destination</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" id="destination" name="destination" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration (days)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-clock"></i>
                            <input type="number" id="duration" name="duration" min="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-rupee-sign"></i>
                            <input type="number" id="price" name="price" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Package Image</label>
                        <div class="file-upload">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag and drop your image here or click to browse</p>
                            <input type="file" id="image" name="image" accept="image/*" style="opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-plus-circle"></i> Add Package
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 
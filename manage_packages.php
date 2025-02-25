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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Package - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
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
            background: var(--white);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .form-group {
            position: relative;
            margin-bottom: 1.8rem;
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

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.7rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--bg-light);
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: var(--white);
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
            position: relative;
            width: 100%;
            min-height: 150px;
            border: 2px dashed #e5e7eb;
            border-radius: 0.7rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .file-upload:hover {
            border-color: var(--primary-color);
            background-color: rgba(37, 99, 235, 0.02);
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
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.7rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .form-container {
                padding: 1.5rem;
                margin: 0 1rem;
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
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Reuse the sidebar from packages_view.php -->
        <nav class="sidebar">
            <!-- ... Copy the sidebar content from packages_view.php ... -->
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
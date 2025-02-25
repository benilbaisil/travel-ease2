<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session status
error_log("Session status check:");
error_log("Session ID: " . session_id());
error_log("User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set'));

// Remove debug session data output
// var_dump($_SESSION);

// Check if user is logged in and is an admin or staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'Staff')) {
    error_log("Access denied - redirecting to login. User role: " . 
              (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set'));
    header("Location: login.php");
    exit();
}

// Check if package ID is provided
if (!isset($_GET['id'])) {
    header('Location: packages_view.php');
    exit();
}

$package_id = $_GET['id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "travel_booking";

try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Fetch package details
$sql = "SELECT * FROM travel_packages WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();

if (!$package) {
    header('Location: packages_view.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $package_name = trim(filter_input(INPUT_POST, 'package_name', FILTER_SANITIZE_STRING));
    $destination = trim(filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING));
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    
    // Validate inputs
    if (!$package_name || !$destination || !$duration || !$price || !$description) {
        $error = "Please fill all required fields with valid data.";
    } else {
        // Handle image upload
        $image_path = $package['image_path']; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error = "Invalid file type. Only JPG, PNG and GIF are allowed.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = "File is too large. Maximum size is 5MB.";
            } else {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $new_filename = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $error = "Error uploading file. Please try again.";
                } else {
                    // Delete old image if exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $target_file;
                }
            }
        }

        if (!isset($error)) {
            // Update package in database
            $update_sql = "UPDATE travel_packages SET 
                          package_name = ?, 
                          destination = ?, 
                          duration = ?, 
                          price = ?, 
                          description = ?, 
                          image_path = ? 
                          WHERE id = ?";
            
            try {
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssiissi", $package_name, $destination, $duration, $price, $description, $image_path, $package_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Package updated successfully!";
                    header('Location: packages.php');
                    exit();
                } else {
                    error_log("Update failed: " . $stmt->error); // Log the error
                    throw new Exception("Error updating package.");
                }
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Package - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse the same CSS variables from packages_view.php */
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

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .preview-image {
            max-width: 200px;
            margin-top: 1rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
        }

        .cancel-btn {
            background-color: var(--text-light);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
        }

        .sidebar {
            width: 250px;
            background-color: var(--text-dark);
            color: var(--white);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 1rem;
            z-index: 2;
        }

        .sidebar-header {
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid var(--text-light);
            margin-bottom: 1rem;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }

        .sidebar-nav a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s;
        }

        .sidebar-nav a:hover {
            background-color: var(--secondary-color);
        }

        .sidebar-nav i {
            margin-right: 0.75rem;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            position: relative;
            z-index: 1;
            min-height: 100vh;
            background-color: #f8f9fc;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Replace the include with direct sidebar code -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>TravelEase</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="staff_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="packages_view.php"><i class="fas fa-suitcase"></i> Packages</a></li>
                    <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1>Edit Package</h1>
            </div>

            <div class="form-container">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="package_name">Package Name</label>
                        <input type="text" id="package_name" name="package_name" 
                               value="<?php echo htmlspecialchars($package['package_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="destination">Destination</label>
                        <input type="text" id="destination" name="destination" 
                               value="<?php echo htmlspecialchars($package['destination']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration (days)</label>
                        <input type="number" id="duration" name="duration" 
                               value="<?php echo htmlspecialchars($package['duration']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" name="price" 
                               value="<?php echo htmlspecialchars($package['price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($package['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Package Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (!empty($package['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($package['image_path']); ?>" 
                                 alt="Current package image" class="preview-image">
                        <?php endif; ?>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">Update Package</button>
                        <a href="packages_view.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 
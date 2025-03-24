<?php
session_start();

// Check if user is logged in and is staff or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Staff', 'Admin'])) {
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST['package_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $destination = $conn->real_escape_string($_POST['destination']);
    
    // Check if package name already exists
    $check_query = "SELECT COUNT(*) as count FROM travel_packages WHERE LOWER(package_name) = LOWER(?)";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $package_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $_SESSION['error'] = "A package with this name already exists.";
        header("Location: packages.php");
        exit();
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/packages/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle image upload
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] == 0) {
        $file = $_FILES['package_image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_type = $file['type'];
        
        // Generate unique filename
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('package_', true) . '.' . $file_ext;
        
        // Allowed file types
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg', 'image/gif');
        
        if (in_array($file_type, $allowed_types)) {
            $upload_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Store the relative path in database
                $image_path = $upload_path;
                
                // Add created_by field to track who created the package
                $created_by = $_SESSION['user_id'];
                
                // Insert package with image path and creator info
                $sql = "INSERT INTO travel_packages (package_name, description, price, duration, destination, image_path, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssddssi", $package_name, $description, $price, $duration, $destination, $image_path, $created_by);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Package added successfully!";
                    header("Location: packages.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Error adding package: " . $conn->error;
                    header("Location: packages.php");
                    exit();
                }
                
                $stmt->close();
            } else {
                $_SESSION['error'] = "Error uploading image.";
                header("Location: packages.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Please upload JPG, JPEG, PNG or GIF files only.";
            header("Location: packages.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please select an image for the package.";
        header("Location: packages.php");
        exit();
    }
    
    $conn->close();
}
?> 
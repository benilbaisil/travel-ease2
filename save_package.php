<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "travel_booking";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $package_name = $_POST['package_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $destination = $_POST['destination'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["package_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["package_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    try {
        if (isset($_POST['package_id'])) {
            // Update existing package
            $stmt = $conn->prepare("UPDATE travel_packages SET 
                package_name = ?, 
                description = ?, 
                price = ?, 
                duration = ?, 
                destination = ?" .
                ($image_path ? ", image_path = ?" : "") .
                " WHERE id = ?");
            
            if ($image_path) {
                $stmt->bind_param("ssdissi", $package_name, $description, $price, $duration, $destination, $image_path, $_POST['package_id']);
            } else {
                $stmt->bind_param("ssdisi", $package_name, $description, $price, $duration, $destination, $_POST['package_id']);
            }
        } else {
            // Add new package
            $stmt = $conn->prepare("INSERT INTO travel_packages (package_name, description, price, duration, destination, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiss", $package_name, $description, $price, $duration, $destination, $image_path);
        }

        if ($stmt->execute()) {
            header("Location: admin_dashboard.php");
            exit();
        } else {
            die("Error saving package: " . $conn->error);
        }
    } catch (mysqli_sql_exception $e) {
        die("Database error: " . $e->getMessage());
    }
}

$conn->close();
?> 
<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Fetch package details if ID is provided
if (isset($_GET['id'])) {
    $packageId = intval($_GET['id']);
    $sql = "SELECT * FROM travel_packages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $packageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $package = $result->fetch_assoc();
} else {
    header('Location: packages_view.php');
    exit();
}

// Handle form submission for updating package
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageId = intval($_GET['id']); // Get the package ID from the URL
    $packageName = $_POST['package_name'];
    $destination = $_POST['destination'];
    $duration = intval($_POST['duration']);
    $price = floatval($_POST['price']);
    
    // Handle image upload if a file is provided
    $imagePath = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
        $imagePath = 'uploads/' . basename($_FILES['image_path']['name']);
        move_uploaded_file($_FILES['image_path']['tmp_name'], $imagePath);
    }

    // Prepare the SQL update statement
    $sql = "UPDATE travel_packages SET package_name = ?, destination = ?, duration = ?, price = ?, image_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisi", $packageName, $destination, $duration, $price, $imagePath, $packageId);
    
    // Execute the statement and check for success
    if ($stmt->execute()) {
        header('Location: packages_view.php?success=1');
        exit();
    } else {
        echo "Error updating package: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Package - TravelEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f6fa;
        }

        .package-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .package-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .package-title {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-group label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 8px;
            display: block;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .submit-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            background-color: #2ecc71;
        }

        .submit-btn:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="package-container">
        <div class="package-header">
            <h1 class="package-title">Update Package</h1>
            <a href="packages_view.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Packages
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="package_name">Package Name</label>
                <input type="text" id="package_name" name="package_name" value="<?php echo htmlspecialchars($package['package_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="destination">Destination</label>
                <input type="text" id="destination" name="destination" value="<?php echo htmlspecialchars($package['destination']); ?>" required>
            </div>
            <div class="form-group">
                <label for="duration">Duration (days)</label>
                <input type="number" id="duration" name="duration" value="<?php echo htmlspecialchars($package['duration']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price (₹)</label>
                <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($package['price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="image_path">Image Upload</label>
                <input type="file" id="image_path" name="image_path" accept="image/*">
            </div>
            <div class="action-buttons">
                <button type="submit" class="submit-btn">Update Package</button>
            </div>
        </form>
    </div>
</body>
</html> 
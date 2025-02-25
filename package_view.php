<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// Check if we're in add mode or view mode
$is_add_mode = !isset($_GET['id']);

if (!$is_add_mode) {
    // Existing package view logic
    $package_id = $_GET['id'];
    
    try {
        // Fetch package details with created_at field
        $stmt = $conn->prepare("SELECT id, package_name, description, price, duration, destination, image_path, created_at FROM travel_packages WHERE id = ?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $package = $result->fetch_assoc();

        if (!$package) {
            header("Location: admin_dashboard.php");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    // Initialize empty package for add mode
    $package = [
        'id' => '',
        'package_name' => '',
        'description' => '',
        'price' => '',
        'duration' => '',
        'destination' => '',
        'image_path' => '',
        'created_at' => date('Y-m-d H:i:s')
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $is_add_mode ? 'Add New Package' : 'Package Details - ' . htmlspecialchars($package['package_name']); ?></title>
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

        .back-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .package-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .detail-section {
            margin-bottom: 30px;
        }

        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 8px;
        }

        .detail-value {
            color: #2c3e50;
            font-size: 16px;
            line-height: 1.6;
        }

        .package-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .edit-btn, .delete-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
        }

        .edit-btn {
            background-color: #2ecc71;
        }

        .delete-btn {
            background-color: #e74c3c;
        }

        .edit-btn:hover {
            background-color: #27ae60;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .price-tag {
            font-size: 24px;
            color: #2c3e50;
            font-weight: bold;
        }

        .duration-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #f1f2f6;
            padding: 8px 15px;
            border-radius: 20px;
            color: #2c3e50;
        }

        .detail-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            color: #2c3e50;
        }

        .description-input {
            min-height: 150px;
            resize: vertical;
        }

        form {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="package-container">
        <div class="package-header">
            <h1 class="package-title"><?php echo $is_add_mode ? 'Add New Package' : htmlspecialchars($package['package_name']); ?></h1>
            <a href="admin_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <form action="save_package.php" method="POST" enctype="multipart/form-data">
            <?php if (!$is_add_mode): ?>
                <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
            <?php endif; ?>

            <div class="package-details">
                <div class="detail-section">
                    <div class="detail-label">Package Name</div>
                    <input type="text" name="package_name" value="<?php echo htmlspecialchars($package['package_name']); ?>" required class="detail-input">
                </div>

                <div class="detail-section">
                    <div class="detail-label">Destination</div>
                    <input type="text" name="destination" value="<?php echo htmlspecialchars($package['destination']); ?>" required class="detail-input">
                </div>

                <div class="detail-section">
                    <div class="detail-label">Price</div>
                    <input type="number" name="price" value="<?php echo htmlspecialchars($package['price']); ?>" required class="detail-input">
                </div>

                <div class="detail-section">
                    <div class="detail-label">Duration (days)</div>
                    <input type="number" name="duration" value="<?php echo htmlspecialchars($package['duration']); ?>" required class="detail-input">
                </div>

                <div class="detail-section">
                    <div class="detail-label">Image</div>
                    <input type="file" name="package_image" class="detail-input" <?php echo $is_add_mode ? 'required' : ''; ?>>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-label">Description</div>
                <textarea name="description" required class="detail-input description-input"><?php echo htmlspecialchars($package['description']); ?></textarea>
            </div>

            <div class="action-buttons">
                <button type="submit" class="edit-btn">
                    <i class="fas fa-save"></i> <?php echo $is_add_mode ? 'Create Package' : 'Save Changes'; ?>
                </button>
                <?php if (!$is_add_mode): ?>
                    <button type="button" class="delete-btn" onclick="deletePackage(<?php echo $package['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete Package
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        function deletePackage(packageId) {
            console.log('Package ID to delete:', packageId); // Debugging line
            if (confirm('Are you sure you want to delete this package?')) {
                fetch('delete_package.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'package_id=' + packageId
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Package deleted successfully');
                        window.location.href = 'admin_dashboard.php';
                    } else {
                        alert('Error deleting package: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting package: ' + error.message);
                });
            }
        }
    </script>
</body>
</html> 
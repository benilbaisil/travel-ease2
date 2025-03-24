<?php
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$database = "travel_booking"; // Your database name

// Create connection without database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database with proper character set
$sql = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($database);

// SQL to create the users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    user_role ENUM('Client', 'Staff', 'Admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE INDEX email_idx (email),
    INDEX role_idx (user_role)
)";


if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// SQL to create the travel_packages table
$sql = "CREATE TABLE IF NOT EXISTS travel_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    destination VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive', 'Sold Out') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX package_status_idx (status),
    INDEX destination_idx (destination)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'travel_packages' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// SQL to create the bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    travel_date DATE NOT NULL,
    num_guests INT NOT NULL,
    phone VARCHAR(15) NOT NULL,
    payment_id VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') DEFAULT 'Pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (package_id) REFERENCES travel_packages(id),
    INDEX booking_status_idx (booking_status),
    INDEX travel_date_idx (travel_date)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'bookings' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// First create admin user
$admin_email = mysqli_real_escape_string($conn, "benilbaisil001@gmail.com");
$check_admin = "SELECT * FROM users WHERE email = '$admin_email'";
$result = $conn->query($check_admin);

if ($result->num_rows == 0) {
    // Insert Admin user (password should be hashed)
    $admin_password = password_hash("Admin1234", PASSWORD_DEFAULT);
    $sql_insert_admin = "INSERT INTO users (name, email, password, phone_number, user_role) 
                         VALUES ('System Admin', '$admin_email', '$admin_password', '9876543210', 'Admin')";

    if ($conn->query($sql_insert_admin) === TRUE) {
        echo "Admin user inserted successfully.<br>";
    } else {
        echo "Error inserting Admin user: " . $conn->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

// Then insert travel packages with admin as created_by
$admin_id_query = "SELECT user_id FROM users WHERE email = '$admin_email'";
$admin_result = $conn->query($admin_id_query);
$admin_id = $admin_result->fetch_assoc()['user_id'];

// Insert sample travel packages without the status field
$sql = "INSERT INTO travel_packages (package_name, description, price, duration, destination, image_path, created_by) VALUES
    ('Goa Beach Vacation', 'Enjoy 3 days of sun and sand in beautiful Goa', 15000.00, 3, 'Goa', 'images/goa.jpg', ?),
    ('Kerala Backwaters', 'Experience the serene backwaters of Kerala', 20000.00, 4, 'Kerala', 'images/kerala.jpg', ?),
    ('Manali Adventure', 'Thrilling mountain adventure in Manali', 18000.00, 5, 'Manali', 'images/manali.jpg', ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $admin_id, $admin_id, $admin_id);

if ($stmt->execute()) {
    echo "Sample travel packages inserted successfully.<br>";
} else {
    echo "Error inserting sample packages: " . $stmt->error . "<br>";
}

// SQL to create the notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'notifications' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Fetch notifications for the logged-in user
$notifications = [];
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create enquiries table
$sql = "CREATE TABLE IF NOT EXISTS enquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending', 'Responded', 'Closed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response TEXT,
    responded_at TIMESTAMP NULL,
    responded_by INT,
    FOREIGN KEY (responded_by) REFERENCES users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'enquiries' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    // After booking is successful
    $message = "Your booking has been confirmed!";
    $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
}


// Close connection
$conn->close();
?>

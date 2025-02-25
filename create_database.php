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

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
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
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    user_role ENUM('Client', 'Staff', 'Admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
)";

// CREATE TABLE `bookings` (
//     `id` int(11) NOT NULL AUTO_INCREMENT,
//     `user_id` int(11) NOT NULL,
//     `package_id` int(11) NOT NULL,
//     `staff_id` int(11) NOT NULL,
//     `booking_date` datetime DEFAULT CURRENT_TIMESTAMP,
//     `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
//     PRIMARY KEY (`id`),
//     KEY `user_id` (`user_id`),
//     KEY `package_id` (`package_id`),
//     KEY `staff_id` (`staff_id`),
//     CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
//     CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`),
//     CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



if ($conn->query($sql) === TRUE) {
    echo "Table 'travel_packages' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if admin already exists
$admin_email = "benilbaisil001@gmail.com";
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

// Close connection
$conn->close();
?>

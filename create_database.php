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


// Insert Admin user (password should be hashed)
$admin_email = "admin@gmail.com";
$admin_password = password_hash("AdminPass123", PASSWORD_DEFAULT);
$sql_insert_admin = "INSERT INTO users (name, email, password, phone_number, user_role) 
                     VALUES ('System Admin', '$admin_email', '$admin_password', '9876543210', 'Admin')";

if ($conn->query($sql_insert_admin) === TRUE) {
    echo "Admin user inserted successfully.<br>";
} else {
    echo "Error inserting Admin user: " . $conn->error . "<br>";
}

// Close connection
$conn->close();
?>

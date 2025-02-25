<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS travel_booking";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("travel_booking");

// Create packages table
$sql = "CREATE TABLE IF NOT EXISTS packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    location VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Packages table created successfully or already exists<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert sample data
$sql = "INSERT INTO packages (package_name, description, price, duration, location, image_url, status) 
        SELECT * FROM (SELECT 
            'Goa Beach Getaway' as package_name,
            'Experience the beautiful beaches of Goa with this all-inclusive package. Includes hotel stay, meals, and sightseeing.' as description,
            25000.00 as price,
            5 as duration,
            'Goa, India' as location,
            'images/packages/goa-beach.jpg' as image_url,
            'Available' as status
        ) AS tmp
        WHERE NOT EXISTS (
            SELECT package_name FROM packages WHERE package_name = 'Goa Beach Getaway'
        ) LIMIT 1";

if ($conn->query($sql) === TRUE) {
    echo "Sample package added successfully (if it didn't exist)<br>";
} else {
    echo "Error inserting sample data: " . $conn->error . "<br>";
}

$conn->close();
echo "Database setup completed!";
?>
<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
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
    // Get form data
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $user_role = $conn->real_escape_string($_POST['user_role']);

    // Check if email already exists
    $check_email = "SELECT email FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists!";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Insert new user
    $sql = "INSERT INTO users (name, email, password, user_role) 
            VALUES ('$name', '$email', '$password', '$user_role')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = "New user added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }

    $conn->close();
    header("Location: admin_dashboard.php");
    exit();
}
?> 
<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Debug incoming data
    error_log("Login attempt - Email: $email");

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['user_name'] = $user['name'];
            
            // Debug session after setting
            error_log("Session set - User ID: {$_SESSION['user_id']}, Role: {$_SESSION['user_role']}");

            // Redirect based on user role
            if ($user['user_role'] == 'Admin' || $user['user_role'] == 'Staff') {
                header("Location: packages.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "User not found!";
        header("Location: login.php");
        exit();
    }
}
?> 
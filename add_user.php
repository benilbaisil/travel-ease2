<?php
session_start();
require_once 'config.php';

// Validate and sanitize inputs
$errors = [];

// Name validation
if (empty($_POST['name'])) {
    $errors[] = "Full name is required";
} elseif (strlen($_POST['name']) > 100) {
    $errors[] = "Name cannot exceed 100 characters";
} elseif (!preg_match("/^[a-zA-Z ]*$/", $_POST['name'])) {
    $errors[] = "Name can only contain letters and spaces";
}

// Email validation
if (empty($_POST['email'])) {
    $errors[] = "Email is required";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
} else {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
}

// Password validation
if (empty($_POST['password'])) {
    $errors[] = "Password is required";
} elseif (strlen($_POST['password']) < 8) {
    $errors[] = "Password must be at least 8 characters long";
} elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/", $_POST['password'])) {
    $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
}

// Role validation
$allowed_roles = ['Client', 'Staff', 'Admin'];
if (empty($_POST['user_role']) || !in_array($_POST['user_role'], $allowed_roles)) {
    $errors[] = "Invalid user role selected";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: manage_users.php");
    exit();
}

// If validation passes, proceed with user creation
try {
    // Hash the password
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_role, active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $_POST['name'], $_POST['email'], $hashed_password, $_POST['user_role']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User created successfully";
    } else {
        throw new Exception("Error creating user");
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header("Location: manage_users.php");
exit();
?> 
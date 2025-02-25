<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get user input
$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$current_password = $_POST['currentPassword'] ?? '';
$new_password = $_POST['newPassword'] ?? '';
$confirm_new_password = $_POST['confirmNewPassword'] ?? '';

// Validate inputs
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Update basic information
    $update_sql = "UPDATE users SET name = ?, email = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $name, $email, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error updating profile information');
    }

    // Handle password change if requested
    if (!empty($current_password) && !empty($new_password)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }

        // Validate new password
        if (strlen($new_password) < 8) {
            throw new Exception('New password must be at least 8 characters long');
        }

        if ($new_password !== $confirm_new_password) {
            throw new Exception('New passwords do not match');
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error updating password');
        }
    }

    // Commit transaction
    $conn->commit();

    // Update session variables
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'name' => $name,
            'email' => $email
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
$conn->close();
?> 
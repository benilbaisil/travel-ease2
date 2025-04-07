<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit an enquiry']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$email = $_SESSION['email'];
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);

// Validate input
if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

try {
    // Prepare and execute the SQL statement
    $sql = "INSERT INTO enquiries (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    
    if ($stmt->execute()) {
        // Create a notification for staff members
        $notify_sql = "INSERT INTO notifications (user_id, message) 
                      SELECT user_id, 'New enquiry received from " . $conn->real_escape_string($name) . "'
                      FROM users 
                      WHERE user_role IN ('Staff', 'Admin')";
        $conn->query($notify_sql);
        
        echo json_encode(['success' => true, 'message' => 'Enquiry submitted successfully']);
    } else {
        throw new Exception('Failed to submit enquiry');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 
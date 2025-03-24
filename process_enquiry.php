<?php
// Remove any whitespace before opening PHP tag
session_start();
require_once 'config.php';

// Set JSON header immediately
header('Content-Type: application/json');

// Disable error output that might corrupt JSON
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check database connection
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection failed");
        }

        // Validate inputs
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING));
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));

        if (!$name || !$email || !$subject || !$message) {
            throw new Exception('All fields are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert enquiry
            $sql = "INSERT INTO enquiries (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'Pending')";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Failed to prepare enquiry statement');
            }

            $stmt->bind_param("ssss", $name, $email, $subject, $message);

            if (!$stmt->execute()) {
                throw new Exception('Failed to submit enquiry');
            }

            $enquiry_id = $conn->insert_id;

            // Notify staff members
            $staff_sql = "SELECT user_id FROM users WHERE user_role = 'Staff'";
            $staff_result = $conn->query($staff_sql);

            if ($staff_result) {
                $notification_message = "New enquiry #$enquiry_id from " . htmlspecialchars($name) . ": " . htmlspecialchars($subject);
                
                while ($staff = $staff_result->fetch_assoc()) {
                    $notify_sql = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
                    $notify_stmt = $conn->prepare($notify_sql);
                    
                    if ($notify_stmt) {
                        $notify_stmt->bind_param("is", $staff['user_id'], $notification_message);
                        $notify_stmt->execute();
                        $notify_stmt->close();
                    }
                }
            }

            // Commit transaction
            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Thank you for your enquiry. We will get back to you soon!'
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    // Close connections
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
exit();
<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $enquiry_id = filter_input(INPUT_POST, 'enquiry_id', FILTER_VALIDATE_INT);
        $response = trim(filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING));
        $staff_id = $_SESSION['user_id'];

        if (!$enquiry_id || !$response) {
            throw new Exception('Invalid input data');
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update enquiry
            $stmt = $conn->prepare("UPDATE enquiries SET status = 'Responded', response = ?, responded_at = NOW(), responded_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $response, $staff_id, $enquiry_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update enquiry');
            }

            // Get enquiry details for notification
            $query = "SELECT name, email FROM enquiries WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $enquiry_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $enquiry = $result->fetch_assoc();

            // Create notification for the enquirer if they are a registered user
            $user_query = "SELECT user_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param("s", $enquiry['email']);
            $stmt->execute();
            $user_result = $stmt->get_result();
            
            if ($user = $user_result->fetch_assoc()) {
                $notification_message = "Your enquiry has received a response from our staff.";
                $notify_sql = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
                $notify_stmt = $conn->prepare($notify_sql);
                $notify_stmt->bind_param("is", $user['user_id'], $notification_message);
                $notify_stmt->execute();
            }

            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Response sent successfully'
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
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

exit(); 
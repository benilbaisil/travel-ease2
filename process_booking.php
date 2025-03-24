<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['name']) || !isset($_POST['payment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

function createNotification($conn, $user_id, $message) {
    $sql = "INSERT INTO notifications (user_id, message, is_read, created_at) 
            VALUES (?, ?, 0, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);
    return $stmt->execute();
}

function notifyStaff($conn, $booking_id, $package_name, $user_name) {
    // Get all staff members
    $sql = "SELECT user_id FROM users WHERE user_role = 'Staff'";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($staff = $result->fetch_assoc()) {
            $message = "New booking received: $user_name has booked $package_name (Booking ID: $booking_id)";
            createNotification($conn, $staff['user_id'], $message);
        }
    }
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert booking details
    $sql = "INSERT INTO bookings (
        user_id,
        package_id,
        travel_date,
        num_guests,
        phone,
        payment_id,
        total_amount,
        booking_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')";

    $stmt = $conn->prepare($sql);
    
    $user_id = $_SESSION['user_id'];
    $package_id = $_POST['package_id'];
    $travel_date = $_POST['travel_date'];
    $guests = $_POST['guests'];
    $phone = $_POST['phone'];
    $payment_id = $_POST['payment_id'];
    
    // Calculate total amount
    $package_query = "SELECT price FROM travel_packages WHERE id = ?";
    $stmt_price = $conn->prepare($package_query);
    $stmt_price->bind_param("i", $package_id);
    $stmt_price->execute();
    $price_result = $stmt_price->get_result();
    $package_price = $price_result->fetch_assoc()['price'];
    $total_amount = $package_price * $guests;

    $stmt->bind_param("iissssd",
        $user_id,
        $package_id,
        $travel_date,
        $guests,
        $phone,
        $payment_id,
        $total_amount
    );

    $stmt->execute();
    $booking_id = $conn->insert_id;

    // Commit transaction
    $conn->commit();

    if ($booking_id) {
        // Create notification for the new booking
        $package_query = "SELECT package_name, destination FROM travel_packages WHERE id = ?";
        $stmt_package = $conn->prepare($package_query);
        $stmt_package->bind_param("i", $package_id);
        $stmt_package->execute();
        $package_result = $stmt_package->get_result();
        $package = $package_result->fetch_assoc();
        
        $notification_message = "New booking confirmed for " . htmlspecialchars($package['package_name']) . 
                              " to " . htmlspecialchars($package['destination']) . 
                              " on " . date('F j, Y', strtotime($travel_date));
        
        createNotification($conn, $_SESSION['user_id'], $notification_message);
        
        // Create notification for staff
        notifyStaff($conn, $booking_id, $package['package_name'], $_SESSION['name']);

        // Send success response
        $response = array(
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Booking successful!'
        );
        echo json_encode($response);
    } else {
        // Error handling
        $response = array(
            'success' => false,
            'message' => 'Failed to create booking'
        );
        echo json_encode($response);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your booking.'
    ]);
}

$conn->close(); 
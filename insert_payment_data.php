<?php
require_once 'config.php';

// Get all bookings that don't have payment records
$sql = "SELECT b.* 
        FROM bookings b 
        LEFT JOIN payment p ON b.id = p.booking_id 
        WHERE p.id IS NULL";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " bookings without payment records.<br>";
    
    // Insert payment records for each booking
    while ($booking = $result->fetch_assoc()) {
        $amount = $booking['total_amount'];
        $booking_id = $booking['id'];
        
        // Determine payment status based on booking status
        $payment_status = 'Pending';
        if ($booking['booking_status'] == 'Confirmed') {
            $payment_status = 'Success';
        } elseif ($booking['booking_status'] == 'Cancelled') {
            $payment_status = 'Failed';
        }
        
        // Generate a random payment method
        $payment_methods = ['Credit Card', 'Debit Card', 'UPI', 'Net Banking', 'Cash'];
        $payment_method = $payment_methods[array_rand($payment_methods)];
        
        // Generate a random transaction ID
        $transaction_id = 'TXN' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Insert payment record
        $insert_sql = "INSERT INTO payment (booking_id, amount, mode, status, transaction_id, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $created_at = $booking['created_at']; // Use booking creation date for payment
        $stmt->bind_param("idssss", $booking_id, $amount, $payment_method, $payment_status, $transaction_id, $created_at);
        
        if ($stmt->execute()) {
            echo "Payment record created for booking ID: $booking_id<br>";
        } else {
            echo "Error creating payment record for booking ID: $booking_id - " . $stmt->error . "<br>";
        }
    }
    
    // Display the newly created payment records
    echo "<br>Newly created payment records:<br>";
    $sql = "SELECT p.*, b.booking_status, u.name as user_name, u.email as user_email, tp.package_name 
            FROM payment p 
            JOIN bookings b ON p.booking_id = b.id 
            JOIN users u ON b.user_id = u.user_id 
            JOIN travel_packages tp ON b.package_id = tp.id 
            ORDER BY p.created_at DESC";
            
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Payment ID: " . $row['id'] . 
                 " | User: " . $row['user_name'] . 
                 " | Package: " . $row['package_name'] . 
                 " | Amount: ₹" . $row['amount'] . 
                 " | Status: " . $row['status'] . 
                 " | Transaction ID: " . $row['transaction_id'] . 
                 " | Date: " . $row['created_at'] . "<br>";
        }
    }
} else {
    echo "No bookings found without payment records.";
}

$conn->close();
?> 
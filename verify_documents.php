<?php
// ... existing code ...

// Establish a database connection
$servername = "localhost"; // or your server name
$username = "root"; // updated username
$password = ""; // updated password
$dbname = "travel_booking"; // updated database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Aadhaar document path from the database
$sql = "SELECT aadhaar_path, verified FROM bookings WHERE user_id = ? AND package_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $package_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if ($booking && file_exists($booking['aadhaar_path'])) {
    echo '<div class="aadhaar-document">';
    echo '<h3>Aadhaar Document</h3>';
    echo '<img src="' . htmlspecialchars($booking['aadhaar_path']) . '" alt="Aadhaar Document">';
    echo '</div>';

    // Add verification button for staff
    if (!$booking['verified']) {
        echo '<form method="POST" action="verify_aadhaar.php">';
        echo '<input type="hidden" name="user_id" value="' . htmlspecialchars($user_id) . '">';
        echo '<input type="hidden" name="package_id" value="' . htmlspecialchars($package_id) . '">';
        echo '<button type="submit" name="verify" class="btn btn-success">Verify Aadhaar</button>';
        echo '</form>';
    } else {
        echo '<p>Aadhaar document is verified.</p>';
    }
} else {
    echo 'Aadhaar document not found.';
}

// ... existing code ...
?> 
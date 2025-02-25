<?php
$conn = new mysqli('localhost', 'root', '', 'travel_booking');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 
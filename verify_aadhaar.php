<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $user_id = $_POST['user_id'];
    $package_id = $_POST['package_id'];

    $sql = "UPDATE documents SET verified = 1 WHERE user_id = ? AND package_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $package_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Aadhaar document verified successfully.";
    } else {
        $_SESSION['message'] = "Error verifying Aadhaar document.";
    }

    header("Location: verify_documents.php");
    exit();
}
?> 
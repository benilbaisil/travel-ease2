<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit(); 
<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'];
$sql = "DELETE FROM users WHERE id = $id AND role = 'sales'";
$conn->query($sql);

header('Location: manage_users.php');
exit();
?>

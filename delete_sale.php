<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $sale_id = $_GET['id'];

    // ลบยอดขาย
    $sql = "DELETE FROM sales WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $sale_id]);

    header('Location: dashboard.php');
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}

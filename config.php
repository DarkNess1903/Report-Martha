<?php
$host = 'localhost';
$db = 'sales_management';
$user = 'root'; // หรือ username ของฐานข้อมูล
$pass = ''; // หรือ password ของฐานข้อมูล

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
<?php
$servername = "localhost";
$username = "root"; // ชื่อผู้ใช้ MySQL
$password = ""; // รหัสผ่าน MySQL
$dbname = "marthag_sales"; // ชื่อฐานข้อมูล

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

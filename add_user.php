<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];  // ใช้รหัสผ่านที่ผู้ใช้กรอก
    $role = $_POST['role'];

    // ใช้ password_hash เพื่อเข้ารหัสรหัสผ่านก่อนบันทึกลงฐานข้อมูล
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // เพิ่มข้อมูลผู้ใช้งานใหม่ในฐานข้อมูล
    $sql = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username, 'password' => $hashed_password, 'role' => $role]);

    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">เพิ่มผู้ใช้งานใหม่</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">บทบาท</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="admin">ผู้บริหาร</option>
                    <option value="sales">พนักงานขาย</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">เพิ่มผู้ใช้งาน</button>
        </form>
    </div>
</body>
</html>

<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // เข้ารหัสรหัสผ่าน

    // เพิ่มพนักงาน
    $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'sales')";
    if ($conn->query($sql) === TRUE) {
        header('Location: manage_users.php');
        exit();
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <title>เพิ่มพนักงาน</title>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">เพิ่มพนักงาน</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success">เพิ่ม</button>
            <a href="manage_users.php" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</body>
</html>

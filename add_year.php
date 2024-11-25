<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// เพิ่มปีใหม่
if (isset($_POST['add_year'])) {
    $year = $_POST['year'];

    $sql = "INSERT INTO years (year) VALUES ('$year')";
    if ($conn->query($sql) === TRUE) {
        echo "เพิ่มปีใหม่สำเร็จ";
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <title>เพิ่มปีใหม่</title>
</head>
<body>
    <div class="container mt-5">
        <h1>เพิ่มปีใหม่</h1>
        <form action="add_year.php" method="POST">
            <div class="mb-3">
                <label for="year" class="form-label">ปี</label>
                <input type="number" class="form-control" name="year" required>
            </div>
            <button type="submit" name="add_year" class="btn btn-primary">เพิ่มปี</button>
        </form>
    </div>
</body>
</html>

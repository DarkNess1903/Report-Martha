<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// แสดงข้อมูลพนักงานทั้งหมด
$sql = "SELECT users.id, users.username, 
               COALESCE(SUM(sales.amount), 0) AS total_sales 
        FROM users 
        LEFT JOIN sales ON users.id = sales.user_id 
        WHERE users.role = 'sales'
        GROUP BY users.id, users.username";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>จัดการข้อมูลยอดขาย</title>
</head>
<body>
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1>จัดการข้อมูลยอดขาย</h1>

        <!-- ตารางแสดงข้อมูลพนักงาน -->
        <table class="table table-bordered mt-4">
    <thead>
        <tr>
            <th>#</th>
            <th>พนักงานขาย</th>
            <th>ยอดขายรวม (บาท)</th>
            <th>ดูข้อมูลยอดขาย</th>
        </tr>
    </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= number_format($row['total_sales'], 2) ?> บาท</td>
                        <td>
                            <a href="sales_details.php?user_id=<?= $row['id'] ?>" class="btn btn-info">ดูข้อมูล</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">ไม่มีข้อมูลพนักงานขาย</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลยอดขายเฉพาะของพนักงานขายที่ล็อกอิน
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM sales WHERE user_id = ? ORDER BY year DESC, quarter ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// แปลงไตรมาสเป็นเดือน
$quarter_to_month = [
    '1' => 'มกราคม',
    '2' => 'เมษายน',
    '3' => 'กรกฎาคม',
    '4' => 'ตุลาคม'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>ดูข้อมูลยอดขาย</title>
</head>
<body>
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">ข้อมูลยอดขายของคุณ</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ปี</th>
                    <th>เดือน</th>
                    <th>ยอดขาย (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['year']) ?></td>
                            <td><?= htmlspecialchars($quarter_to_month[$row['quarter']]) ?></td>
                            <td><?= number_format($row['amount'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">ไม่มีข้อมูลยอดขาย</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="employee_dashboard.php" class="btn btn-secondary">กลับไปยังหน้าหลัก</a>
    </div>
</body>
</html>

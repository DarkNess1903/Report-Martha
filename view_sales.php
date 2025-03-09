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

// ดึงยอดขายทั้งหมดในแต่ละปี
$sql_yearly_sales = "SELECT year, SUM(amount) AS total_sales FROM sales WHERE user_id = ? GROUP BY year ORDER BY year DESC";
$stmt_yearly_sales = $conn->prepare($sql_yearly_sales);
$stmt_yearly_sales->bind_param("i", $user_id);
$stmt_yearly_sales->execute();
$yearly_sales_result = $stmt_yearly_sales->get_result();

// ดึงยอดขายทั้งหมด
$sql_total_sales = "SELECT SUM(amount) AS total_sales FROM sales WHERE user_id = ?";
$stmt_total_sales = $conn->prepare($sql_total_sales);
$stmt_total_sales->bind_param("i", $user_id);
$stmt_total_sales->execute();
$total_sales_result = $stmt_total_sales->get_result();
$stmt_yearly_sales->close();
$stmt_total_sales->close();

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

        <!-- ยอดขายรวมทั้งหมด -->
        <div class="mb-4">
            <h3>ยอดขายรวมทั้งหมด:</h3>
            <p><?= number_format($total_sales_result->fetch_assoc()['total_sales'], 2) ?> บาท</p>
        </div>

        <!-- ตารางยอดขายตามปี -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ปี</th>
                    <th>ยอดขายรวม (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($yearly_sales_result->num_rows > 0): ?>
                    <?php while ($row = $yearly_sales_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['year']) ?></td>
                            <td><?= number_format($row['total_sales'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">ไม่มีข้อมูลยอดขาย</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ข้อมูลยอดขายตามไตรมาส -->
        <h3 class="mb-3">ข้อมูลยอดขายตามไตรมาส (ปีและไตรมาส):</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ปี</th>
                    <th>ไตรมาส</th>
                    <th>ยอดขาย (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงข้อมูลยอดขายตามปีและไตรมาส
                $sql_quarterly_sales = "SELECT year, quarter, SUM(amount) AS total_amount FROM sales WHERE user_id = ? GROUP BY year, quarter ORDER BY year DESC, quarter ASC";
                $stmt_quarterly_sales = $conn->prepare($sql_quarterly_sales);
                $stmt_quarterly_sales->bind_param("i", $user_id);
                $stmt_quarterly_sales->execute();
                $quarterly_sales_result = $stmt_quarterly_sales->get_result();
                $stmt_quarterly_sales->close();

                if ($quarterly_sales_result->num_rows > 0):
                    while ($row = $quarterly_sales_result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['year']) ?></td>
                        <td><?= $quarter_to_month[$row['quarter']] ?></td>
                        <td><?= number_format($row['total_amount'], 2) ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" class="text-center">ไม่มีข้อมูลยอดขายตามไตรมาส</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="employee_dashboard.php" class="btn btn-secondary">กลับไปยังหน้าหลัก</a>
    </div>
</body>
</html>

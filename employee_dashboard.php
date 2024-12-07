<?php
session_start();
include 'db.php';

// ตรวจสอบว่าผู้ใช้เป็นพนักงาน (sales) หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลยอดขายของพนักงานที่ล็อกอินอยู่
$user_id = $_SESSION['user_id'];
$sql = "SELECT year, quarter, SUM(amount) AS total_sales 
        FROM sales 
        WHERE user_id = ? 
        GROUP BY year, quarter 
        ORDER BY year DESC, quarter DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// สร้าง array สำหรับข้อมูลยอดขาย
$sales_data = [];
$years = [];
$months = [];
$sales = [];

// แปลงไตรมาสเป็นเดือน
$quarter_to_month = [
    '1' => 'มกราคม',
    '2' => 'เมษายน',
    '3' => 'กรกฎาคม',
    '4' => 'ตุลาคม'
];

while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
    $years[] = $row['year'];
    // แปลงไตรมาสเป็นเดือน
    $months[] = $quarter_to_month[$row['quarter']] . " " . $row['year'];
    $sales[] = $row['total_sales'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Dashboard - ยอดขายของคุณ</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* กรอบให้กับกราฟและตาราง */
        .chart-container, .table-container {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
        }
        .table-container {
            height: 100%;
        }
    </style>
</head>
<body>
    <?php include 'topnavbar.php'; ?> <!-- เรียกใช้ topnavbar.php สำหรับเมนู -->
    <div class="container mt-5">
        <h1>Dashboard - ยอดขายของคุณ</h1>

        <?php if (count($sales_data) > 0): ?>
            <div class="row">
                <!-- กราฟยอดขาย -->
                <div class="col-md-6 chart-container">
                    <canvas id="salesChart" width="400" height="200"></canvas>
                    <script>
                        // กราฟยอดขาย
                        var ctx = document.getElementById('salesChart').getContext('2d');
                        var salesChart = new Chart(ctx, {
                            type: 'line', // กราฟเป็นแบบเส้น
                            data: {
                                labels: <?= json_encode($months) ?>, // เดือนที่แปลงจากไตรมาส
                                datasets: [{
                                    label: 'ยอดขายรวม (บาท)',
                                    data: <?= json_encode($sales) ?>, // ข้อมูลยอดขาย
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(tooltipItem) {
                                                return 'ยอดขาย: ' + tooltipItem.raw.toLocaleString() + ' บาท';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                </div>

                <!-- ตารางยอดขาย -->
                <div class="col-md-6 table-container">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>เดือน</th>
                                <th>ยอดขายรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_data as $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($quarter_to_month[$data['quarter']]) ?> <?= $data['year'] ?></td>
                                    <td><?= number_format($data['total_sales'], 2) ?> บาท</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <p>ยังไม่มีข้อมูลยอดขายของคุณในปีและไตรมาสนี้</p>
        <?php endif; ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
            <div class="row mb-3">
                <!-- ฟอร์มเลือกปี -->
                <div class="col-md-12 mb-4">
                    <label class="form-label">เลือกปี:</label>
                    <div class="d-flex flex-wrap">
                        <?php
                        // กำหนดรายการปีที่มีข้อมูล
                        $unique_years = array_unique(array_column($sales_data, 'year')); 
                        foreach ($unique_years as $year): ?>
                            <div class="form-check form-check-inline me-3">
                                <input class="form-check-input" type="checkbox" name="years[]" value="<?= $year ?>" checked>
                                <label class="form-check-label"><?= $year ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ตัวเลือกในการเลือกช่วงเวลาที่ต้องการแสดง -->
                <div class="col-md-12 mb-4">
                    <label for="timePeriodSelect">เลือกช่วงเวลา:</label>
                    <select id="timePeriodSelect" class="form-select">
                        <option value="monthly">รายเดือน</option>
                        <option value="quarterly">รายไตรมาส</option>
                        <option value="yearly">รายปี</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <!-- กราฟยอดขาย -->
                <div class="col-md-6 mb-4 chart-container">
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>

                <!-- ตารางยอดขาย -->
                <div class="col-md-6 mb-4 table-container">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>เดือน/ไตรมาส</th>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('salesChart').getContext('2d');

            // ส่ง quarter_to_month จาก PHP ไปยัง JavaScript
            var quarterToMonth = <?= json_encode($quarter_to_month) ?>;

            // เตรียมข้อมูลสำหรับกราฟ
            var salesData = {
                labels: <?= json_encode($months) ?>, // เดือนที่แปลงจากไตรมาส
                datasets: [] // จะมีข้อมูลที่อัปเดตตามการเลือกปี
            };

            var salesDataFromPHP = <?= json_encode($sales_data) ?>; // ข้อมูลยอดขายจาก PHP

            function updateChart() {
                var selectedYears = Array.from(document.querySelectorAll('input[name="years[]"]:checked')).map(el => el.value);
                
                var datasets = [];

                selectedYears.forEach(function(year) {
                    var dataset = {
                        label: 'ยอดขายปี ' + year,
                        data: Array(salesData.labels.length).fill(null), // กำหนดให้ทุกเดือนมีค่า null เริ่มต้น
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        lineTension: 0, // ทำให้เส้นตรง
                        fill: false // ไม่ให้กราฟเป็นพื้นที่
                    };

                    // ค้นหาข้อมูลยอดขายสำหรับปีนี้
                    salesDataFromPHP.forEach(function(item) {
                        if (item.year == year) {
                            // แปลงไตรมาสเป็นเดือน
                            var month = quarterToMonth[item.quarter];  // ใช้ quarterToMonth ที่ได้รับจาก PHP
                            var index = salesData.labels.indexOf(month + " " + item.year);
                            if (index !== -1) {
                                dataset.data[index] = item.total_sales;
                            }
                        }
                    });

                    datasets.push(dataset);
                });

                // อัปเดตข้อมูลในกราฟ
                salesData.datasets = datasets;
                salesChart.update();
            }

            // สร้างกราฟ
            var salesChart = new Chart(ctx, {
                type: 'line', // กราฟเป็นเส้น
                data: salesData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return 'ยอดขาย: ' + tooltipItem.raw ? tooltipItem.raw.toLocaleString() + ' บาท' : 'ไม่มีข้อมูล';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value === null) {
                                        return 'ไม่มีข้อมูล';
                                    }
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // ฟังการเปลี่ยนแปลงของ checkbox ปีที่เลือก
            document.querySelectorAll('input[name="years[]"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', updateChart);
            });

            // ฟังการเปลี่ยนแปลงของช่วงเวลา
            document.getElementById('timePeriodSelect').addEventListener('change', updateChart);

            // เรียกฟังก์ชันเพื่ออัปเดตกกราฟครั้งแรกเมื่อโหลด
            updateChart();
        });
    </script>
</body>
</html>

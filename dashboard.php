<?php
session_start();
require 'config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// เรียกข้อมูลยอดขายจากฐานข้อมูล
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? $_GET['quarter'] : 'all'; // ใช้ 'all' สำหรับกรณีไม่เลือกไตรมาส

// ฟังก์ชันดึงข้อมูลยอดขายจากฐานข้อมูล
function getSalesData($year, $quarter = 'all') {
    global $pdo;
    if ($quarter == 'all') {
        // ถ้าเลือก 'all' ไม่ต้องใช้ :quarter
        $sql = "SELECT user_id, SUM(sale_amount) AS total_sales FROM sales WHERE YEAR(sale_date) = :year GROUP BY user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year' => $year]);
    } else {
        // ถ้าเลือกไตรมาส ให้ส่ง :quarter
        $sql = "SELECT user_id, SUM(sale_amount) AS total_sales FROM sales WHERE YEAR(sale_date) = :year AND QUARTER(sale_date) = :quarter GROUP BY user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year' => $year, 'quarter' => $quarter]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึงข้อมูลยอดขายรวม
function getTotalSales($year, $quarter = 'all') {
    global $pdo;
    if ($quarter == 'all') {
        // ถ้าเลือก 'all' ไม่ต้องใช้ :quarter
        $sql = "SELECT SUM(sale_amount) AS total_sales FROM sales WHERE YEAR(sale_date) = :year";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year' => $year]);
    } else {
        // ถ้าเลือกไตรมาส ให้ส่ง :quarter
        $sql = "SELECT SUM(sale_amount) AS total_sales FROM sales WHERE YEAR(sale_date) = :year AND QUARTER(sale_date) = :quarter";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year' => $year, 'quarter' => $quarter]);
    }
    return $stmt->fetchColumn();
}

// ข้อมูลยอดขายรวม
$totalSales = getTotalSales($year, $quarter);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ด</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">แดชบอร์ดยอดขาย</h2>
        
        <!-- เลือกปีและไตรมาส -->
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <label for="year" class="form-label">เลือกปี</label>
                    <select class="form-select" id="year" name="year">
                        <?php for ($i = 2020; $i <= date('Y'); $i++) { ?>
                            <option value="<?= $i ?>" <?= ($i == $year) ? 'selected' : ''; ?>><?= $i ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="quarter" class="form-label">เลือกไตรมาส</label>
                    <select class="form-select" id="quarter" name="quarter">
                        <option value="all" <?= ($quarter == 'all') ? 'selected' : ''; ?>>ทั้งหมด</option>
                        <option value="1" <?= ($quarter == '1') ? 'selected' : ''; ?>>ไตรมาส 1</option>
                        <option value="2" <?= ($quarter == '2') ? 'selected' : ''; ?>>ไตรมาส 2</option>
                        <option value="3" <?= ($quarter == '3') ? 'selected' : ''; ?>>ไตรมาส 3</option>
                        <option value="4" <?= ($quarter == '4') ? 'selected' : ''; ?>>ไตรมาส 4</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary mt-4">แสดงข้อมูล</button>
                </div>
            </div>
        </form>

        <!-- ข้อมูลยอดขายรวม -->
        <h3>ยอดขายรวม: ฿<?= number_format($totalSales, 2) ?></h3>

        <!-- กราฟยอดขาย -->
        <canvas id="salesChart"></canvas>

        <script>
            var ctx = document.getElementById('salesChart').getContext('2d');
            var salesData = <?php echo json_encode($salesData); ?>;
            
            var labels = salesData.map(function(sale) {
                return 'พนักงาน ' + sale.user_id;
            });

            var data = salesData.map(function(sale) {
                return sale.total_sales;
            });

            var chart = new Chart(ctx, {
                type: 'bar', // กราฟแท่ง
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ยอดขาย',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>

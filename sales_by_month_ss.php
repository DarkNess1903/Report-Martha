<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

$monthNames = [
    1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
    5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
    9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'
];

// ดึงชื่อพนักงาน
$stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($employee_name);
$stmt->fetch();
$stmt->close();

// ดึงยอดขายสินค้าเดือนนั้น
$stmt = $conn->prepare("SELECT product, SUM(amount) AS total_amount 
                        FROM sales 
                        WHERE user_id=? AND year=? AND month=? 
                        GROUP BY product 
                        ORDER BY total_amount DESC");
$stmt->bind_param("iii", $user_id, $year, $month);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
$amounts = [];
$total_month = 0;
while($row = $result->fetch_assoc()){
    $products[] = $row['product'];
    $amounts[] = floatval($row['total_amount']);
    $total_month += floatval($row['total_amount']);
}
$stmt->close();
$conn->close();

$monthName = $monthNames[$month] ?? "ไม่ระบุ";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ยอดขาย <?= htmlspecialchars($employee_name) ?> เดือน <?= $monthName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="container my-4">
 <!-- ปุ่มกลับ -->
    <div class="mb-3">
        <a href="sales_details.php?user_id=<?= $user_id ?>&year=<?= $year ?>" class="btn btn-outline-secondary">
            &laquo; กลับ
        </a>
    </div>

    <!-- Card กราฟและยอดรวม -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">ยอดขาย <?= htmlspecialchars($employee_name) ?> เดือน <?= $monthName ?> ปี <?= $year ?></h5>
            <h5 class="fw-bold"><?= number_format($total_month) ?> บาท</h5>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 400px; width: 100%;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ตารางยอดขายสินค้า -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">ตารางข้อมูลยอดขาย</h5>
        </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>สินค้า</th>
                            <th class="text-end">ยอดขาย (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $i => $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p) ?></td>
                                <td class="text-end"><?= number_format($amounts[$i]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td>รวมทั้งหมด</td>
                            <td class="text-end"><?= number_format($total_month) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
// กราฟพาสเทล
const ctx = document.getElementById('salesChart').getContext('2d');
const productLabels = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;
const productAmounts = <?= json_encode($amounts, JSON_NUMERIC_CHECK) ?>;

const pastelColors = [
    'rgba(255, 99, 132, 0.6)',
    'rgba(255, 159, 64, 0.6)',
    'rgba(255, 205, 86, 0.6)',
    'rgba(75, 192, 192, 0.6)',
    'rgba(54, 162, 235, 0.6)',
    'rgba(153, 102, 255, 0.6)',
    'rgba(201, 203, 207, 0.6)'
];
const pastelBorders = [
    'rgba(255, 99, 132, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(255, 205, 86, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(54, 162, 235, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(201, 203, 207, 1)'
];

const backgroundColors = productLabels.map((_, i) => pastelColors[i % pastelColors.length]);
const borderColors = productLabels.map((_, i) => pastelBorders[i % pastelBorders.length]);

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: productLabels,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: productAmounts,
            backgroundColor: backgroundColors,
            borderColor: borderColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // ❌ ปิดการบังคับสัดส่วน
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': ' + ctx.raw.toLocaleString() + ' บาท'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: value => value.toLocaleString() + ' บาท' }
            }
        }
    }
});
</script>

</body>
</html>

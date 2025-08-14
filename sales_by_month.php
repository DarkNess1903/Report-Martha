<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

$sql = "SELECT product, SUM(amount) AS total_amount
        FROM sales
        WHERE year = ? AND month = ?
        GROUP BY product
        ORDER BY total_amount DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
$amounts = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row['product'];
    $amounts[] = $row['total_amount'];
}

$thaiMonths = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$monthName = $thaiMonths[$month] ?? 'ไม่ระบุ';
$totalAmount = array_sum($amounts);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ยอดขายสินค้าประจำเดือน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ทำให้ตาราง scroll ในหน้าจอเล็ก */
        .table-responsive {
            overflow-x: auto;
        }
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">

    <!-- ปุ่มกลับ -->
    <div class="mb-3">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            ← กลับ
        </a>
    </div>

   <!-- กราฟยอดขาย -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">ยอดขายสินค้าทั้งหมด เดือน <?= $monthName ?> ปี <?= $year ?></h5>
            <h4 class="fw-bold"><?= number_format($totalAmount) ?> บาท</h4>
        </div>
        <div class="card-body" style="height: 500px;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>


    <!-- ตารางยอดขาย -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">ตารางข้อมูลยอดขาย</h5>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>สินค้า</th>
                        <th class="text-end">ยอดขาย (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product) ?></td>
                                <td class="text-end"><?= number_format($amounts[$index]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted">ไม่มีข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const productLabels = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;
const productAmounts = <?= json_encode($amounts, JSON_NUMERIC_CHECK) ?>;
const colors = productLabels.map((p, i) => `hsl(${(i * 360 / productLabels.length)}, 50%, 80%)`);
const borderColors = productLabels.map((p, i) => `hsl(${(i * 360 / productLabels.length)}, 50%, 70%)`);

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: productLabels,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: productAmounts,
            backgroundColor: colors,
            borderColor: borderColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.parsed.y.toLocaleString('th-TH') + ' บาท'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => value.toLocaleString('th-TH') + ' บาท'
                }
            }
        }
    }
});
</script>

</body>
</html>

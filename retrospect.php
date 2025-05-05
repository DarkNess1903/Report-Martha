<?php
session_start();
include 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// รับค่าปีที่เลือก หรือใช้ปีปัจจุบัน
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");
$current_year = date("Y");

// ดึงปีที่มีในฐานข้อมูล
$year_result = $conn->query("SELECT DISTINCT year FROM sales ORDER BY year DESC");
$years = [];
while ($row = $year_result->fetch_assoc()) {
    $years[] = $row['year'];
}

// ดึงข้อมูลยอดขายรวมรายเดือน รายไตรมาส และสินค้า ตามปีที่เลือก
$sql = "SELECT month, quarter, product, SUM(amount) AS total_amount
        FROM sales
        WHERE year = $selected_year
        GROUP BY month, quarter, product
        ORDER BY month";
$result = $conn->query($sql);

$monthly_data = [];
$quarterly_data = [];
$product_data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $month = $row['month'];
        $quarter = $row['quarter'];
        $product = $row['product'];
        $amount = $row['total_amount'];

        // รวมข้อมูลรายเดือน
        $monthly_data[$month] = ($monthly_data[$month] ?? 0) + $amount;

        // รวมข้อมูลรายไตรมาส
        $quarterly_data[$quarter][] = $amount;

        // รวมข้อมูลรายสินค้า
        $product_data[$product][$month] = $amount;
    }
}

// ดึงข้อมูลยอดขายย้อนหลัง 5 ปี (รวมปีปัจจุบัน)
$current_year = date("Y"); // เพิ่มตัวแปรปีปัจจุบัน
$past_years = [$current_year - 4, $current_year - 3, $current_year - 2, $current_year - 1, $current_year]; // 5 ปี
$past_years_data = [];

$sql = "SELECT year, month, SUM(amount) AS total 
        FROM sales 
        WHERE year IN (" . implode(",", $past_years) . ")
        GROUP BY year, month 
        ORDER BY year, month";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $year = $row['year'];
        $month = $row['month'];
        $amount = $row['total'];

        $past_years_data[$year][$month] = $amount;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<!-- Include Top Navbar -->
<?php include 'topnavbar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">แดชบอร์ดยอดขายย้อนหลัง</h2>

<!-- ฟอร์มเลือกปี -->
<div class="card shadow-sm mb-4 ">
    <div class="card-body">
        <form method="get" class="row align-items-center">
            <div class="col-md-3">
                <label for="yearSelect" class="form-label fw-bold">เลือกปี:</label>
            </div>
            <div class="col-md-6">
                <select name="year" id="yearSelect" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
<!-- กราฟยอดขาย -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-center">ยอดขายตามช่วงเวลา</h5>
                
                <!-- บรรทัดเดียวกัน: เลือกช่วงเวลา + ปุ่มขยาย -->
                <div class="row align-items-center mb-3">
                    <div class="col-8">
                        <label for="timePeriodSelect" class="form-label mb-1">เลือกช่วงเวลา:</label>
                        <select id="timePeriodSelect" class="form-select form-select-sm">
                            <option value="monthly">รายเดือน</option>
                            <option value="quarterly">รายไตรมาส</option>
                        </select>
                    </div>
                    <div class="col-4 text-end mt-4">
                        <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('timePeriodChart')">
                            <i class="fas fa-expand"></i> ขยาย
                        </button>
                    </div>
                </div>

                <canvas id="timePeriodChart"></canvas>
            </div>
        </div>
    </div>

    <!-- กราฟรายสินค้า -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-center">ยอดขายแยกตามสินค้า</h5>

                <!-- บรรทัดเดียวกัน: ปุ่มขยายเท่านั้น -->
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('productChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>

                <canvas id="productChart"></canvas>
            </div>
        </div>
    </div>
</div>

    <!-- กราฟเปรียบเทียบยอดขายย้อนหลัง 5 ปี -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-center">กราฟเปรียบเทียบยอดขายย้อนหลัง 5 ปี</h5>

                <!-- ปุ่มขยาย -->
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('pastYearsChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>

                <canvas id="pastYearsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงกราฟเต็มจอ -->
    <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- เปลี่ยนขนาดจาก fullscreen เป็น xl -->
            <div class="modal-content bg-white">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-4" id="chartModalLabel">กราฟแบบขยาย</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="w-100" style="height:500px;"> <!-- กำหนดความสูงกราฟ -->
                        <canvas id="fullScreenChart" style="width:100%; height:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
    // แปลงข้อมูลจาก PHP มาเป็น JavaScript object
    const monthlyData = <?= json_encode($monthly_data) ?>;
    const quarterlyData = <?= json_encode($quarterly_data) ?>;
    const productData = <?= json_encode($product_data) ?>;
    const pastYearsData = <?= json_encode($past_years_data) ?>;

    // ป้ายกำกับเดือนและไตรมาส
    const monthLabels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                         'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const shortMonthLabels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const quarterLabels = ['ไตรมาส 1', 'ไตรมาส 2', 'ไตรมาส 3', 'ไตรมาส 4'];

    // กราฟยอดขายตามช่วงเวลา (รายเดือน/รายไตรมาส)
    const ctxTime = document.getElementById('timePeriodChart').getContext('2d');
    const timePeriodChart = new Chart(ctxTime, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'ยอดขายรายเดือน',
                data: Array.from({ length: 12 }, (_, i) => monthlyData[i + 1] || 0),
                borderColor: 'rgba(75,192,192,1)',
                fill: false,
                tension: 0.3  // ทำให้เส้นโค้ง
            }]
        }
    });

    // กราฟยอดขายรายสินค้า
    const ctxProduct = document.getElementById('productChart').getContext('2d');
    const productChart = new Chart(ctxProduct, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: Object.keys(productData).map((product, i) => ({
                label: product,
                data: Array.from({ length: 12 }, (_, m) => productData[product][m + 1] || 0),
                borderColor: `hsl(${(i * 360 / Object.keys(productData).length)}, 70%, 50%)`,
                fill: false,
                tension: 0.3  // ทำให้เส้นโค้ง
            }))
        }
    });

    // กราฟเปลี่ยนระหว่างรายเดือนและรายไตรมาส
    document.getElementById('timePeriodSelect').addEventListener('change', function () {
        const period = this.value;

        if (period === 'monthly') {
            timePeriodChart.data.labels = monthLabels;
            timePeriodChart.data.datasets[0].label = 'ยอดขายรายเดือน';
            timePeriodChart.data.datasets[0].data = Array.from({ length: 12 }, (_, i) => monthlyData[i + 1] || 0);
        } else {
            timePeriodChart.data.labels = quarterLabels;
            timePeriodChart.data.datasets[0].label = 'ยอดขายรายไตรมาส';
            timePeriodChart.data.datasets[0].data = [1, 2, 3, 4].map(q => {
                const data = quarterlyData[q] || [];
                return data.reduce((a, b) => a + b, 0);
            });
        }

        timePeriodChart.update();
    });

    // กราฟแสดงยอดขายย้อนหลัง 5 ปี
    const ctxPastYears = document.getElementById('pastYearsChart').getContext('2d');
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']; // เพิ่มสีสำหรับปี 5 ปี

    // กราฟย้อนหลัง 5 ปี
    const pastYearsChart = new Chart(ctxPastYears, {
        type: 'line',
        data: {
            labels: shortMonthLabels,
            datasets: Object.keys(pastYearsData).map((year, idx) => ({
                label: 'ปี ' + year,
                data: Array.from({ length: 12 }, (_, m) => pastYearsData[year][m + 1] || 0),
                borderColor: colors[idx % colors.length],
                fill: false,
                tension: 0.3  // ทำให้เส้นโค้ง
            }))
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'ยอดขายย้อนหลัง 5 ปี'
                }
            }
        }
    });

    // ตรวจสอบข้อมูลที่ส่งมาจาก PHP
    console.log(pastYearsData);
    
    let fullScreenChartInstance;

    function showFullScreenChart(originalChartId) {
        const originalChart = Chart.getChart(originalChartId);
        if (!originalChart) return;

        if (fullScreenChartInstance) {
            fullScreenChartInstance.destroy();
        }

        const ctx = document.getElementById('fullScreenChart').getContext('2d');

        fullScreenChartInstance = new Chart(ctx, {
            type: originalChart.config.type,
            data: JSON.parse(JSON.stringify(originalChart.data)),
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                size: 16 // เพิ่มขนาดตัวอักษรของ legend
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: originalChart.options.plugins?.title?.text || 'กราฟ',
                        font: {
                            size: 20 // ขนาดหัวข้อกราฟ
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 14 // แกน X
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 14 // แกน Y
                            }
                        }
                    }
                }
            }
        });

        const modal = new bootstrap.Modal(document.getElementById('chartModal'));
        modal.show();
    }


    document.getElementById('chartModal').addEventListener('shown.bs.modal', () => {
        if (fullScreenChartInstance) {
            fullScreenChartInstance.resize();
        }
    });

</script>

<!-- เชื่อมต่อกับ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

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
        $month = $row['month'] ?? null;
        $quarter = $row['quarter'] ?? null;
        $product = $row['product'] ?? '';
        $amount = $row['total_amount'] ?? 0;

        // รวมข้อมูลรายเดือน
        if (!is_null($month)) {
            $monthly_data[$month] = ($monthly_data[$month] ?? 0) + $amount;
        }

        // รวมข้อมูลรายไตรมาส (ถ้ามีค่า)
        if (!is_null($quarter)) {
            $quarterly_data[$quarter] = ($quarterly_data[$quarter] ?? 0) + $amount;
        }

        // รวมข้อมูลรายสินค้า
        if (!is_null($month)) {
            $product_data[$product][$month] = $amount;
        }
    }
}

// ดึงยอดขายรวมของพนักงานแต่ละคนในปีที่เลือก
$sql = "SELECT u.username AS employee_name, SUM(s.amount) AS total_sales
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.year = ?
        GROUP BY s.user_id
        ORDER BY total_sales DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

$employee_labels = [];
$employee_sales = [];

while ($row = $result->fetch_assoc()) {
    $employee_labels[] = $row['employee_name'];
    $employee_sales[] = $row['total_sales'];
}

// ดึงสินค้าขายดี/ขายไม่ดี 5 อันดับของปีที่เลือก
$sql = "SELECT product, SUM(amount) AS total_sales
        FROM sales
        WHERE year = ?
        GROUP BY product
        ORDER BY total_sales DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

$top_products = [];
$bottom_products = [];

while ($row = $result->fetch_assoc()) {
    $top_products[] = $row;
}

// แยก 5 อันดับแรกและ 5 อันดับล่าง (ถ้ามีมากพอ)
$top_5_products = array_slice($top_products, 0, 5);
$bottom_5_products = array_slice(array_reverse($top_products), 0, 5);

// ยอดขายรวมทั้งปี
$total_sales_year = array_sum($monthly_data);

// ดึงยอดขายรวมของพนักงานแต่ละคนในปีที่เลือก พร้อม user_id
$sql = "SELECT u.id AS user_id, u.username AS employee_name, SUM(s.amount) AS total_sales
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.year = ?
        GROUP BY s.user_id
        ORDER BY total_sales DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

$employee_labels = [];
$employee_sales = [];
$employee_ids = []; // เพิ่ม array เก็บ user_id

while ($row = $result->fetch_assoc()) {
    $employee_ids[] = $row['user_id'];          // เก็บ user_id
    $employee_labels[] = $row['employee_name']; // เก็บชื่อพนักงาน
    $employee_sales[] = $row['total_sales'];    // ยอดขายรวม
}

$stmt->close();
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
    <style>
    /* เอฟเฟกต์เบลอพื้นหลังเมื่อเปิด modal */
    .modal-backdrop.show {
        backdrop-filter: blur(4px);
    }

    /* ปรับปุ่มปิดให้มี hover effect */
    .btn-close:hover {
        transform: scale(1.1);
    }

    /* ปรับความโค้งและเงาใน canvas */
    #fullScreenChart {
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 10px;
        background-color: white;
    }
</style>

</head>
<body>
    <!-- Include Top Navbar -->
    <?php include 'topnavbar.php'; ?>

<div class="container mt-5">

    <!-- ฟอร์มเลือกปี -->
    <div class="card shadow-sm mb-4">
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
            <!-- แสดงยอดขายรวมของปี -->
            <div class="mt-3 text-center">
                <h4 class="fw-bold text-success">
                    ยอดขายรวมปี <?= $selected_year ?>: <?= number_format($total_sales_year, 2) ?> บาท
                </h4>
            </div>
        </div>
    </div>

    <!-- 1. กราฟยอดขายพนักงาน -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">ยอดขายพนักงาน ปี <?= $selected_year ?></h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('employeeSalesChart')">
                            <i class="fas fa-expand"></i> ขยาย
                        </button>
                    </div>
                    <canvas id="employeeSalesChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. กราฟยอดขายตามช่วงเวลา -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-center">ยอดขายตามช่วงเวลา</h5>
                    <div class="row align-items-center mb-3">
                        <div class="col-8">
                            <label for="timePeriodSelect" class="form-label mb-1">เลือกช่วงเวลา:</label>
                            <select id="timePeriodSelect" class="form-select form-select-sm">
                                <option value="monthly">รายเดือน</option>
                                <option value="quarterly">รายไตรมาส</option>
                            </select>
                        </div>
                        <div class="col-4 text-end mt-4">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('timePeriodChart')">
                                <i class="fas fa-expand"></i> ขยาย
                            </button>
                        </div>
                    </div>
                    <canvas id="timePeriodChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. กราฟ stacked bar ยอดขายสินค้า -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-center">ยอดขายแยกตามสินค้า</h5>
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('productChart')">
                            <i class="fas fa-expand"></i> ขยาย
                        </button>
                    </div>
                    <canvas id="productChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. กราฟอันดับสินค้า -->
    <div class="row mb-4">
        <!-- ขายดี -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100 position-relative">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('topProductsChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>
                <h5 class="text-center">สินค้าขายดี 5 อันดับ</h5>
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        <!-- ขายไม่ดี -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3 h-100 position-relative">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('bottomProductsChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>
                <h5 class="text-center">สินค้าขายไม่ดี 5 อันดับ</h5>
                <canvas id="bottomProductsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงกราฟเต็มจอ -->
<div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 rounded-top-4 px-4">
                <h5 class="modal-title fw-bold fs-4" id="chartModalLabel">
                    <i class="fas fa-chart-bar me-2 text-primary"></i> กราฟแบบขยาย
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body p-3 bg-white">
                <div class="w-100 rounded-3" style="height: 80vh; min-height: 300px;">
                    <canvas id="fullScreenChart" style="width: 100%; height: 100%;"></canvas>
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
    const top5Products = <?= json_encode($top_5_products) ?>;
    const bottom5Products = <?= json_encode($bottom_5_products) ?>;

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

    const ctxProduct = document.getElementById('productChart').getContext('2d');
    const productNames = Object.keys(productData);

    const productChart = new Chart(ctxProduct, {
        type: 'bar',
        data: {
            labels: monthLabels, // ["ม.ค.", "ก.พ.", ..., "ธ.ค."]
            datasets: productNames.map((product, i) => ({
                label: product,
                data: Array.from({ length: 12 }, (_, m) => productData[product][m + 1] || 0),
                // สีพาสเทล
                backgroundColor: `hsl(${(i * 360 / productNames.length)}, 50%, 80%)`,
                borderColor: `hsl(${(i * 360 / productNames.length)}, 50%, 70%)`,
                borderWidth: 1
            }))
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: { size: 10 }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('th-TH') + ' บาท'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: value => value.toLocaleString('th-TH') + ' บาท'
                    }
                }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const monthIndex = elements[0].index; // ตำแหน่งเดือนที่คลิก (0-11)
                    const monthNumber = monthIndex + 1;   // แปลงเป็น 1-12
                    const year = <?= $selected_year ?>;   // ดึงค่าปีจาก PHP

                    // ไปหน้าแสดงสินค้าของเดือนนั้น
                    window.location.href = `sales_by_month.php?year=${year}&month=${monthNumber}`;
                }
            }
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
            timePeriodChart.data.datasets[0].data = [1, 2, 3, 4].map(q => quarterlyData[q] || 0);
        }

        timePeriodChart.update();
    });

    //กราฟอันดับ
    function getColorPalette(count) {
        return Array.from({length: count}, (_, i) => `hsl(${i * 360 / count}, 70%, 70%)`);
    }

    // กราฟสินค้าขายดี
    const ctxTop = document.getElementById('topProductsChart').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: top5Products.map(item => item.product),
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: top5Products.map(item => item.total_sales),
                backgroundColor: getColorPalette(top5Products.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => new Intl.NumberFormat('th-TH').format(value) + ' บาท'
                    }
                }
            }
        }
    });

    // กราฟสินค้าขายไม่ดี
    const ctxBottom = document.getElementById('bottomProductsChart').getContext('2d');
    new Chart(ctxBottom, {
        type: 'bar',
        data: {
            labels: bottom5Products.map(item => item.product),
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: bottom5Products.map(item => item.total_sales),
                backgroundColor: getColorPalette(bottom5Products.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => new Intl.NumberFormat('th-TH').format(value) + ' บาท'
                    }
                }
            }
        }
    });
    
        // รับค่า user_id และ ปีจาก PHP
        const employeeIds = <?= json_encode($employee_ids) ?>;
        const selectedYear = <?= json_encode($selected_year) ?>;

        // ฟังก์ชันสุ่มสีพาสเทล (ของเดิม)
        function getRandomPastelColor() {
            const hue = Math.floor(Math.random() * 360);
            const saturation = 70;
            const lightness = 70;
            return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
        }

        // สร้าง array สี
        const employeeColors = <?= json_encode($employee_labels) ?>.map(() => getRandomPastelColor());

        // สร้างกราฟแท่ง
        const employeeSalesCtx = document.getElementById('employeeSalesChart').getContext('2d');
        const employeeSalesChart = new Chart(employeeSalesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($employee_labels) ?>,
                datasets: [{
                    label: 'ยอดขายรวม (บาท)',
                    data: <?= json_encode($employee_sales) ?>,
                    backgroundColor: employeeColors.map(c => c.replace('hsl', 'hsla').replace(')', ', 0.7)')),
                    borderColor: employeeColors.map(c => c.replace('hsl', 'hsla').replace(')', ', 1)')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('th-TH').format(value) + ' บาท';
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.formattedValue + ' บาท';
                            }
                        }
                    }
                },
                // เพิ่ม event onClick
                onClick: function(evt, elements) {
                    if (elements.length > 0) {
                        const chartElement = elements[0];
                        const index = chartElement.index;
                        const userId = employeeIds[index];
                        if (userId) {
                            const url = `sales_details.php?user_id=${userId}&year=${selectedYear}`;
                            window.location.href = url;
                        }
                    }
                }
            }
        });
    
    let fullScreenChartInstance;

    function showFullScreenChart(originalChartId) {
        const originalChart = Chart.getChart(originalChartId);
        if (!originalChart) return;

        // ทำลายอินสแตนซ์เก่า
        if (fullScreenChartInstance) {
            fullScreenChartInstance.destroy();
        }

        const ctx = document.getElementById('fullScreenChart').getContext('2d');

        // สร้างสำเนา config เพื่อไม่กระทบต้นฉบับ
        const clonedData = JSON.parse(JSON.stringify(originalChart.data));
        const clonedOptions = JSON.parse(JSON.stringify(originalChart.options || {}));

        // ปรับขนาด legend, tooltip, title, ticks ให้อ่านง่ายบนหน้าจอใหญ่
        clonedOptions.plugins = clonedOptions.plugins || {};
        clonedOptions.plugins.legend = {
            display: true,
            position: 'top',
            labels: {
                font: {
                    size: 16
                }
            }
        };
        clonedOptions.plugins.tooltip = {
            mode: 'index',
            intersect: false,
            bodyFont: {
                size: 16
            },
            callbacks: originalChart.options.plugins?.tooltip?.callbacks || {}
        };
        clonedOptions.plugins.title = {
            display: true,
            text: originalChart.options.plugins?.title?.text || 'กราฟ',
            font: {
                size: 20,
                weight: 'bold'
            },
            padding: {
                top: 10,
                bottom: 20
            }
        };

        // ปรับแกน
        if (clonedOptions.scales) {
            if (clonedOptions.scales.x?.ticks) {
                clonedOptions.scales.x.ticks.font = { size: 14 };
            }
            if (clonedOptions.scales.y?.ticks) {
                clonedOptions.scales.y.ticks.font = { size: 14 };
            }
        }

        clonedOptions.maintainAspectRatio = false;
        clonedOptions.responsive = true;

        // สร้างกราฟใหม่
        fullScreenChartInstance = new Chart(ctx, {
            type: originalChart.config.type,
            data: clonedData,
            options: clonedOptions
        });

        const modal = new bootstrap.Modal(document.getElementById('chartModal'));
        modal.show();
    }

    document.getElementById('chartModal').addEventListener('shown.bs.modal', () => {
        setTimeout(() => {
            if (fullScreenChartInstance) {
                fullScreenChartInstance.resize();
            }
        }, 200); // รอ modal เปิดก่อนเล็กน้อย
    });
</script>

<!-- เชื่อมต่อกับ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

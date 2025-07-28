<?php
session_start();
include 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ปีปัจจุบันและรายการปีที่มีในฐานข้อมูล
$currentYear = date('Y');
$currentMonth = date('n');

$yearsAvailable = [];
$sqlYears = "SELECT DISTINCT year FROM sales ORDER BY year DESC";
$result = $conn->query($sqlYears);
while ($row = $result->fetch_assoc()) {
    $yearsAvailable[] = $row['year'];
}

// รับค่าปีจาก GET
$year1 = isset($_GET['year1']) ? intval($_GET['year1']) : $currentYear;
$year2 = isset($_GET['year2']) ? intval($_GET['year2']) : $currentYear - 1;

// ดึงยอดขายรายเดือนของทั้งสองปี
$monthlySalesYear1 = array_fill(1, 12, 0);
$monthlySalesYear2 = array_fill(1, 12, 0);

$sql = "SELECT month, SUM(amount) AS total_amount FROM sales WHERE year = ? GROUP BY month";
$stmt = $conn->prepare($sql);

// ปีที่ 1
$stmt->bind_param("i", $year1);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthlySalesYear1[intval($row['month'])] = $row['total_amount'];
}

// ปีที่ 2
$stmt->bind_param("i", $year2);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthlySalesYear2[intval($row['month'])] = $row['total_amount'];
}

// ชื่อเดือน
$monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

// ฟังก์ชันย่อรูปแบบจำนวนเงิน
function formatShortNumber($number, $precision = 2) {
    if ($number >= 1000000) {
        return number_format($number / 1000000, $precision) . 'M';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, $precision) . 'K';
    } else {
        return number_format($number, $precision);
    }
}

$year1 = isset($_GET['year1']) ? intval($_GET['year1']) : date('Y') - 1;
$year2 = isset($_GET['year2']) ? intval($_GET['year2']) : date('Y');

// ดึงยอดขายรายเดือน
$sqlMonthly = "SELECT year, month, SUM(amount) AS total_amount FROM sales WHERE year IN (?, ?) GROUP BY year, month";
$stmt = $conn->prepare($sqlMonthly);
$stmt->bind_param("ii", $year1, $year2);
$stmt->execute();
$result = $stmt->get_result();

$monthlyData = [
    $year1 => array_fill(1, 12, 0),
    $year2 => array_fill(1, 12, 0),
];
while ($row = $result->fetch_assoc()) {
    $monthlyData[intval($row['year'])][intval($row['month'])] = floatval($row['total_amount']);
}

// สินค้าขายดี 5 อันดับ
function getTopProducts($conn, $year, $limit = 5, $asc = false) {
    $order = $asc ? "ASC" : "DESC";
    $stmt = $conn->prepare("SELECT product, SUM(amount) AS total_amount FROM sales WHERE year = ? GROUP BY product ORDER BY total_amount $order LIMIT $limit");
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $res = $stmt->get_result();

    $labels = [];
    $values = [];
    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['product'];
        $values[] = $row['total_amount'];
    }
    return [$labels, $values];
}

list($topProducts1, $topAmounts1) = getTopProducts($conn, $year1);
list($topProducts2, $topAmounts2) = getTopProducts($conn, $year2);

list($worstProducts1, $worstAmounts1) = getTopProducts($conn, $year1, 5, true);
list($worstProducts2, $worstAmounts2) = getTopProducts($conn, $year2, 5, true);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>แดชบอร์ดรวมยอดขาย</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Font Awesome ล่าสุด -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Include Top Navbar -->
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5 mb-5">
    <?php if (!empty($errorMsg)) : ?>
        <div class="alert alert-warning text-center py-2">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- ฟอร์มเลือกปีเปรียบเทียบ -->
    <form method="GET" class="row g-3 align-items-end justify-content-center mb-4">
        <div class="col-12 col-md-3">
            <label for="year1" class="form-label fw-semibold mb-1">ปีที่ 1</label>
            <select name="year1" id="year1" class="form-select form-select-sm">
                <?php foreach ($yearsAvailable as $y): ?>
                    <option value="<?= htmlspecialchars($y) ?>" <?= $y == $year1 ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-3">
            <label for="year2" class="form-label fw-semibold mb-1">ปีที่ 2</label>
            <select name="year2" id="year2" class="form-select form-select-sm">
                <?php foreach ($yearsAvailable as $y): ?>
                    <option value="<?= htmlspecialchars($y) ?>" <?= $y == $year2 ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-2 d-grid">
            <button type="submit" class="btn btn-primary btn-sm">
                เปรียบเทียบ
            </button>
        </div>
    </form>
    
        <!-- หัวข้อ -->
        <h5 class="fw-bold text-center mb-3">
            เปรียบเทียบยอดขายรายเดือน ( ปี <?= $year1 ?> vs <?= $year2 ?>)
        </h5>

        <!-- ตารางยอดขาย -->
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>เดือน</th>
                        <?php foreach ($monthNames as $month): ?>
                            <th><?= $month ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- ปีที่ 1 -->
                    <tr>
                        <td class="fw-bold text-primary"><?= $year1 ?></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td title="<?= number_format($monthlySalesYear1[$m], 2) ?> บาท">
                                <?= formatShortNumber($monthlySalesYear1[$m] ?? 0) ?>฿
                            </td>
                        <?php endfor; ?>
                    </tr>

                    <!-- ปีที่ 2 -->
                    <tr>
                        <td class="fw-bold text-secondary"><?= $year2 ?></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td title="<?= number_format($monthlySalesYear2[$m], 2) ?> บาท">
                                <?= formatShortNumber($monthlySalesYear2[$m] ?? 0) ?>฿
                            </td>
                        <?php endfor; ?>
                    </tr>

                    <!-- การเปลี่ยนแปลง -->
                    <tr>
                        <td class="fw-bold">การเปลี่ยนแปลง</td>
                        <?php for ($m = 1; $m <= 12; $m++):
                            $sale1 = $monthlySalesYear1[$m] ?? 0;
                            $sale2 = $monthlySalesYear2[$m] ?? 0;

                            if ($sale2 == 0 && $sale1 == 0) {
                                $growth = 0;
                                $class = 'text-muted';
                                $icon = '<i class="fas fa-minus"></i>';
                            } elseif ($sale2 == 0 && $sale1 > 0) {
                                $growth = 100;
                                $class = 'text-success fw-bold';
                                $icon = '<i class="fas fa-arrow-up"></i>';
                            } else {
                                $growth = (($sale1 - $sale2) / $sale2) * 100;
                                $class = $growth >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
                                $icon = $growth >= 0 ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>';
                            }
                        ?>
                            <td class="<?= $class ?>">
                                <?= $icon ?> <?= number_format($growth, 2) ?>%
                            </td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="row g-4">
            <!-- กราฟเปรียบเทียบยอดขายรายเดือน -->
            <div class="col-12">
                <div class="card shadow-sm border position-relative">
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" 
                            style="top: 10px; right: 10px; z-index: 10;"
                            onclick="showFullScreenChart('monthlyCompareChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body">
                        <h5 class="card-title text-center fw-bold mb-3">เปรียบเทียบยอดขายรายเดือน</h5>
                        <canvas id="monthlyCompareChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟสินค้าขายดีปีที่ 1 -->
            <div class="col-md-6">
                <div class="card shadow-sm border position-relative">
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" 
                            style="top: 10px; right: 10px; z-index: 10;"
                            onclick="showFullScreenChart('topYear1')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body">
                        <h6 class="card-title text-center fw-semibold mb-3">สินค้าขายดีปี <?= $year1 ?></h6>
                        <canvas id="topYear1" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟสินค้าขายดีปีที่ 2 -->
            <div class="col-md-6">
                <div class="card shadow-sm border position-relative">
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" 
                            style="top: 10px; right: 10px; z-index: 10;"
                            onclick="showFullScreenChart('topYear2')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body">
                        <h6 class="card-title text-center fw-semibold mb-3">สินค้าขายดีปี <?= $year2 ?></h6>
                        <canvas id="topYear2" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟสินค้าขายได้น้อยปีที่ 1 -->
            <div class="col-md-6">
                <div class="card shadow-sm border position-relative">
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" 
                            style="top: 10px; right: 10px; z-index: 10;"
                            onclick="showFullScreenChart('worstYear1')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body">
                        <h6 class="card-title text-center fw-semibold mb-3">สินค้าขายได้น้อยปี <?= $year1 ?></h6>
                        <canvas id="worstYear1" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟสินค้าขายได้น้อยปีที่ 2 -->
            <div class="col-md-6">
                <div class="card shadow-sm border position-relative">
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" 
                            style="top: 10px; right: 10px; z-index: 10;"
                            onclick="showFullScreenChart('worstYear2')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body">
                        <h6 class="card-title text-center fw-semibold mb-3">สินค้าขายได้น้อยปี <?= $year2 ?></h6>
                        <canvas id="worstYear2" height="100"></canvas>
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
</body>

<script>
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

const year1 = <?= $year1 ?>;
const year2 = <?= $year2 ?>;

const monthLabels = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

// ----------- เปรียบเทียบยอดขายรายเดือน (เดิม ไม่ต้องเปลี่ยน) -----------
const monthlyData = <?= json_encode($monthlyData) ?>;
new Chart(document.getElementById('monthlyCompareChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'ปี ' + year1,
                data: Object.values(monthlyData[year1]),
                borderColor: '#007bff',
                backgroundColor: '#007bff22',
                fill: false,
                tension: 0.3
            },
            {
                label: 'ปี ' + year2,
                data: Object.values(monthlyData[year2]),
                borderColor: '#ff5722',
                backgroundColor: '#ff572222',
                fill: false,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'เปรียบเทียบยอดขายรายเดือน'
            },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('th-TH') + ' บาท'
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

// ----------- ข้อมูลสินค้า -----------
const topProducts1 = <?= json_encode($topProducts1) ?>;
const topAmounts1 = <?= json_encode($topAmounts1) ?>;
const topProducts2 = <?= json_encode($topProducts2) ?>;
const topAmounts2 = <?= json_encode($topAmounts2) ?>;

const worstProducts1 = <?= json_encode($worstProducts1) ?>;
const worstAmounts1 = <?= json_encode($worstAmounts1) ?>;
const worstProducts2 = <?= json_encode($worstProducts2) ?>;
const worstAmounts2 = <?= json_encode($worstAmounts2) ?>;

// ----------- รวมชื่อสินค้าทั้งหมดและสร้างสีสุ่มแบบ pastel โดยใช้ Map -----------
const allProducts = [...new Set([
    ...topProducts1, ...topProducts2,
    ...worstProducts1, ...worstProducts2
])];

// สร้างสี pastel สบายตา
function getPastelColor(seed) {
    const hue = Math.abs(seed.split('').reduce((a, c) => a + c.charCodeAt(0), 0)) % 360;
    return `hsl(${hue}, 70%, 70%)`;
}

// map สินค้า → สี
const productColorMap = {};
allProducts.forEach(product => {
    productColorMap[product] = getPastelColor(product);
});

// ----------- ฟังก์ชันสร้างกราฟแท่ง -----------
function createBarChart(canvasId, title, labels, data) {
    const colors = labels.map(product => productColorMap[product]);
    new Chart(document.getElementById(canvasId), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: title,
                data: data,
                backgroundColor: colors,
                borderColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: title
                },
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
                        callback: val => val.toLocaleString('th-TH') + ' บาท'
                    }
                }
            }
        }
    });
}

// ----------- เรียกใช้สร้างกราฟทั้ง 4 ตัว -----------
createBarChart('topYear1', 'สินค้าขายดีปี ' + year1, topProducts1, topAmounts1);
createBarChart('topYear2', 'สินค้าขายดีปี ' + year2, topProducts2, topAmounts2);
createBarChart('worstYear1', 'สินค้าขายได้น้อยปี ' + year1, worstProducts1, worstAmounts1);
createBarChart('worstYear2', 'สินค้าขายได้น้อยปี ' + year2, worstProducts2, worstAmounts2);
</script>

</body>
</html>

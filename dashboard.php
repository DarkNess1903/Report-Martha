<?php
session_start();
include 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentYear = date('Y');
$currentMonth = date('n');

$yearsAvailable = [];
$sqlYears = "SELECT DISTINCT year FROM sales ORDER BY year DESC";
$result = $conn->query($sqlYears);
while ($row = $result->fetch_assoc()) {
    $yearsAvailable[] = $row['year'];
}

// ปีที่เลือก (ดึงจาก GET หรือใช้ค่าปัจจุบันและปีก่อนหน้า)
$year1 = isset($_GET['year1']) ? intval($_GET['year1']) : $currentYear;
$year2 = isset($_GET['year2']) ? intval($_GET['year2']) : $currentYear - 1;

// ดึงข้อมูลยอดขายของปีที่เลือก
$monthlySalesYear1 = array_fill(1, 12, 0);
$monthlySalesYear2 = array_fill(1, 12, 0);

$sql = "SELECT month, SUM(amount) AS total_amount FROM sales WHERE year = ? GROUP BY month";
$stmt = $conn->prepare($sql);

// ปี 1
$stmt->bind_param("i", $year1);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthlySalesYear1[intval($row['month'])] = $row['total_amount'];
}

// ปี 2
$stmt->bind_param("i", $year2);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthlySalesYear2[intval($row['month'])] = $row['total_amount'];
}

$currentYear = date('Y');
$currentMonth = date('n');
$lastMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$lastMonthYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

// กำหนดชื่อเดือนภาษาไทย
$monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

// ดึงยอดขายรวมปีปัจจุบัน
$sqlTotalSales = "SELECT SUM(amount) AS total_sales FROM sales WHERE year = ?";
$stmt = $conn->prepare($sqlTotalSales);
$stmt->bind_param("i", $currentYear);
$stmt->execute();
$totalSalesResult = $stmt->get_result()->fetch_assoc();
$totalSales = $totalSalesResult['total_sales'] ?? 0;

$lastYear = $currentYear - 1;

// ดึงยอดขายรายเดือนของปีปัจจุบัน
$sqlMonthlySales = "
    SELECT month, SUM(amount) AS total_amount
    FROM sales
    WHERE year = ?
    GROUP BY month
";
$stmt = $conn->prepare($sqlMonthlySales);

// ปีปัจจุบัน
$stmt->bind_param("i", $currentYear);
$stmt->execute();
$result = $stmt->get_result();
$monthlySalesCurrentYear = array_fill(1, 12, 0);
while ($row = $result->fetch_assoc()) {
    $monthlySalesCurrentYear[intval($row['month'])] = $row['total_amount'];
}

// ปีก่อนหน้า
$stmt->bind_param("i", $lastYear);
$stmt->execute();
$result = $stmt->get_result();
$monthlySalesLastYear = array_fill(1, 12, 0);
while ($row = $result->fetch_assoc()) {
    $monthlySalesLastYear[intval($row['month'])] = $row['total_amount'];
}

// คำนวณยอดขายเดือนนี้และเดือนก่อนหน้า สำหรับคำนวณ % เติบโต
$salesThisMonth = $monthlySalesCurrentYear[$currentMonth] ?? 0;

// เดือนก่อนหน้า (ถ้าเดือนนี้เป็น มกราคม ให้ใช้ธันวาคมของปีก่อนหน้า)
$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevMonthYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

if ($prevMonthYear == $currentYear) {
    $salesLastMonth = $monthlySalesCurrentYear[$prevMonth] ?? 0;
} else {
    // ถ้าเดือนก่อนหน้าอยู่ปีที่แล้ว ใช้ข้อมูลจาก $monthlySalesLastYear
    $salesLastMonth = $monthlySalesLastYear[$prevMonth] ?? 0;
}

// คำนวณ % การเติบโต
if ($salesLastMonth == 0) {
    $growthPercent = ($salesThisMonth > 0) ? 100 : 0;
} else {
    $growthPercent = (($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100;
}

$growthPercent = round($growthPercent, 2);

// ดึงยอดขายย้อนหลัง 5 ปี (รายเดือน)
$startYear = $currentYear - 4;
$sqlPastYears = "
    SELECT year, month, SUM(amount) AS total_amount
    FROM sales
    WHERE year BETWEEN ? AND ?
    GROUP BY year, month
    ORDER BY year ASC, month ASC
";
$stmt = $conn->prepare($sqlPastYears);
$stmt->bind_param("ii", $startYear, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

$pastYearsData = [];
while ($row = $result->fetch_assoc()) {
    $y = $row['year'];
    $m = $row['month'];
    $amt = $row['total_amount'];

    if (!isset($pastYearsData[$y])) {
        $pastYearsData[$y] = array_fill(1, 12, 0); // เตรียมเดือน 1-12
    }
    $pastYearsData[$y][$m] = $amt;
}

// เติมข้อมูลปีที่ไม่มีข้อมูลเป็น 0 เต็ม 12 เดือนด้วย
for ($y = $startYear; $y <= $currentYear; $y++) {
    if (!isset($pastYearsData[$y])) {   
        $pastYearsData[$y] = array_fill(1, 12, 0);
    }
}
$maxSaleCurrentYear = max($monthlySalesCurrentYear);
$maxSaleLastYear = max($monthlySalesLastYear);

$totalSalesLastYear = array_sum($monthlySalesLastYear);
$yearlyGrowthPercent = $totalSalesLastYear == 0 
    ? 100 
    : round((($totalSales - $totalSalesLastYear) / $totalSalesLastYear) * 100, 2);

function formatShortNumber($number, $precision = 2) {
    if ($number >= 1000000) {
        return number_format($number / 1000000, $precision) . 'M';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, $precision) . 'K';
    } else {
        return number_format($number, $precision);
    }
}

// ดึงสินค้าขายดี 5 อันดับ
$sqlTopProducts = "
    SELECT product, SUM(amount) AS total_amount
    FROM sales
    GROUP BY product
    ORDER BY total_amount DESC
    LIMIT 5
";
$topProducts = [];
$topAmounts = [];
$result = $conn->query($sqlTopProducts);
while ($row = $result->fetch_assoc()) {
    $topProducts[] = $row['product'];
    $topAmounts[] = $row['total_amount'];
}

// ดึงสินค้าขายไม่ดี 5 อันดับ
$sqlWorstProducts = "
    SELECT product, SUM(amount) AS total_amount
    FROM sales
    GROUP BY product
    ORDER BY total_amount ASC
    LIMIT 5
";
$worstProducts = [];
$worstAmounts = [];
$result = $conn->query($sqlWorstProducts);
while ($row = $result->fetch_assoc()) {
    $worstProducts[] = $row['product'];
    $worstAmounts[] = $row['total_amount'];
}

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
            เปรียบเทียบยอดขายรายเดือน (ปี <?= $year1 ?> vs <?= $year2 ?>)
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

        <!-- กราฟสินค้าขายดี และขายไม่ดี -->
        <div class="row">
            <!-- ขายดี -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card shadow-sm h-100 position-relative">
                    <!-- ปุ่มขยายบนมุมขวาบน -->
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" style="top: 10px; right: 10px; z-index: 10;"
                        onclick="showFullScreenChart('topProductsChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body pt-4">
                        <h5 class="text-center">สินค้าขายดี 5 อันดับ (รวมทุกปี)</h5>
                        <canvas id="topProductsChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- ขายไม่ดี -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card shadow-sm h-100 position-relative">
                    <!-- ปุ่มขยายบนมุมขวาบน -->
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" style="top: 10px; right: 10px; z-index: 10;"
                        onclick="showFullScreenChart('worstProductsChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <div class="card-body pt-4">
                        <h5 class="text-center">สินค้าขายไม่ดี 5 อันดับ (รวมทุกปี)</h5>
                        <canvas id="worstProductsChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟยอดขายย้อนหลัง -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm p-4 mb-5 position-relative">
                    <!-- ปุ่มขยายบนมุมขวาบน -->
                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute" style="top: 10px; right: 10px; z-index: 10;"
                        onclick="showFullScreenChart('salesTrendChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                    <h5 class="text-center mb-3">ยอดขายย้อนหลัง 5 ปี (รายเดือน)</h5>
                    <div class="chart-container" style="position: relative; height:350px; max-height: 450px;">
                        <canvas id="salesTrendChart"></canvas>
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
    const pastYearsData = <?= json_encode($pastYearsData) ?>;
    const currentYear = <?= $currentYear ?>;

    // สร้าง array ปี (เรียงจากใหม่ไปเก่า)
    const years = Object.keys(pastYearsData).sort((a, b) => b - a);

    // ป้ายเดือนภาษาไทย
    const monthLabels =  ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    // สร้าง datasets สำหรับแต่ละปี (เรียงปีจากใหม่ไปเก่า)
    const datasets = years.map((year, idx) => {
        const data = pastYearsData[year];
        // สร้างสีโทนสบายตา (pastel)
        const hue = (idx * 360 / years.length);
        const color = `hsl(${hue}, 70%, 70%)`;
        const borderColor = `hsl(${hue}, 70%, 40%)`;

        // ถ้าเป็นปีปัจจุบันเน้นสีเข้มกว่า
        const backgroundColor = year == currentYear ? borderColor : color;

        return {
            label: 'ปี ' + year,
            data: Object.values(data),
            borderColor: borderColor,
            backgroundColor: backgroundColor,
            fill: false,
            tension: 0.3,
            borderWidth: year == currentYear ? 3 : 2,
            pointRadius: year == currentYear ? 5 : 3,
            hidden: false,
        };
    });

    const ctx = document.getElementById('salesTrendChart').getContext('2d');

    const salesTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // <== เพิ่มบรรทัดนี้
            interaction: {
                mode: 'nearest',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'ยอดขายย้อนหลัง 5 ปี (รายเดือน)'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('th-TH') + ' บาท';
                        }
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
        
    // ฟังก์ชันสุ่มสีสวยงาม
    function generateColors(count, base = 'top') {
        const colors = [];
        const colorPool = base === 'top'
            ? ['#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236']
            : ['#ff6384', '#ff9f40', '#ffcd56', '#4bc0c0', '#9966ff'];
        for (let i = 0; i < count; i++) {
            colors.push(colorPool[i % colorPool.length]);
        }
        return colors;
    }

    const topProducts = <?= json_encode($topProducts) ?>;
    const topAmounts = <?= json_encode($topAmounts) ?>;
    const worstProducts = <?= json_encode($worstProducts) ?>;
    const worstAmounts = <?= json_encode($worstAmounts) ?>;

    // สร้างสีสุ่มแต่ละแท่ง
    const topColors = generateColors(topProducts.length, 'top');
    const worstColors = generateColors(worstProducts.length, 'worst');

    const barOptions = {
        responsive: true,
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
    };

    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: topProducts,
            datasets: [{
                label: 'ยอดขายรวม',
                data: topAmounts,
                backgroundColor: topColors,
                borderColor: topColors,
                borderWidth: 1
            }]
        },
        options: barOptions
    });

    new Chart(document.getElementById('worstProductsChart'), {
        type: 'bar',
        data: {
            labels: worstProducts,
            datasets: [{
                label: 'ยอดขายรวม',
                data: worstAmounts,
                backgroundColor: worstColors,
                borderColor: worstColors,
                borderWidth: 1
            }]
        },
        options: barOptions
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

</body>
</html>

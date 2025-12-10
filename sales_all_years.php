<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// รับ user_id ที่ต้องการดูจากพารามิเตอร์
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id <= 0) {
    die('ไม่พบข้อมูลผู้ใช้งานที่ต้องการดูรายงาน');
}

// ดึงชื่อพนักงาน
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($employee_name);
$stmt->fetch();
$stmt->close();

// ชื่อเดือนภาษาไทย
$monthNames = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม'
];

// ดึงยอดขายรายเดือนของทุกปีของพนักงานคนนี้
$sql = "
    SELECT year, month, SUM(amount) AS total_amount
    FROM sales
    WHERE user_id = ?
    GROUP BY year, month
    ORDER BY year ASC, month ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// เตรียมข้อมูลเป็นโครงสร้าง: [ปี][เดือน] = ยอดขาย
$yearsData = [];      // [year => [1..12]]
$yearTotals = [];     // [year => total_all_months]

while ($row = $result->fetch_assoc()) {
    $year  = (int)$row['year'];
    $month = (int)$row['month'];
    $amount = (float)$row['total_amount'];

    if (!isset($yearsData[$year])) {
        // เตรียม array 12 เดือน ค่าเริ่มต้น 0
        $yearsData[$year] = array_fill(1, 12, 0.0);
        $yearTotals[$year] = 0.0;
    }
    if ($month >= 1 && $month <= 12) {
        $yearsData[$year][$month] += $amount;
        $yearTotals[$year] += $amount;
    }
}

$years = array_keys($yearsData);
sort($years);

// สร้างข้อมูลสำหรับส่งไป Chart.js
$chartData = [];
$all_time_total = 0;
$best_year = null;
$best_year_amount = 0;

foreach ($years as $year) {
    $monthsArray = $yearsData[$year];

    $total = $yearTotals[$year];
    $all_time_total += $total;

    if ($total > $best_year_amount) {
        $best_year_amount = $total;
        $best_year = $year;
    }

    $chartData[] = [
        'year'  => $year,
        'label' => "ยอดขายปี " . $year,
        'total' => $total,
        'data'  => array_values($monthsArray)
    ];
}

$year_count    = count($years);
$avg_per_year  = $year_count > 0 ? $all_time_total / $year_count : 0;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>ยอดขายรวมทุกช่วงเวลา (เทียบทุกปี)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Font Awesome  -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            font-family: "Open Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f5f7fb;
        }

        .page-header {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            border-radius: 1rem;
            border: none;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .badge-employee {
            font-size: 0.85rem;
            border-radius: 999px;
        }

        .btn-back {
            border-radius: 999px;
        }

        .card-chart {
            border-radius: 1rem;
            border: none;
        }

        .year-filter-pill {
            border-radius: 999px;
        }
    </style>
</head>

<body>
<?php include 'topnavbar.php'; ?>

<div class="container my-4">

    <!-- หัวหน้าเพจ -->
    <div class="d-flex justify-content-between align-items-center page-header flex-wrap gap-2">
        <div>
            <h3 class="mb-1">ยอดขายรวมทุกช่วงเวลา (เทียบทุกปี)</h3>
            <div class="text-muted">
                พนักงาน:
                <span class="fw-semibold">
                    <?= htmlspecialchars($employee_name ?: 'ไม่พบชื่อพนักงาน') ?>
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="javascript:history.back();" class="btn btn-outline-secondary btn-sm btn-back">
                <i class="fa-solid fa-arrow-left-long me-1"></i> ย้อนกลับ
            </a>
        </div>
    </div>

    <?php if ($year_count === 0): ?>
        <div class="alert alert-warning mt-3">
            <i class="fa-solid fa-circle-info me-1"></i>
            ยังไม่มียอดขายสำหรับพนักงานคนนี้
        </div>
    <?php else: ?>

        <!-- การ์ดสรุปด้านบน -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-label mb-1">ยอดขายรวมทุกปี</div>
                        <div class="stat-value text-primary">
                            <?= number_format($all_time_total, 2) ?> <span class="fs-6">บาท</span>
                        </div>
                        <div class="text-muted small mt-1">
                            รวมจาก <?= $year_count ?> ปี
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-label mb-1">ปีที่มียอดขายสูงสุด</div>
                        <div class="stat-value text-success">
                            <?= $best_year ?: '-' ?>
                        </div>
                        <div class="text-muted small mt-1">
                            <?= $best_year ? number_format($best_year_amount, 2) . ' บาท' : 'ยังไม่มียอดขาย' ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-label mb-1">ยอดขายเฉลี่ยต่อปี</div>
                        <div class="stat-value text-info">
                            <?= number_format($avg_per_year, 2) ?> <span class="fs-6">บาท</span>
                        </div>
                        <div class="text-muted small mt-1">
                            คำนวณจาก <?= $year_count ?> ปี
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- การ์ดกราฟหลัก -->
        <div class="card card-chart shadow-sm mb-4">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">ยอดขายรายเดือน เปรียบเทียบทุกปี</h5>
                        <div class="text-muted small">
                            แกน X = เดือน, แกน Y = ยอดขาย (บาท)
                        </div>
                    </div>
                    <!-- ปุ่มขยาย (ถ้าอยากผูก modal ทีหลังได้) -->
                    <!--
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-up-right-and-down-left-from-center me-1"></i> ขยาย
                    </button>
                    -->
                </div>
            </div>

            <!-- แถบเลือกปี -->
            <div class="px-3 pb-0">
                <div class="mb-2 fw-semibold">เลือกปีที่ต้องการแสดง:</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($chartData as $item): ?>
                        <div class="form-check form-check-inline year-filter-pill border px-3 py-1 bg-light">
                            <input
                                class="form-check-input year-toggle"
                                type="checkbox"
                                id="yearCheck<?= $item['year'] ?>"
                                data-year="<?= $item['year'] ?>"
                                checked>
                            <label class="form-check-label small" for="yearCheck<?= $item['year'] ?>">
                                <?= $item['year'] ?>
                                (<?= number_format($item['total'], 2) ?> บาท)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card-body">
                <div style="height: 420px;">
                    <canvas id="multiYearMonthlyChart"></canvas>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($year_count > 0): ?>
<script>
    // ข้อมูลจาก PHP
    const chartData = <?= json_encode($chartData, JSON_NUMERIC_CHECK) ?>;
    const monthLabels = <?= json_encode(array_values($monthNames), JSON_UNESCAPED_UNICODE) ?>;

    // สีสำหรับแต่ละปี (วนใช้ซ้ำถ้าปีเยอะ)
    const lineColors = [
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 99, 132, 1)',
        'rgba(201, 203, 207, 1)'
    ];
    const lineBgColors = [
        'rgba(75, 192, 192, 0.15)',
        'rgba(153, 102, 255, 0.15)',
        'rgba(255, 159, 64, 0.15)',
        'rgba(54, 162, 235, 0.15)',
        'rgba(255, 99, 132, 0.15)',
        'rgba(201, 203, 207, 0.15)'
    ];

    const ctx = document.getElementById('multiYearMonthlyChart').getContext('2d');

    // สร้าง datasets จากข้อมูลทุกปี
    const datasets = chartData.map((item, index) => ({
        label: item.label,
        year: item.year, // เก็บไว้ใช้หา dataset ตอนเช็ค/ยกเลิก
        data: item.data,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 5,
        borderColor: lineColors[index % lineColors.length],
        backgroundColor: lineBgColors[index % lineBgColors.length],
        fill: false
    }));

    const multiYearChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const v = ctx.raw || 0;
                            return ` ${ctx.dataset.label}: ${v.toLocaleString()} บาท`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'เดือน'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'ยอดขาย (บาท)'
                    },
                    ticks: {
                        callback: (value) => value.toLocaleString() + ' บาท'
                    }
                }
            },
            // คลิกที่จุด/เส้นของเดือน → ถ้าต้องการจะลิงก์ไปหน้า sales_by_month ก็ทำตรงนี้
           /* onClick: (event, elements) => {
                if (elements.length > 0) {
                    const element = elements[0];
                    const year   = multiYearChart.data.datasets[element.datasetIndex].year;
                    const monthIndex = element.index; // 0–11
                    const month = monthIndex + 1;

                    // ตัวอย่าง: กระโดดไปหน้าแสดงรายละเอียดยอดขายเดือนนั้นๆ ของปีนั้น
                    // (ถ้าไม่ต้องการ ให้ลบส่วนนี้ออกได้)
                    window.location.href =
                        "sales_by_month_ss.php?user_id=<?= $user_id ?>&year=" + year + "&month=" + month;
                }
            }*/
        }
    });

    // จัดการ checkbox เปิด/ปิดปี
    document.querySelectorAll('.year-toggle').forEach(chk => {
        chk.addEventListener('change', function () {
            const year = parseInt(this.dataset.year);
            multiYearChart.data.datasets.forEach(ds => {
                if (ds.year === year) {
                    ds.hidden = !this.checked;
                }
            });
            multiYearChart.update();
        });
    });
</script>
<?php endif; ?>

</body>
</html>

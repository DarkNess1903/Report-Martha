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
$sql = "SELECT year, month, quarter, product, SUM(amount) AS total_sales
        FROM sales 
        WHERE user_id = ? 
        GROUP BY year, month, quarter, product
        ORDER BY year DESC, quarter DESC, month DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// สร้าง array สำหรับข้อมูลยอดขาย
$sales_data = [];
$months = [];
$products = [];
$sales = [];

// แปลงไตรมาสเป็นเดือน
$quarter_to_month = [
    '1' => 'ไตรมาส 1',
    '2' => 'ไตรมาส 2',
    '3' => 'ไตรมาส 3',
    '4' => 'ไตรมาส 4'
];

while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
    $months[] = $quarter_to_month[$row['quarter']] . " " . $row['year'];
    $products[$row['product']][] = $row['total_sales'];
    $sales[] = $row['total_sales'];
}

// เตรียม labels สำหรับแต่ละช่วงเวลา
$labels_monthly = [];
$labels_quarterly = [];
$labels_yearly = [];

foreach ($sales_data as $row) {
    $labels_monthly[] = $row['month'] . "/" . $row['year'];
    $labels_quarterly[] = $quarter_to_month[$row['quarter']] . " " . $row['year'];
    $labels_yearly[] = $row['year'];
}

// กำจัดค่าที่ซ้ำ
$labels_monthly = array_values(array_unique($labels_monthly));
$labels_quarterly = array_values(array_unique($labels_quarterly));
$labels_yearly = array_values(array_unique($labels_yearly));

// สรุปยอดขายรวมตามสินค้า
$total_sales_per_product = [];
foreach ($sales_data as $row) {
    $product = $row['product'];
    if (!isset($total_sales_per_product[$product])) {
        $total_sales_per_product[$product] = 0;
    }
    $total_sales_per_product[$product] += $row['total_sales'];
}

// เตรียมข้อมูลยอดขายสินค้าแยกตามปี
$product_sales_by_year = [];
foreach ($sales_data as $row) {
    $year = $row['year'];
    $product = $row['product'];
    $amount = floatval($row['total_sales']);

    if (!isset($product_sales_by_year[$year])) {
        $product_sales_by_year[$year] = [];
    }

    if (!isset($product_sales_by_year[$year][$product])) {
        $product_sales_by_year[$year][$product] = 0;
    }

    $product_sales_by_year[$year][$product] += $amount;
}

// สำหรับกราฟแท่งสินค้าเริ่มต้น (รวมทั้งหมด)
$product_labels = array_keys($total_sales_per_product);
$product_sales = array_values($total_sales_per_product);

$stmt->close();
?>

<!-- ส่งข้อมูลไป JavaScript -->
<script>
    const quarterToMonth = <?= json_encode($quarter_to_month) ?>;
    const labelsMonthly = <?= json_encode($labels_monthly) ?>;
    const labelsQuarterly = <?= json_encode($labels_quarterly) ?>;
    const labelsYearly = <?= json_encode($labels_yearly) ?>;
    const salesDataFromPHP = <?= json_encode($sales_data) ?>;
    const productSalesByYear = <?= json_encode($product_sales_by_year) ?>;
    const productLabels = <?= json_encode($product_labels) ?>;
    const productSales = <?= json_encode($product_sales) ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Dashboard - ยอดขายของคุณ</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Favicons -->
  <link href="assets/img/ma2.png" rel="icon">
  <link href="assets/img/ma2.png" rel="apple-touch-icon">

  <!-- ลิงค์ของตาราง -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


  <!-- ไลบารี่ไอคอน -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">


  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
 <!-- จบลิงค์ของตาราง -->
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

            <!-- ส่วนแสดงกราฟและปุ่มขยาย -->
            <div class="row">
                <div class="col-md-6 mb-4 chart-container">
                    <!-- แถวเลือกช่วงเวลา + ปุ่มขยาย -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <label for="timePeriodSelect" class="me-2 mb-0">เลือกช่วงเวลา:</label>
                            <select id="timePeriodSelect" class="form-select" style="width:auto;">
                                <option value="monthly">รายเดือน</option>
                                <option value="quarterly">รายไตรมาส</option>
                                <option value="yearly">รายปี</option>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('salesChart')">
                            <i class="fas fa-expand"></i> ขยาย
                        </button>
                    </div>

                    <!-- กราฟยอดขาย -->
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>

                <div class="col-md-6 mb-4 chart-container">
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('productSalesChart')">
                            <i class="fas fa-expand"></i> ขยาย
                        </button>
                    </div>
                    <!-- กราฟยอดขายสินค้า -->
                    <div style="width: 600px; max-width: 100%; margin: auto;">
                        <canvas id="productSalesChart"></canvas>
                    </div>
                </div>
            </div>
                                
            <!-- ขยายเต็มจอ -->
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

            <?php
            // สร้างอาร์เรย์ชื่อเดือนภาษาไทย
            $thai_months = [
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
            ?>

            <!-- ตารางยอดขาย --> 
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tabledata" class="table table-striped table-bordered">
                            <thead style="font-size: small;">
                                <tr>
                                    <th>ปี</th>
                                    <th>เดือน</th>
                                    <th>ยอดขายรวม (บาท)</th>
                                    <th>สินค้า</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_data as $data): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($data['year']) ?></td>
                                        <td>
                                            <?php
                                            // แปลงเลขเดือนเป็นชื่อเดือนภาษาไทย
                                            if (!empty($data['month']) && $data['month'] != 0) {
                                                echo htmlspecialchars($thai_months[(int)$data['month']]);
                                            } else {
                                                echo "-";  // กรณีไม่มีเดือน
                                            }
                                            ?>
                                        </td>
                                        <td><?= number_format($data['total_sales'], 2) ?> บาท</td>
                                        <td><?= htmlspecialchars($data['product']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p>ยังไม่มีข้อมูลยอดขายของคุณในปีและไตรมาสนี้</p>
        <?php endif; ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctxLine = document.getElementById('salesChart').getContext('2d');
        const ctxBar = document.getElementById('productSalesChart').getContext('2d');

        const quarterToMonth = <?= json_encode($quarter_to_month) ?>;
        const labelsMonthly = <?= json_encode($labels_monthly) ?>;
        const labelsQuarterly = <?= json_encode($labels_quarterly) ?>;
        const labelsYearly = <?= json_encode($labels_yearly) ?>;
        const salesDataFromPHP = <?= json_encode($sales_data) ?>;
        const productSalesByYear = <?= json_encode($product_sales_by_year) ?>;

        // ----------------- กราฟเส้นยอดขาย -----------------
        const salesChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const val = context.raw;
                                return val ? `ยอดขาย: ${val.toLocaleString()} บาท` : 'ไม่มีข้อมูล';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return value === null ? 'ไม่มีข้อมูล' : value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // ----------------- กราฟแท่งยอดขายสินค้า -----------------
        const productSalesChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'ยอดขายสินค้า (รวม)',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'ยอดขาย (จำนวนเงิน)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'สินค้า'
                        }
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: { enabled: true }
                }
            }
        });

        // ----------------- ฟังก์ชันอัปเดตกกราฟเส้น -----------------
        function updateLineChart() {
            const selectedYears = Array.from(document.querySelectorAll('input[name="years[]"]:checked')).map(el => el.value);
            const timePeriod = document.getElementById('timePeriodSelect').value;

            let labels = [];
            if (timePeriod === 'monthly') {
                labels = labelsMonthly;
            } else if (timePeriod === 'quarterly') {
                labels = labelsQuarterly;
            } else {
                labels = labelsYearly;
            }

            const datasets = selectedYears.map(year => {
                const data = Array(labels.length).fill(null);

                salesDataFromPHP.forEach(item => {
                    if (item.year == year) {
                        let labelKey = '';
                        if (timePeriod === 'monthly') {
                            labelKey = item.month + "/" + item.year;
                        } else if (timePeriod === 'quarterly') {
                            labelKey = quarterToMonth[item.quarter] + " " + item.year;
                        } else {
                            labelKey = item.year;
                        }

                        const index = labels.indexOf(labelKey);
                        if (index !== -1) {
                            if (!data[index]) data[index] = 0;
                            data[index] += parseFloat(item.total_sales);
                        }
                    }
                });

                return {
                    label: `ยอดขายปี ${year}`,
                    data: data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false
                };
            });

            salesChart.data.labels = labels;
            salesChart.data.datasets = datasets;
            salesChart.update();
        }

        // ----------------- ฟังก์ชันอัปเดตกกราฟสินค้า -----------------
        function updateProductChart() {
            const selectedYears = Array.from(document.querySelectorAll('input[name="years[]"]:checked')).map(el => el.value);
            const aggregatedSales = {};

            selectedYears.forEach(year => {
                if (productSalesByYear[year]) {
                    Object.entries(productSalesByYear[year]).forEach(([product, value]) => {
                        if (!aggregatedSales[product]) aggregatedSales[product] = 0;
                        aggregatedSales[product] += parseFloat(value);
                    });
                }
            });

            productSalesChart.data.labels = Object.keys(aggregatedSales);
            productSalesChart.data.datasets[0].data = Object.values(aggregatedSales);
            productSalesChart.update();
        }

        // ----------------- ผูก event -----------------
        document.querySelectorAll('input[name="years[]"]').forEach(el => {
            el.addEventListener('change', () => {
                updateLineChart();
                updateProductChart();
            });
        });

        document.getElementById('timePeriodSelect').addEventListener('change', updateLineChart);

        // เรียกใช้งานครั้งแรก
        updateLineChart();
        updateProductChart();
    });
</script>

<script>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
        $('#tabledata').dataTable( {
        "oLanguage": {
        "sLengthMenu": "แสดง MENU ข้อมูล",
        "sZeroRecords": "ไม่พบข้อมูล",
        "sInfo": "แสดง START ถึง END ของ TOTAL ข้อมูล",
        "sInfoEmpty": "แสดง 0 ถึง 0 ของ 0 ข้อมูล",
        "sInfoFiltered": "(จากข้อมูลทั้งหมด MAX ข้อมูล)",
        "sSearch": "ค้นหา :",
        "aaSorting" :[[0,'desc']],
        "oPaginate": {
        "sFirst":    "หน้าแรก",
        "sPrevious": "ก่อนหน้า",
        "sNext":     "ถัดไป",
        "sLast":     "หน้าสุดท้าย"
        },
        }
        } );
        } );
    </script>
</body>
</html>

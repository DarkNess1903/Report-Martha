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
$products = [];
$months = [];
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

$labels_monthly = [];
$labels_quarterly = [];
$labels_yearly = [];

foreach ($sales_data as $row) {
    // รายเดือน
    $labels_monthly[] = $row['month'] . "/" . $row['year'];
    // รายไตรมาส
    $labels_quarterly[] = $quarter_to_month[$row['quarter']] . " " . $row['year'];
    // รายปี
    $labels_yearly[] = $row['year'];
}

$labels_monthly = array_values(array_unique($labels_monthly));
$labels_quarterly = array_values(array_unique($labels_quarterly));
$labels_yearly = array_values(array_unique($labels_yearly));

$stmt->close();
?>

<script>
    const labelsMonthly = <?= json_encode($labels_monthly) ?>;
    const labelsQuarterly = <?= json_encode($labels_quarterly) ?>;
    const labelsYearly = <?= json_encode($labels_yearly) ?>;
    const salesDataFromPHP = <?= json_encode($sales_data) ?>;
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
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('salesChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>
            
            <!-- ขยายเต็มจอ -->
            <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content bg-white">
                        <div class="modal-header">
                            <h5 class="modal-title" id="chartModalLabel">กราฟแบบเต็มหน้าจอ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="w-100 h-100">
                                <canvas id="fullScreenChart" style="width:100% !important; height:100% !important;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <!-- ตารางยอดขาย -->
                <div class="col-md-6">
                <div class="card shadow-sm">
                <div class="card-body">
                <div class="table-responsive">
                <table id= "tabledata" class="table table-striped table-boredered">
                        <thead style="font-size: small;">
                            <tr>
                                <th>เดือน/ไตรมาส</th>
                                <th>ยอดขายรวม (บาท)</th>
                                <th>สินค้า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_data as $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($quarter_to_month[$data['quarter']]) ?> <?= $data['year'] ?></td>
                                    <td><?= number_format($data['total_sales'], 2) ?> บาท</td>
                                    <td><?= htmlspecialchars($data['product']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
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
                const selectedYears = Array.from(document.querySelectorAll('input[name="years[]"]:checked')).map(el => el.value);
                const timePeriod = document.getElementById('timePeriodSelect').value;

                let labels = [];
                if (timePeriod === 'monthly') {
                    labels = labelsMonthly;
                } else if (timePeriod === 'quarterly') {
                    labels = labelsQuarterly;
                } else if (timePeriod === 'yearly') {
                    labels = labelsYearly;
                }

                // สร้าง datasets
                let datasets = [];

                selectedYears.forEach(year => {
                    let data = Array(labels.length).fill(null);

                    salesDataFromPHP.forEach(item => {
                        if (item.year == year) {
                            let labelKey = '';
                            if (timePeriod === 'monthly') {
                                labelKey = item.month + "/" + item.year;
                            } else if (timePeriod === 'quarterly') {
                                labelKey = quarterToMonth[item.quarter] + " " + item.year;
                            } else if (timePeriod === 'yearly') {
                                labelKey = item.year;
                            }

                            let index = labels.indexOf(labelKey);
                            if (index !== -1) {
                                if (!data[index]) data[index] = 0;
                                data[index] += parseFloat(item.total_sales);
                            }
                        }
                    });

                    datasets.push({
                        label: "ยอดขายปี " + year,
                        data: data,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false
                    });
                });

                salesChart.data.labels = labels;
                salesChart.data.datasets = datasets;
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
    
    <script>
        let fullScreenChartInstance;

        function showFullScreenChart(originalChartId) {
            const originalChart = Chart.getChart(originalChartId);
            if (!originalChart) {
                console.error("ไม่พบกราฟ:", originalChartId);
                return;
            }

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
                        legend: { display: true },
                        title: {
                            display: true,
                            text: originalChart.options.plugins?.title?.text || 'กราฟ'
                        }
                    },
                    scales: originalChart.options.scales
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

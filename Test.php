<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_year = date("Y");

// ดึงข้อมูลยอดขายรวมรายเดือน, รายไตรมาส และสินค้า
$sql = "SELECT month, quarter, product, SUM(amount) AS total_amount
        FROM sales
        WHERE year = $current_year
        GROUP BY month, quarter, product
        ORDER BY month";
$result = $conn->query($sql);

$monthly_data = [];
$quarterly_data = [];
$product_data = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $month = $row['month'];
        $quarter = $row['quarter'];
        $product = $row['product'];
        $amount = $row['total_amount'];

        $monthly_data[$month] = $amount;
        $quarterly_data[$quarter][] = $amount;
        $product_data[$product][$month] = $amount;
    }
}

// ดึงข้อมูลยอดขายของพนักงานรายเดือนและรายไตรมาส
$sql = "SELECT u.username, s.product, s.month, s.quarter, SUM(s.amount) AS total_amount
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.year = $current_year
        GROUP BY u.username, s.product, s.month, s.quarter
        ORDER BY u.username, s.product, s.month";
$result = $conn->query($sql);

$employee_product_sales = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $username = $row['username'];
        $product = $row['product'];
        $month = $row['month'];
        $quarter = $row['quarter'];
        $amount = $row['total_amount'];

        $employee_product_sales[$username][$product]['monthly'][$month] = $amount;

        $employee_product_sales[$username][$product]['quarterly'][$quarter] = 
            isset($employee_product_sales[$username][$product]['quarterly'][$quarter])
                ? $employee_product_sales[$username][$product]['quarterly'][$quarter] + $amount
                : $amount;
    }
}

// ดึงข้อมูลสินค้าขายดี 5 อันดับ
$sql_best_selling = "SELECT product, SUM(amount) AS total_sales 
                     FROM sales 
                     WHERE year = $current_year
                     GROUP BY product 
                     ORDER BY total_sales DESC 
                     LIMIT 5";
$result_best = $conn->query($sql_best_selling);

$best_selling_products = [];
while ($row = $result_best->fetch_assoc()) {
    $best_selling_products[] = $row;
}

// ดึงข้อมูลสินค้าขายไม่ดี 5 อันดับ
$sql_worst_selling = "SELECT product, SUM(amount) AS total_sales 
                      FROM sales 
                      WHERE year = $current_year
                      GROUP BY product 
                      ORDER BY total_sales ASC 
                      LIMIT 5";
$result_worst = $conn->query($sql_worst_selling);

$worst_selling_products = [];
while ($row = $result_worst->fetch_assoc()) {
    $worst_selling_products[] = $row;
}
$best_selling_json = json_encode($best_selling_products);
$worst_selling_json = json_encode($worst_selling_products);

$conn->close();

// ส่งข้อมูลเป็น JSON ไปยัง JavaScript
?>
<script>
    var monthlyData = <?php echo json_encode($monthly_data); ?>;
    var quarterlyData = <?php echo json_encode($quarterly_data); ?>;
    var productData = <?php echo json_encode($product_data); ?>;
    var employeeProductSales = <?php echo json_encode($employee_product_sales); ?>;
    var bestSelling = <?php echo json_encode($best_selling_products); ?>;
    var worstSelling = <?php echo json_encode($worst_selling_products); ?>;
</script>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- เชื่อมต่อ Bootstrap 5 และ Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Include Top Navbar -->
    <?php include 'topnavbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12 text-center">
            <h2>กราฟข้อมูลทางการเงิน</h2>
            <p>เลือกช่วงเวลาที่ต้องการแสดงข้อมูลยอดขาย</p>
        </div>
    </div>
    <div class="row mb-4">
    <!-- กราฟยอดขาย -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- ตัวเลือกในการเลือกช่วงเวลาที่ต้องการแสดง -->
                <label for="timePeriodSelect">เลือกช่วงเวลายอดขาย:</label>
                <select id="timePeriodSelect" class="form-select mb-2">
                    <option value="monthly">รายเดือน</option>
                    <option value="quarterly">รายไตรมาส</option>
                </select>     

                <!-- ปุ่มขยายกราฟ -->
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('timePeriodChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>

                <!-- แสดงกราฟยอดขาย -->
                <canvas id="timePeriodChart"></canvas>
            </div>
        </div>
    </div>

    <!-- กราฟสินค้า -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- ตัวเลือกช่วงเวลาในการแสดงกราฟสินค้า -->
                <label for="productTimeSelect">เลือกช่วงเวลาสำหรับกราฟสินค้า:</label>
                <select id="productTimeSelect" class="form-select mb-2">
                    <option value="monthly">รายเดือน</option>
                    <option value="quarterly">รายไตรมาส</option>
                </select>

                <!-- ปุ่มขยายกราฟ -->
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="showFullScreenChart('productChart')">
                        <i class="fas fa-expand"></i> ขยาย
                    </button>
                </div>

                <canvas id="productChart"></canvas> <!-- กราฟสินค้า -->
            </div>
        </div>
    </div>
</div>

<!-- Modal แบบเต็มหน้าจอ (ใส่นอก .row) -->
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

    <!-- กราฟสินค้าขายดีและขายไม่ดี -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">สินค้าขายดี 5 อันดับ</h5>
                    <canvas id="bestSellingChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">สินค้าขายไม่ดี 5 อันดับ</h5>
                    <canvas id="worstSellingChart"></canvas>
                </div>
            </div>
        </div>
    </div>

<script>
    var ctx1 = document.getElementById('timePeriodChart').getContext('2d');
    var ctx2 = document.getElementById('productChart').getContext('2d');

    // กราฟยอดขายในรูปแบบเริ่มต้น (รายเดือน)
    var timePeriodChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'],
            datasets: [{
                label: 'ข้อมูลรายเดือน',
                data: [1,2,3,4,5,6,7,8,9,10,11,12].map(function(m) {
                    return monthlyData[m] || 0;
                }),
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false,
                tension: 0.3
            }]
        }
    });
    // ฟังก์ชันเพื่อสร้างสีจาก HSL
    function generateHSLColor(index) {
        var hue = (index * 360 / Object.keys(productData).length) % 360;  // คำนวณค่า Hue
        return 'hsl(' + hue + ', 70%, 50%)';  // Saturation 70%, Lightness 50%
    }

    // กราฟสินค้าในรูปแบบเริ่มต้น (รายเดือน)
    var productChart = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'],
            datasets: Object.keys(productData).map(function(product, index) {
                return {
                    label: product,
                    data: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map(function(m) {
                        return productData[product][m] || 0;
                    }),
                    borderColor: generateHSLColor(index), // ใช้สีจากฟังก์ชัน HSL
                    fill: false,
                    tension: 0.3
                };
            })
        }
    });

    // อัปเดตกราฟยอดขายตามช่วงเวลา
    document.getElementById('timePeriodSelect').addEventListener('change', function() {
        var selectedPeriod = this.value;
        var newLabels = [];
        var newData = [];
        var labelPrefix = "";

        if (selectedPeriod == "monthly") {
            newLabels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            newData = [1,2,3,4,5,6,7,8,9,10,11,12].map(function(m) {
                return monthlyData[m] || 0;
            });
            labelPrefix = "ข้อมูลรายเดือน";
        } else if (selectedPeriod == "quarterly") {
            newLabels = ['ไตรมาส 1', 'ไตรมาส 2', 'ไตรมาส 3', 'ไตรมาส 4'];
            newData = [1, 2, 3, 4].map(function(q) {
                let sum = quarterlyData[q];
                return Array.isArray(sum) ? sum.reduce((a, b) => a + b, 0) : 0;
            });
            labelPrefix = "ข้อมูลรายไตรมาส";
        }

        timePeriodChart.data.labels = newLabels;
        timePeriodChart.data.datasets[0].label = labelPrefix;
        timePeriodChart.data.datasets[0].data = newData;
        timePeriodChart.update();
    });

    // อัปเดตกราฟสินค้า
    document.getElementById('productTimeSelect').addEventListener('change', function() {
        var selectedPeriod = this.value;
        var newLabels = [];
        var newDatasets = [];

        // กำหนด label
        if (selectedPeriod === "monthly") {
            newLabels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        } else if (selectedPeriod === "quarterly") {
            newLabels = ['ไตรมาส 1', 'ไตรมาส 2', 'ไตรมาส 3', 'ไตรมาส 4'];
        }

        // สร้าง datasets
        var products = Object.keys(productData);
        newDatasets = products.map(function(product, index) {
            var hue = (index * 360 / products.length); // สร้างสีไม่ซ้ำ
            var dataPoints = (selectedPeriod === 'monthly'
                ? [1,2,3,4,5,6,7,8,9,10,11,12]
                : [1,2,3,4]
            ).map(function(unit) {
                return productData[product][unit] || 0;
            });

            return {
                label: product,
                data: dataPoints,
                borderColor: `hsl(${hue}, 70%, 50%)`,
                fill: false,
                tension: 0.4 // ความโค้งของเส้น
            };
        });

        // อัปเดตกราฟ
        productChart.data.labels = newLabels;
        productChart.data.datasets = newDatasets;
        productChart.update();
    });

    //เต็มจอ
    let fullScreenChartInstance;

    function showFullScreenChart(originalChartId) {
        const originalChart = Chart.getChart(originalChartId);

        if (!originalChart) {
            console.error("ไม่พบกราฟที่ระบุ:", originalChartId);
            return;
        }

        // ล้างกราฟก่อน
        if (fullScreenChartInstance) {
            fullScreenChartInstance.destroy();
        }

        const ctx = document.getElementById('fullScreenChart').getContext('2d');

        // คัดลอกข้อมูลจากกราฟต้นฉบับ
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

    // รีเฟรชขนาดเมื่อแสดง modal
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


<script>
    var bestSellingProducts = <?php echo $best_selling_json; ?>;
    var worstSellingProducts = <?php echo $worst_selling_json; ?>;
</script>

<script>
// ฟังก์ชันสุ่มสี
function generateRandomColor() {
    var r = Math.floor(Math.random() * 256);
    var g = Math.floor(Math.random() * 256);
    var b = Math.floor(Math.random() * 256);
    return 'rgba(' + r + ',' + g + ',' + b + ', 0.6)'; // สร้างสีแบบโปร่งใส
}

// สร้างสีให้กับสินค้าแต่ละชนิด (ใช้ชื่อสินค้าหรือรหัสสินค้าในการระบุ)
function getColorForProduct(product) {
    if (!getColorForProduct.colorMap) {
        getColorForProduct.colorMap = {}; // เก็บสีของสินค้าแต่ละตัว
    }

    if (!getColorForProduct.colorMap[product]) {
        getColorForProduct.colorMap[product] = generateRandomColor(); // สร้างสีใหม่ให้สินค้า
    }

    return getColorForProduct.colorMap[product]; // ส่งคืนสีที่ถูกเก็บไว้
}

// ฟังก์ชันสร้างกราฟ
function createChart(chartId, data, label) {
    var ctx = document.getElementById(chartId).getContext('2d');
    var labels = data.map(item => item.product);
    var salesData = data.map(item => item.total_sales);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: salesData,
                backgroundColor: data.map(item => getColorForProduct(item.product)), // ใช้สีที่เหมือนกันตามชื่อสินค้า
                borderColor: data.map(item => getColorForProduct(item.product)), // สีกรอบ
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// สร้างกราฟยอดขายสูงสุด
createChart("bestSellingChart", bestSellingProducts, "ยอดขายสูงสุด");
// สร้างกราฟยอดขายต่ำสุด
createChart("worstSellingChart", worstSellingProducts, "ยอดขายต่ำสุด");

</script>

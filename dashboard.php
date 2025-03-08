<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลจากฐานข้อมูล
$sql = "SELECT year, month, quarter, product, SUM(amount) AS total_amount
        FROM sales
        GROUP BY year, month, quarter, product
        ORDER BY year, month";
$result = $conn->query($sql);

// สร้าง array สำหรับจัดกลุ่มข้อมูล
$monthly_data = [];
$quarterly_data = [];   
$yearly_data = [];
$product_data = []; // เก็บข้อมูลสินค้า

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $monthly_data[$row['year']][$row['month']] = $row['total_amount'];
        $quarterly_data[$row['year']][$row['quarter']][] = $row['total_amount'];
        $yearly_data[$row['year']][] = $row['total_amount'];

        // เก็บข้อมูลสินค้า
        $product_data[$row['product']][$row['year']][$row['month']] = $row['total_amount'];
    }
} else {
    echo "0 results";
}

// ดึงข้อมูลยอดขายของพนักงานตามสินค้าและจัดกลุ่มตามช่วงเวลา
$sql = "SELECT u.username, s.product, s.year, s.month, s.quarter, SUM(s.amount) AS total_amount
        FROM sales s
        JOIN users u ON s.user_id = u.id
        GROUP BY u.username, s.product, s.year, s.month, s.quarter
        ORDER BY u.username, s.product, s.year, s.month";
$result = $conn->query($sql);

// สร้าง array สำหรับเก็บข้อมูลยอดขายของพนักงานตามสินค้า
$employee_product_sales = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $username = $row['username'];
        $product = $row['product'];
        $year = $row['year'];
        $month = $row['month'];
        $quarter = $row['quarter'];

        // จัดเก็บข้อมูลตามโครงสร้าง
        $employee_product_sales[$username][$product]['monthly'][$year][$month] = $row['total_amount'];
        $employee_product_sales[$username][$product]['quarterly'][$year][$quarter] = 
            isset($employee_product_sales[$username][$product]['quarterly'][$year][$quarter])
                ? $employee_product_sales[$username][$product]['quarterly'][$year][$quarter] + $row['total_amount']
                : $row['total_amount'];
        $employee_product_sales[$username][$product]['yearly'][$year] = 
            isset($employee_product_sales[$username][$product]['yearly'][$year])
                ? $employee_product_sales[$username][$product]['yearly'][$year] + $row['total_amount']
                : $row['total_amount'];
    }
} else {
    echo "0 results";
}

// ดึงข้อมูลสินค้าขายดี 5 อันดับ
$sql_best_selling = "SELECT product, SUM(amount) AS total_sales 
                     FROM sales 
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
                      GROUP BY product 
                      ORDER BY total_sales ASC 
                      LIMIT 5";
$result_worst = $conn->query($sql_worst_selling);

$worst_selling_products = [];
while ($row = $result_worst->fetch_assoc()) {
    $worst_selling_products[] = $row;
}

// แปลงข้อมูลเป็น JSON
$best_selling_json = json_encode($best_selling_products);
$worst_selling_json = json_encode($worst_selling_products);

$conn->close();

// แปลงข้อมูลให้เป็น JSON เพื่อใช้งานใน JavaScript
echo "<script>";
echo "var monthlyData = " . json_encode($monthly_data) . ";";
echo "var quarterlyData = " . json_encode($quarterly_data) . ";";
echo "var yearlyData = " . json_encode($yearly_data) . ";";
echo "var productData = " . json_encode($product_data) . ";"; // ส่งข้อมูลสินค้า
echo "</script>";

echo "<script>";
echo "var employeeProductSales = " . json_encode($employee_product_sales) . ";";
echo "</script>";
?>

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
                    <label for="timePeriodSelect">เลือกช่วงเวลา:</label>
                    <select id="timePeriodSelect" class="form-select">
                        <option value="monthly">รายเดือน</option>
                        <option value="quarterly">รายไตรมาส</option>
                        <option value="yearly">รายปี</option>
                    </select>
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
                <select id="productTimeSelect" class="form-select">
                    <option value="monthly">รายเดือน</option>
                    <option value="quarterly">รายไตรมาส</option>
                    <option value="yearly">รายปี</option>
                </select>
                <canvas id="productChart"></canvas> <!-- กราฟสินค้า -->
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

    // กราฟยอดขายในรูปแบบเดิม
    var timePeriodChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'],
            datasets: [{
                label: 'ข้อมูลรายเดือน',
                data: Object.keys(monthlyData).map(function(year) {
                    return Object.keys(monthlyData[year]).map(function(month) {
                        return monthlyData[year][month] || 0;
                    });
                }).flat(),
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false
            }]
        }
    });

    // กราฟสินค้า
    var productChart = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'],
            datasets: Object.keys(productData).map(function(product) {
                return {
                    label: product,
                    data: Object.keys(productData[product]).map(function(year) {
                        return Object.keys(productData[product][year]).map(function(month) {
                            return productData[product][year][month] || 0;
                        });
                    }).flat(),
                    borderColor: 'rgba(255, 99, 132, 1)', 
                    fill: false
                };
            })
        }
    });

    // ฟังก์ชันเพื่ออัปเดตกราฟตามช่วงเวลา
    document.getElementById('timePeriodSelect').addEventListener('change', function() {
        var selectedPeriod = this.value;
        var newLabels = [];
        var newData = [];
        var labelPrefix = "";

        if (selectedPeriod == "monthly") {
            newLabels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            newData = Object.keys(monthlyData).map(function(year) {
                return Object.keys(monthlyData[year]).map(function(month) {
                    return monthlyData[year][month] || 0;
                });
            }).flat();
            labelPrefix = "ข้อมูลรายเดือน";
        } else if (selectedPeriod == "quarterly") {
            newLabels = ['ไตรมาส 1', 'ไตรมาส 2', 'ไตรมาส 3', 'ไตรมาส 4'];
            newData = Object.keys(quarterlyData).map(function(year) {
                return Object.keys(quarterlyData[year]).map(function(quarter) {
                    return quarterlyData[year][quarter].reduce(function(a, b) { return a + b; }, 0);
                });
            }).flat();
            labelPrefix = "ข้อมูลรายไตรมาส";
        } else if (selectedPeriod == "yearly") {
            newLabels = Object.keys(yearlyData);
            newData = Object.keys(yearlyData).map(function(year) {
                return yearlyData[year].reduce(function(a, b) { return a + b; }, 0);
            });
            labelPrefix = "ข้อมูลรายปี";
        }

        // อัปเดตกราฟ
        timePeriodChart.data.labels = newLabels;
        timePeriodChart.data.datasets[0].label = labelPrefix;
        timePeriodChart.data.datasets[0].data = newData;
        timePeriodChart.update();
    });

    // ฟังก์ชันเพื่ออัปเดตกราฟสินค้า
    document.getElementById('productTimeSelect').addEventListener('change', function() {
        var selectedPeriod = this.value;
        var newLabels = [];
        var newData = [];

        if (selectedPeriod == "monthly") {
            newLabels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            newData = Object.keys(productData).map(function(product) {
                return Object.keys(productData[product]).map(function(year) {
                    return Object.keys(productData[product][year]).map(function(month) {
                        return productData[product][year][month] || 0;
                    });
                }).flat();
            });
        } else if (selectedPeriod == "quarterly") {
            newLabels = ['ไตรมาส 1', 'ไตรมาส 2', 'ไตรมาส 3', 'ไตรมาส 4'];
            newData = Object.keys(productData).map(function(product) {
                return Object.keys(productData[product]).map(function(year) {
                    return Object.keys(productData[product][year]).map(function(quarter) {
                        return productData[product][year][quarter] || 0;
                    });
                }).flat();
            });
        } else if (selectedPeriod == "yearly") {
            newLabels = Object.keys(yearlyData);
            newData = Object.keys(productData).map(function(product) {
                return Object.keys(yearlyData).map(function(year) {
                    return productData[product][year] || 0;
                });
            });
        }

        // อัปเดตกราฟสินค้า
        productChart.data.labels = newLabels;
        productChart.data.datasets = newData.map(function(data, index) {
            return {
                label: Object.keys(productData)[index],
                data: data,
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false
            };
        });
        productChart.update();
    });

    // อัปเดตกราฟเมื่อเลือกช่วงเวลา
    document.getElementById('timePeriodSelect').addEventListener('change', function() {
        updateEmployeeProductChart(this.value);
    });

    // เริ่มต้นด้วยข้อมูลรายเดือน
    updateEmployeeProductChart('monthly');
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
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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

    createChart("bestSellingChart", bestSellingProducts, "ยอดขายสูงสุด");
    createChart("worstSellingChart", worstSellingProducts, "ยอดขายต่ำสุด");
</script>

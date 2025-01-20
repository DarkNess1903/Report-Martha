<?php
session_start();
include 'db.php';

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
    <title>กราฟข้อมูลทางการเงิน</title>
    <!-- เชื่อมต่อ Bootstrap 5 และ Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container mt-5">
    <div class="row">
        <div class="col-12 text-center">
            <h2>กราฟข้อมูลทางการเงิน</h2>
            <p>เลือกช่วงเวลาที่ต้องการแสดงข้อมูลยอดขาย</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- ตัวเลือกในการเลือกช่วงเวลาที่ต้องการแสดง -->
                    <div class="form-group mb-4">
                        <label for="timePeriodSelect">เลือกช่วงเวลา:</label>
                        <select id="timePeriodSelect" class="form-select">
                            <option value="monthly">รายเดือน</option>
                            <option value="quarterly">รายไตรมาส</option>
                            <option value="yearly">รายปี</option>
                        </select>
                    </div>
                    <!-- แสดงกราฟยอดขาย -->
                    <canvas id="timePeriodChart"></canvas>
                </div>
            </div>
        </div>
    </div>


    <!-- แสดงกราฟสินค้า -->
    <div class="row mb-4">
        <div class="col-12">
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


    <!-- กราฟพนักงาน -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- ตัวเลือกสำหรับช่วงเวลา -->
                    <div class="form-group mb-4">
                        <label for="timePeriodSelect">เลือกช่วงเวลา:</label>
                        <select id="timePeriodSelect" class="form-select">
                            <option value="monthly">รายเดือน</option>
                            <option value="quarterly">รายไตรมาส</option>
                            <option value="yearly">รายปี</option>
                        </select>
                    </div>
                    <!-- แสดงกราฟ -->
                    <canvas id="employeeProductChart"></canvas>
                </div>
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

    //กราฟพนักงาน
        var ctx = document.getElementById('employeeProductChart').getContext('2d');

    // ฟังก์ชันสำหรับดึงข้อมูลและสร้างกราฟ
    function updateEmployeeProductChart(timePeriod) {
        var labels = [];
        var datasets = [];

        // สร้างข้อมูลสำหรับแต่ละพนักงาน
        Object.keys(employeeProductSales).forEach(function(username) {
            Object.keys(employeeProductSales[username]).forEach(function(product) {
                var data = [];
                if (timePeriod === 'monthly') {
                    labels = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
                              'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                    Object.keys(employeeProductSales[username][product]['monthly']).forEach(function(year) {
                        labels.forEach(function(month, index) {
                            var monthIndex = index + 1; // เดือนเริ่มจาก 1
                            data.push(employeeProductSales[username][product]['monthly'][year][monthIndex] || 0);
                        });
                    });
                } else if (timePeriod === 'quarterly') {
                    labels = ['ไตรมาส 1', 'ไตรมาส 2', 'ไตรมาส 3', 'ไตรมาส 4'];
                    Object.keys(employeeProductSales[username][product]['quarterly']).forEach(function(year) {
                        labels.forEach(function(quarter, index) {
                            var quarterIndex = index + 1; // ไตรมาสเริ่มจาก 1
                            data.push(employeeProductSales[username][product]['quarterly'][year][quarterIndex] || 0);
                        });
                    });
                } else if (timePeriod === 'yearly') {
                    labels = Object.keys(employeeProductSales[username][product]['yearly']);
                    data = labels.map(function(year) {
                        return employeeProductSales[username][product]['yearly'][year] || 0;
                    });
                }

                // เพิ่มข้อมูลเข้า datasets
                datasets.push({
                    label: username + ' - ' + product,
                    data: data,
                    backgroundColor: getRandomColor(),
                    borderColor: getRandomColor(),
                    borderWidth: 1
                });
            });
        });

        // อัปเดตกราฟ
        employeeProductChart.data.labels = labels;
        employeeProductChart.data.datasets = datasets;
        employeeProductChart.update();
    }

    // ฟังก์ชันสร้างสีสุ่ม
    function getRandomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    // สร้างกราฟ
    var employeeProductChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
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

<?php
session_start();
include 'db.php';  // เชื่อมต่อกับฐานข้อมูล

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// แสดงข้อมูลผู้ใช้
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// ดึงปีทั้งหมดจากฐานข้อมูล
$sql_years = "SELECT DISTINCT year FROM sales ORDER BY year ASC";
$result_years = $conn->query($sql_years);
$all_years = [];
while ($row = $result_years->fetch_assoc()) {
    $all_years[] = $row['year'];
}

// ดึงไตรมาสทั้งหมดจากฐานข้อมูล
$sql_quarters = "SELECT DISTINCT quarter FROM sales ORDER BY quarter ASC";
$result_quarters = $conn->query($sql_quarters);
$all_quarters = [];
while ($row = $result_quarters->fetch_assoc()) {
    $all_quarters[] = $row['quarter'];
}

// รับค่าปีและไตรมาสที่เลือก
$selected_years = isset($_GET['years']) ? $_GET['years'] : [];
$selected_quarters = isset($_GET['quarters']) ? $_GET['quarters'] : [];

// กำหนดค่าเริ่มต้นเป็นทุกปีและทุกไตรมาสหากไม่ได้เลือก
if (empty($selected_years)) {
    $selected_years = $all_years; // ค่าเริ่มต้นคือทุกปี
}

if (empty($selected_quarters)) {
    $selected_quarters = $all_quarters; // ค่าเริ่มต้นคือทุกไตรมาส
}

// ดึงข้อมูลยอดขายรวมแยกตามปีและไตรมาสที่เลือก
$sales_data = [];
foreach ($selected_years as $year) {
    foreach ($selected_quarters as $quarter) {
        $sql = "SELECT SUM(amount) AS total_sales 
                FROM sales 
                WHERE year = ? AND quarter = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $year, $quarter);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $sales_data["$quarter $year"] = $row['total_sales'] ?: 0;
        $stmt->close();
    }
}

// ดึงข้อมูลยอดขายของพนักงานแต่ละคนแยกตามปีและไตรมาสที่เลือก

$sales_per_person = []; // กำหนดค่าเริ่มต้นให้เป็น array ว่าง
foreach ($selected_years as $year) {
    foreach ($selected_quarters as $quarter) {
        if (!isset($sales_per_person["$year $quarter"])) {
            $sales_per_person["$year $quarter"] = [];
        }

        $sql = "SELECT users.username, SUM(sales.amount) AS total_sales 
                FROM sales 
                INNER JOIN users ON sales.user_id = users.id
                WHERE year = ? AND quarter = ?
                GROUP BY users.username";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $year, $quarter);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sales_per_person["$year $quarter"][] = $row;
        }
        $stmt->close();
    }
}

$quarter_to_month = [
    '1' => 'มกราคม',
    '2' => 'เมษายน',
    '3' => 'กรกฎาคม',
    '4' => 'ตุลาคม'
];

// สร้าง labels และ datasets
$labels = [];
$datasets = [];

// เตรียมข้อมูลในรูปแบบ labels และ datasets
foreach ($sales_per_person as $year_quarter => $data) {
    list($year, $quarter) = explode(' ', $year_quarter); // แยกปีและไตรมาส
    $month = $quarter_to_month[$quarter] ?? $quarter;    // แปลงไตรมาสเป็นเดือน
    $labels[] = "$month $year";

    foreach ($data as $person) {
        $username = $person['username'];
        $total_sales = $person['total_sales'];

        // ตรวจสอบว่ามี dataset สำหรับคนนี้หรือยัง
        if (!isset($datasets[$username])) {
            $datasets[$username] = [
                'label' => $username,
                'data' => array_fill(0, count($labels) - 1, null), // เติม null สำหรับ label ก่อนหน้า
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderWidth' => 1
            ];
        }
        $datasets[$username]['data'][] = $total_sales; // เติมข้อมูลยอดขาย
    }
}

// เติม null ให้ dataset ที่ข้อมูลยังไม่ครบทุกเดือน
foreach ($datasets as &$dataset) {
    $missing = count($labels) - count($dataset['data']);
    $dataset['data'] = array_merge($dataset['data'], array_fill(0, $missing, null));
}
unset($dataset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Dashboard</title>
</head>
<body>
    <!-- Include Top Navbar -->
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Dashboard</h1>

    <!-- แบบฟอร์มเลือกปีและไตรมาส -->
    <div class="container mt-4">
        <h2>เลือกปีและไตรมาสสำหรับการดูกราฟ</h2>
        <form action="dashboard.php" method="GET">
            <div class="border p-4 rounded">
                <div class="row">
                    <!-- ฟอร์มเลือกปี -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">เลือกปีที่ต้องการ:</label>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($all_years as $year): ?>
                                <div class="form-check form-check-inline me-3">
                                    <input class="form-check-input" type="checkbox" name="years[]" value="<?= $year ?>" 
                                        <?= in_array($year, $selected_years) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= $year ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ฟอร์มเลือกไตรมาส -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">เลือกไตรมาสที่ต้องการ:</label>
                        <div class="d-flex flex-wrap">
                            <?php 
                            // กำหนดข้อมูลไตรมาสและเดือน
                            $quarters = [
                                1 => 'มกราคม',
                                2 => 'เมษายน',
                                3 => 'กรกฎาคม',
                                4 => 'ตุลาคม'
                            ];
                            foreach ($quarters as $quarter => $months): ?>
                                <div class="form-check form-check-inline me-3">
                                    <input class="form-check-input" type="checkbox" name="quarters[]" value="<?= $quarter ?>" 
                                        <?= in_array($quarter, $selected_quarters) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= $months ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                     <!-- ปุ่มยืนยัน -->
                <button type="submit" class="btn btn-primary mt-3">ดูกราฟ</button>
            </div>
        </div>
        </form>
    </div>

        <div class="row">
            <!-- กราฟยอดขายรวมแยกตามปีและไตรมาส -->
            <div class="col-md-6 mt-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h5>กราฟยอดขายรวมแยกตามปีและไตรมาส</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟยอดขายของพนักงานแต่ละคนแยกตามปีและไตรมาส -->
            <div class="col-md-6 mt-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h5>กราฟยอดขายของพนักงานแต่ละคนแยกตามปีและไตรมาส</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="personSalesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // กราฟยอดขายรวมแยกตามปีและไตรมาส
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($sales_data)) ?>,
                datasets: [{
                    label: 'ยอดขายรวม (บาท)',
                    data: <?= json_encode(array_values($sales_data)) ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'ยอดขาย: ' + tooltipItem.raw + ' บาท';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' บาท';
                            }
                        }
                    }
                }
            }
        });

        // กราฟยอดขายของพนักงานแต่ละคนแยกตามเดือน
        const ctxPerson = document.getElementById('personSalesChart').getContext('2d');
        const personSalesData = {
            labels: <?= json_encode($labels); ?>, // ป้ายกำกับ เช่น ["มกราคม 2023", "เมษายน 2023"]
            datasets: <?= json_encode(array_values($datasets)); ?> // ข้อมูลกราฟแยกตามคน
        };

        const personSalesChart = new Chart(ctxPerson, {
            type: 'bar',
            data: personSalesData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'ยอดขาย: ' + tooltipItem.raw + ' บาท';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' บาท';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'เดือน/ปี'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

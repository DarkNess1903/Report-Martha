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

// กำหนดปีเริ่มต้นและไตรมาสเริ่มต้น
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$quarter = isset($_GET['quarter']) ? $_GET['quarter'] : 'Q1';

// ดึงข้อมูลยอดขายของพนักงานแต่ละคนตามปีและไตรมาส
$sql_sales = "SELECT sales.user_id, users.username, SUM(sales.amount) AS total_sales
              FROM sales
              INNER JOIN users ON sales.user_id = users.id
              WHERE sales.year = '$year' AND sales.quarter = '$quarter'
              GROUP BY sales.user_id";
$result_sales = $conn->query($sql_sales);
$sales_data = [];
while ($row = $result_sales->fetch_assoc()) {
    $sales_data[] = $row;
}

// ดึงข้อมูลยอดขายรวมของทุกคนตามปีและไตรมาส
$sql_total_sales = "SELECT SUM(amount) AS total_sales 
                    FROM sales
                    WHERE year = '$year' AND quarter = '$quarter'";
$result_total_sales = $conn->query($sql_total_sales);
$total_sales_row = $result_total_sales->fetch_assoc();
$total_sales = $total_sales_row['total_sales'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Dashboard</title>
</head>
<body>
    <!-- Include Top Navbar -->
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">ยินดีต้อนรับ, <?= htmlspecialchars($username) ?>!</h1>
        <p>คุณมีบทบาทเป็น: <strong><?= htmlspecialchars($role) ?></strong></p>

        <!-- เลือกปีและไตรมาส -->
        <form action="dashboard.php" method="GET" class="mb-4">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <label for="year">เลือกปี</label>
                    <select class="form-select" name="year" id="year" onchange="this.form.submit()">
                        <?php for ($i = 2020; $i <= date('Y'); $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label for="quarter">เลือกไตรมาส</label>
                    <select class="form-select" name="quarter" id="quarter" onchange="this.form.submit()">
                        <option value="Q1" <?= $quarter == 'Q1' ? 'selected' : '' ?>>Q1</option>
                        <option value="Q2" <?= $quarter == 'Q2' ? 'selected' : '' ?>>Q2</option>
                        <option value="Q3" <?= $quarter == 'Q3' ? 'selected' : '' ?>>Q3</option>
                        <option value="Q4" <?= $quarter == 'Q4' ? 'selected' : '' ?>>Q4</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="row">
            <!-- กราฟยอดขายรวมทั้งหมด -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h5>กราฟยอดขายรวม (ปี <?= $year ?> ไตรมาส <?= $quarter ?>)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="totalSalesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟยอดขายของแต่ละคน -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h5>กราฟยอดขายของพนักงานแต่ละคน</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="individualSalesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ข้อมูลกราฟยอดขายรวมทั้งหมด -->
        <script>
            const ctxTotalSales = document.getElementById('totalSalesChart').getContext('2d');
            const totalSalesChart = new Chart(ctxTotalSales, {
                type: 'bar',
                data: {
                    labels: ['ยอดขายรวม'],
                    datasets: [{
                        label: 'ยอดขาย (บาท)',
                        data: [<?php echo $total_sales; ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
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

            const ctxIndividualSales = document.getElementById('individualSalesChart').getContext('2d');
            const individualSalesChart = new Chart(ctxIndividualSales, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($sales_data, 'username')); ?>,
                    datasets: [{
                        label: 'ยอดขาย (บาท)',
                        data: <?php echo json_encode(array_column($sales_data, 'total_sales')); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
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
        </script>
    </div>
</body>
</html>

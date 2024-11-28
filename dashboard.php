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
        $sales_data["$year-Q$quarter"] = $row['total_sales'] ?: 0;
        $stmt->close();
    }
}

// ดึงข้อมูลยอดขายของพนักงานแต่ละคนแยกตามปีและไตรมาสที่เลือก
$sales_per_person = [];
foreach ($selected_years as $year) {
    foreach ($selected_quarters as $quarter) {
        // ตรวจสอบว่าเคยเก็บข้อมูลปี-ไตรมาสนี้หรือยัง ถ้ายังให้สร้าง
        if (!isset($sales_per_person["$year-Q$quarter"])) {
            $sales_per_person["$year-Q$quarter"] = [];
        }

        $sql = "SELECT users.username, SUM(sales.amount) AS total_sales 
                FROM sales 
                INNER JOIN users ON sales.user_id = users.id
                WHERE year = ? AND quarter = ?
                GROUP BY users.username";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $year, $quarter);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sales_per_person["$year-Q$quarter"][] = $row;
        }
        $stmt->close();
    }
}
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
        <p>คุณมีบทบาทเป็น: <strong><?= htmlspecialchars($role) ?></strong></p>

        <!-- แบบฟอร์มเลือกปีและไตรมาส -->
        <form action="dashboard.php" method="GET">
            <label>เลือกปีที่ต้องการ:</label><br>
            <?php foreach ($all_years as $year): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="years[]" value="<?= $year ?>" 
                           <?= in_array($year, $selected_years) ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= $year ?></label>
                </div>
            <?php endforeach; ?>

            <label>เลือกไตรมาสที่ต้องการ:</label><br>
            <?php foreach ($all_quarters as $quarter): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="quarters[]" value="<?= $quarter ?>" 
                           <?= in_array($quarter, $selected_quarters) ? 'checked' : '' ?>>
                    <label class="form-check-label">Q<?= $quarter ?></label>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary mt-3">ดูกราฟ</button>
        </form>

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

        // กราฟยอดขายของพนักงานแต่ละคนแยกตามปีและไตรมาส
        const ctxPerson = document.getElementById('personSalesChart').getContext('2d');
        const personSalesData = {
            labels: <?= json_encode(array_keys($sales_per_person)); ?>,
            datasets: []
        };

        // เรียงข้อมูลในรูปแบบที่ต้องการ
        <?php foreach ($sales_per_person as $year_quarter => $data): ?>
            <?php foreach ($data as $person): ?>
                personSalesData.datasets.push({
                    label: '<?= $person['username'] ?> (<?= $year_quarter ?>)',
                    data: [
                        // คำนวณยอดขายสำหรับปีและไตรมาสนี้
                        <?= $person['total_sales'] ?>
                    ],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderWidth: 1
                });
            <?php endforeach; ?>
        <?php endforeach; ?>

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
                    }
                }
            }
        });
    </script>
</body>
</html>

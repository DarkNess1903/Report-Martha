<?php
session_start();
include 'db.php';

// ตรวจสอบปีที่เลือก
$selected_year = isset($_GET['year']) && is_numeric($_GET['year']) ? $_GET['year'] : date("Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales by Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'topnavbar.php'; ?>

<div class="container my-5">
    <h3 class="text-center mb-4">ยอดขาย Product รายปี</h3>

    <!-- ฟอร์มเลือกปีใน card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row justify-content-center mb-0">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <label for="year" class="form-label me-2 mb-0 flex-shrink-0">เลือกปี:</label>
                    <select name="year" id="year" class="form-select flex-grow-1" onchange="this.form.submit()">
                        <?php
                        $year_query = $conn->query("SELECT DISTINCT year FROM sales ORDER BY year DESC");
                        while ($row = $year_query->fetch_assoc()) {
                            $year = $row['year'];
                            $selected = ($year == $selected_year) ? "selected" : "";
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>
    

        <!-- ดึงข้อมูลยอดขายตามปีที่เลือก -->
        <?php
        $stmt = $conn->prepare("
            SELECT product, SUM(amount) as total_amount 
            FROM sales 
            WHERE year = ? 
            GROUP BY product
        ");
        $stmt->bind_param("s", $selected_year);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        $total_year = 0; // เก็บยอดรวมทั้งปี
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            $total_year += $row['total_amount'];
        }

        // จัดเรียงยอดขายจากมากไปน้อย
        usort($data, function($a, $b) {
            return $b['total_amount'] - $a['total_amount'];
        });

        $products = [];
        $amounts = [];
        $colors = [];
        $borderColors = [];
        $table_rows = "";

        function generatePastelColor($index) {
            $hue = ($index * 40) % 360;
            return [
                "hsla($hue, 70%, 80%, 0.6)", // background
                "hsla($hue, 70%, 60%, 1)"    // border
            ];
        }

        $i = 1; // เริ่มลำดับจาก 1
        foreach ($data as $row) {
            $products[] = $row['product'];
            $amounts[] = $row['total_amount'];
            [$bgColor, $borderColor] = generatePastelColor($i);
            $colors[] = $bgColor;
            $borderColors[] = $borderColor;

            $table_rows .= "<tr>
                <td>{$i}</td>
                <td>".htmlspecialchars($row['product'])."</td>
                <td>฿".number_format($row['total_amount'])."</td>
            </tr>";

            $i++;
        }

        $stmt->close();
        $conn->close();
        ?>

        <!-- ยอดขายรวมทั้งปี -->
        <div class="mb-4 text-center">
            <h4>ยอดขายรวมทั้งหมดของปี <?= $selected_year ?>: <span class="text-success">฿<?= number_format($total_year) ?></span></h4>
        </div>
    </div>

    <!-- กราฟยอดขายใน card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <h5 class="card-title text-center">กราฟยอดขาย Products <?= $selected_year ?></h5>
            <div class="chart-container" style="position: relative; height:60vh; width:100%;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ตารางยอดขายรวมต่อสินค้าใน card -->
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title text-center">ยอดขาย products รายปี (<?= $selected_year ?>)</h5>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>สินค้า</th>
                        <th>ยอดขายรวม (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    <?= $table_rows ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($products); ?>,
        datasets: [{
            label: 'ยอดขายรวม <?= $selected_year ?>',
            data: <?= json_encode($amounts); ?>,
            backgroundColor: <?= json_encode($colors); ?>,
            borderColor: <?= json_encode($borderColors); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '฿' + context.parsed.y.toLocaleString();
                    }
                }
            },
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

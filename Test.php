<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$timePeriod = isset($_GET['timePeriod']) ? $_GET['timePeriod'] : 'monthly';

// จัดการข้อมูลยอดขาย (เพิ่ม, ลบ, แก้ไข)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_sale'])) {
        $year = $_POST['year'];
        $month = $_POST['month'] ?? null;
        $quarter = $_POST['quarter'] ?? null;
        $product = $_POST['product'];
        $amount = $_POST['amount'];
        
        $sql = "INSERT INTO sales (user_id, year, month, quarter, product, amount) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiissi", $user_id, $year, $month, $quarter, $product, $amount);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['edit_sale'])) {
        $sale_id = $_POST['sale_id'];
        $year = $_POST['year'];
        $month = $_POST['month'] ?? null;
        $quarter = $_POST['quarter'] ?? null;
        $product = $_POST['product'];
        $amount = $_POST['amount'];
        
        $sql = "UPDATE sales SET year=?, month=?, quarter=?, product=?, amount=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissii", $year, $month, $quarter, $product, $amount, $sale_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_sale'])) {
        $sale_id = $_POST['sale_id'];
        
        $sql = "DELETE FROM sales WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $sale_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: sales_details.php?user_id=$user_id&timePeriod=$timePeriod");
    exit();
}

// ปรับ SQL Query ตามตัวเลือกช่วงเวลา
if ($timePeriod == 'monthly') {
    $sql = "SELECT id, year, month, product, amount FROM sales WHERE user_id = ? ORDER BY year ASC, month ASC";
} elseif ($timePeriod == 'quarterly') {
    $sql = "SELECT id, year, quarter, product, amount FROM sales WHERE user_id = ? ORDER BY year ASC, quarter ASC";
} else {
    $sql = "SELECT id, year, product, amount FROM sales WHERE user_id = ? ORDER BY year ASC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$sales_data = [];
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function confirmAction(message) {
            return confirm(message);
        }
    </script>
    <style>
        #chart-container {
            width: 100%;
            max-width: 800px;
            margin: auto;
        }
    </style>
</head>
<body class="container mt-4">
    <h2 class="text-center">Sales Report</h2>
    
    <label for="timePeriodSelect" class="form-label">เลือกช่วงเวลา:</label>
    <select id="timePeriodSelect" class="form-select w-25" onchange="updateTimePeriod()">
        <option value="monthly" <?= ($timePeriod == 'monthly') ? 'selected' : '' ?>>รายเดือน</option>
        <option value="quarterly" <?= ($timePeriod == 'quarterly') ? 'selected' : '' ?>>รายไตรมาส</option>
        <option value="yearly" <?= ($timePeriod == 'yearly') ? 'selected' : '' ?>>รายปี</option>
    </select>

    <div id="chart-container" class="mt-4">
        <canvas id="salesChart"></canvas>
    </div>
    <div id="chart-container" class="mt-4">
        <canvas id="totalSalesChart"></canvas>
    </div>
    
    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>ปี</th>
                <?php if ($timePeriod == 'monthly') echo '<th>เดือน</th>'; ?>
                <?php if ($timePeriod == 'quarterly') echo '<th>ไตรมาส</th>'; ?>
                <th>สินค้า</th>
                <th>ยอดขาย</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales_data as $row) { ?>
                <tr>
                    <td><?= $row['year'] ?></td>
                    <?php if ($timePeriod == 'monthly') echo '<td>' . $row['month'] . '</td>'; ?>
                    <?php if ($timePeriod == 'quarterly') echo '<td>' . $row['quarter'] . '</td>'; ?>
                    <td><?= $row['product'] ?></td>
                    <td><?= number_format($row['amount'], 2) ?></td>
                    <td>
                        <form method="post" class="d-inline" onsubmit="return confirmAction('คุณแน่ใจหรือไม่ที่จะลบรายการนี้?')">
                            <input type="hidden" name="sale_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="delete_sale" class="btn btn-danger">ลบ</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    
    <script>
        function updateTimePeriod() {
            let timePeriod = document.getElementById("timePeriodSelect").value;
            window.location.href = "sales_details.php?user_id=<?= $user_id ?>&timePeriod=" + timePeriod;
        }

        let salesData = <?= json_encode($sales_data) ?>;
        let labels = salesData.map(sale => sale.product);
        let amounts = salesData.map(sale => sale.amount);

        new Chart(document.getElementById("salesChart"), {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "ยอดขายสินค้า",
                    data: amounts,
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    </script>
</body>
</html>

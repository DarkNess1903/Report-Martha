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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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
    <title>Sales Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

<body>
    <?php include 'topnavbar.php'; ?>
    
    <h2 class="text-center mb-4">📊 รายงานยอดขาย</h2>
    <div class="card p-3 mb-4 text-center">
        <div class="d-flex justify-content-center align-items-center">
            <label for="timePeriodSelect" class="form-label fw-bold me-3">🕒 เลือกช่วงเวลา:</label>
            <select id="timePeriodSelect" class="form-select w-25" onchange="updateTimePeriod()">
                <option value="monthly" <?= ($timePeriod == 'monthly') ? 'selected' : '' ?>>รายเดือน</option>
                <option value="quarterly" <?= ($timePeriod == 'quarterly') ? 'selected' : '' ?>>รายไตรมาส</option>
                <option value="yearly" <?= ($timePeriod == 'yearly') ? 'selected' : '' ?>>รายปี</option>
            </select>
        </div>
    </div>

    <!-- กราฟ -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5 class="text-center">📊 ยอดขายสินค้า</h5>
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5 class="text-center">📈 ยอดขายรวมทุกช่วงเวลา</h5>
                <canvas id="totalSalesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
    <!-- กราฟ 📉 ยอดขายพนักงาน -->
    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h5 class="text-center">📉 ยอดขายพนักงาน</h5>
            <canvas id="employeeSalesChart"></canvas>
        </div>
    </div>

    <!-- ตาราง 📋 ข้อมูลยอดขาย -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📋 ข้อมูลยอดขาย</h5>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                    ➕ เพิ่มข้อมูล
                </button>
            </div>
            <div class="card-body">
            <table id= "tabledata" class="table table-striped table-boredered">
                <thead style="font-size: small;">
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
                                <?php if ($timePeriod == 'monthly') echo '<td>' . ($row['month'] ?? '-') . '</td>'; ?>
                                <?php if ($timePeriod == 'quarterly') echo '<td>' . ($row['quarter'] ?? '-') . '</td>'; ?>
                                <td><?= $row['product'] ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td>
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                        ✏️
                                    </button>

                                    <!-- ปุ่มลบ -->
                                    <form method="post" class="d-inline" onsubmit="return confirmAction('คุณแน่ใจหรือไม่ที่จะลบรายการนี้?')">
                                        <input type="hidden" name="sale_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_sale" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal แก้ไข -->
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $row['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">📝 แก้ไขยอดขาย</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="sale_id" value="<?= $row['id'] ?>">

                                                <div class="mb-3">
                                                    <label class="form-label">ปี</label>
                                                    <input type="number" class="form-control" name="year" value="<?= $row['year'] ?>" required>
                                                </div>

                                                <?php if ($timePeriod == 'monthly') { ?>
                                                    <div class="mb-3">
                                                        <label class="form-label">เดือน</label>
                                                        <input type="number" class="form-control" name="month" value="<?= $row['month'] ?? '' ?>" min="1" max="12">
                                                    </div>
                                                <?php } ?>

                                                <?php if ($timePeriod == 'quarterly') { ?>
                                                    <div class="mb-3">
                                                        <label class="form-label">ไตรมาส</label>
                                                        <input type="number" class="form-control" name="quarter" value="<?= $row['quarter'] ?? '' ?>" min="1" max="4">
                                                    </div>
                                                <?php } ?>

                                                <div class="mb-3">
                                                    <label class="form-label">สินค้า</label>
                                                    <input type="text" class="form-control" name="product" value="<?= $row['product'] ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">ยอดขาย</label>
                                                    <input type="number" class="form-control" name="amount" value="<?= $row['amount'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ ยกเลิก</button>
                                                <button type="submit" name="edit_sale" class="btn btn-primary">💾 บันทึก</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script>
        function updateTimePeriod() {
            let timePeriod = document.getElementById("timePeriodSelect").value;
            window.location.href = "sales_details.php?user_id=<?= $user_id ?>&timePeriod=" + timePeriod;
        }
    </script>
</body>

<!-- Modal เพิ่มข้อมูลยอดขาย -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSaleModalLabel">เพิ่มข้อมูลยอดขาย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">

                    <div class="mb-3">
                        <label class="form-label">ปี</label>
                        <input type="number" class="form-control" name="year" required>
                    </div>

                    <?php if ($timePeriod == 'monthly') { ?>
                        <div class="mb-3">
                            <label class="form-label">เดือน</label>
                            <input type="number" class="form-control" name="month" min="1" max="12">
                        </div>
                    <?php } ?>

                    <?php if ($timePeriod == 'quarterly') { ?>
                        <div class="mb-3">
                            <label class="form-label">ไตรมาส</label>
                            <input type="number" class="form-control" name="quarter" min="1" max="4">
                        </div>
                    <?php } ?>

                    <div class="mb-3">
                        <label class="form-label">สินค้า</label>
                        <input type="text" class="form-control" name="product" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ยอดขาย</label>
                        <input type="number" class="form-control" name="amount" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_sale" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

    
<script>
    function updateTimePeriod() {
        let timePeriod = document.getElementById("timePeriodSelect").value;
        window.location.href = "sales_details.php?user_id=<?= $user_id ?>&timePeriod=" + timePeriod;
    }

    let salesData = <?= json_encode($sales_data) ?>;

function processSalesData(salesData, timePeriod) {
    let salesSummary = {};
    let productSales = {};

    salesData.forEach(sale => {
        let key;
        if (timePeriod === 'monthly') {
            key = `${sale.year}-${sale.month}`;
        } else if (timePeriod === 'quarterly') {
            key = `${sale.year} Q${sale.quarter}`;
        } else {
            key = sale.year;
        }

        // ยอดขายรวม
        if (!salesSummary[key]) {
            salesSummary[key] = 0;
        }
        salesSummary[key] += parseFloat(sale.amount);

        // ยอดขายแยกตามสินค้า
        if (!productSales[sale.product]) {
            productSales[sale.product] = {};
        }
        if (!productSales[sale.product][key]) {
            productSales[sale.product][key] = 0;
        }
        productSales[sale.product][key] += parseFloat(sale.amount);
    });

    return {
        labels: Object.keys(salesSummary),
        amounts: Object.values(salesSummary),
        productSales: productSales
    };
}

// Process data based on the selected year
let timePeriod = "<?= $timePeriod ?>";
let processedData = processSalesData(salesData, timePeriod);

// 🔵 กราฟแท่ง: ยอดขายสินค้าแต่ละตัว
new Chart(document.getElementById("salesChart"), {
    type: "bar",
    data: {
        labels: Object.keys(processedData.productSales),
        datasets: [{
            label: "ยอดขายสินค้า",
            data: Object.values(processedData.productSales).map(obj => Object.values(obj).reduce((a, b) => a + b, 0)),
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

// 🔴 กราฟเส้น: ยอดขายรวมของพนักงาน
new Chart(document.getElementById("employeeSalesChart"), {
    type: "line",
    data: {
        labels: processedData.labels,
        datasets: [{
            label: "ยอดขายพนักงาน",
            data: processedData.amounts,
            borderColor: "rgba(75, 192, 192, 1)",
            backgroundColor: "rgba(75, 192, 192, 0.2)",
            borderWidth: 2,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            x: {
                title: {
                    display: true,
                    text: "ช่วงเวลา"
                }
            },
            y: {
                title: {
                    display: true,
                    text: "ยอดขาย (บาท)"
                }
            }
        }
    }
});

// 🟢 กราฟเส้น: ยอดขายรวมทุกช่วงเวลา
new Chart(document.getElementById("totalSalesChart"), {
    type: "line",
    data: {
        labels: processedData.labels,
        datasets: [{
            label: "ยอดขายรวมทุกช่วงเวลา",
            data: processedData.amounts,
            borderColor: "rgba(255, 99, 132, 1)",
            backgroundColor: "rgba(255, 99, 132, 0.2)",
            borderWidth: 2,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            x: {
                title: {
                    display: true,
                    text: "ช่วงเวลา"
                }
            },
            y: {
                title: {
                    display: true,
                    text: "ยอดขาย (บาท)"
                }
            }
        }
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
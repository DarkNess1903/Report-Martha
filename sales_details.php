<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = intval($_GET['user_id']);
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

$monthNames = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
$timePeriod = isset($_GET['timePeriod']) ? $_GET['timePeriod'] : 'monthly';

// จัดการข้อมูลยอดขาย (เพิ่ม, ลบ, แก้ไข)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_sale'])) {
        $year = $_POST['year'];
        $month = $_POST['month'] ?? null;

        // ✅ คำนวณไตรมาสจากเดือน
        $quarter = null;
        if ($month !== null) {
            $month = intval($month);
            if ($month >= 1 && $month <= 12) {
                $quarter = ceil($month / 3);
            }
        }
        $product = strtoupper(trim($_POST['product']));
        $amount = isset($_POST['amount']) ? number_format(floatval($_POST['amount']), 2, '.', '') : '0.00';
        $sql = "INSERT INTO sales (user_id, year, month, quarter, product, amount) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);   
        $stmt->bind_param("iiissd", $user_id, $year, $month, $quarter, $product, $amount);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['edit_sale'])) {
        $sale_id = $_POST['sale_id'];
        $year = $_POST['year'];
        $month = $_POST['month'] ?? null;
        $quarter = null;
        if ($month !== null) {
            $month = intval($month);
            if ($month >= 1 && $month <= 12) {
                $quarter = ceil($month / 3);
            }
        }
        $product = strtoupper(trim($_POST['product'])); // แปลงเป็นพิมพ์เล็กและตัดช่องว่าง
        $amount = isset($_POST['amount']) ? number_format(floatval($_POST['amount']), 2, '.', '') : '0.00';

        $sql = "UPDATE sales SET year=?, month=?, quarter=?, product=?, amount=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissdi", $year, $month, $quarter, $product, $amount, $sale_id);
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
    header("Location: sales_details.php?user_id=$user_id&timePeriod=$timePeriod&year=$year");
    exit();
}

// ปรับ SQL Query ตามตัวเลือกช่วงเวลา
if ($timePeriod == 'monthly') {
    $sql = "SELECT id, year, month, product, amount 
            FROM sales 
            WHERE user_id = ? AND year = ? 
            ORDER BY year ASC, month ASC";
} elseif ($timePeriod == 'quarterly') {
    $sql = "SELECT id, year, quarter, product, amount 
            FROM sales 
            WHERE user_id = ? AND year = ? 
            ORDER BY year ASC, quarter ASC";
} else {
    $sql = "SELECT id, year, product, amount 
            FROM sales 
            WHERE user_id = ? AND year = ? 
            ORDER BY year ASC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$sales_data = [];
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
}

// ดึงชื่อพนักงาน
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($employee_name);
$stmt->fetch();
$stmt->close();


// รับ user_id และ year จาก query string
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// ดึงยอดขายรวมของพนักงานในปีนั้น
$sql_total = "SELECT SUM(amount) AS total_sales 
              FROM sales 
              WHERE user_id = ? AND year = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("ii", $user_id, $selected_year);
$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total_row = $total_result->fetch_assoc();
$total_sales = $total_row['total_sales'] ?? 0;
$stmt_total->close();

?>

<!DOCTYPE html>
<html>
<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    
    <div class="container mt-5">

    <!-- ตารางข้อมูลยอดขาย -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">ข้อมูลยอดขายของพนักงาน: <?= htmlspecialchars($employee_name) ?></h5>
            <div>
                <button type="button" class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                    เพิ่มข้อมูล
                </button>
                <!-- ปุ่มเปิด modal สำหรับอัปโหลด Excel -->
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadExcelModal">
                    นำเข้า Excel
                </button>
            </div>
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
                                <?php if ($timePeriod == 'monthly'): ?>
                                    <td><?= isset($monthNames[$row['month']]) ? $monthNames[$row['month']] : '-' ?></td>
                                <?php endif; ?>
                                <?php if ($timePeriod == 'quarterly') echo '<td>' . ($row['quarter'] ?? '-') . '</td>'; ?>
                                <td><?= $row['product'] ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td>
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- ปุ่มลบ -->
                                    <form method="post" class="d-inline" onsubmit="return confirmAction('คุณแน่ใจหรือไม่ที่จะลบรายการนี้?')">
                                        <input type="hidden" name="sale_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_sale" class="btn btn-danger btn-sm" title="ลบ">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal แก้ไข -->
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $row['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">แก้ไขยอดขาย</h5>
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
                                                        <select class="form-select" name="month" required>
                                                            <?php
                                                            $months = [
                                                                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
                                                                4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
                                                                7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
                                                                10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                                                            ];
                                                            foreach ($months as $num => $name):
                                                            ?>
                                                                <option value="<?= $num ?>" <?= (isset($row['month']) && $row['month'] == $num) ? 'selected' : '' ?>>
                                                                    <?= $name ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                <?php } ?>

                                                <div class="mb-3">
                                                    <label class="form-label">สินค้า</label>
                                                    <input type="text" class="form-control" name="product" value="<?= $row['product'] ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">ยอดขาย</label>
                                                    <input type="number" class="form-control"
                                                        name="amount"
                                                        step="0.01"
                                                        min="0"
                                                        inputmode="decimal"
                                                        lang="en"
                                                        value="<?= number_format((float)$row['amount'], 2, '.', '') ?>"
                                                        placeholder="เช่น 1999.25 หรือ 2500.00"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times"></i> ยกเลิก
                                                </button>
                                                <button type="submit" name="edit_sale" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> บันทึก
                                                </button>
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
<!-- </div> -->
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
                        <label class="form-label">ปี ค.ศ</label>
                        <input type="number" class="form-control" name="year" placeholder="เช่น 20xx" required>
                    </div>

                    <?php if ($timePeriod == 'monthly') { ?>
                        <div class="mb-3">
                            <label class="form-label">เดือน</label>
                            <select class="form-select" name="month" required>
                                <option value="">-- เลือกเดือน --</option>
                                <option value="1">มกราคม</option>
                                <option value="2">กุมภาพันธ์</option>
                                <option value="3">มีนาคม</option>
                                <option value="4">เมษายน</option>
                                <option value="5">พฤษภาคม</option>
                                <option value="6">มิถุนายน</option>
                                <option value="7">กรกฎาคม</option>
                                <option value="8">สิงหาคม</option>
                                <option value="9">กันยายน</option>
                                <option value="10">ตุลาคม</option>
                                <option value="11">พฤศจิกายน</option>
                                <option value="12">ธันวาคม</option>
                            </select>
                        </div>
                    <?php } ?>

                    <div class="mb-3">
                        <label class="form-label">สินค้า</label>
                        <input type="text" class="form-control" name="product" placeholder="เช่น Product 1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ยอดขาย</label>
                        <input type="number" class="form-control" 
                            name="amount" 
                            step="0.01" 
                            min="0" 
                            inputmode="decimal"
                            lang="en"
                            placeholder="เช่น 1999.25 หรือ 2500" 
                            required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> <i class="fas fa-times"></i> ยกเลิก</button>
                    <button type="submit" name="add_sale" class="btn btn-primary"> <i class="fas fa-save"></i> บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="container mt-5">

    <h2 class="text-center mb-4">รายงานยอดขาย</h2>

    <!-- เลือกเวลา -->
    <div class="card p-3 mb-4 text-center">
        <div class="d-flex justify-content-center align-items-center flex-wrap">
            <label for="timePeriodSelect" class="form-label fw-bold me-3">เลือกช่วงเวลา:</label>
            <select id="timePeriodSelect" class="form-select w-auto" onchange="updateTimePeriod()">
                <option value="monthly" <?= ($timePeriod == 'monthly') ? 'selected' : '' ?>>รายเดือน</option>
                <option value="quarterly" <?= ($timePeriod == 'quarterly') ? 'selected' : '' ?>>รายไตรมาส</option>
            </select>
        </div>
    </div>

    <div class="row">
    <!-- ✅ แสดงยอดขายรวม -->
    <div class="col-md-12 mb-3">
        <div class="alert alert-info text-center fw-bold fs-5">
            ยอดขายรวมทั้งหมดในปี <?= $selected_year ?>: <?= number_format($total_sales, 2) ?> บาท
        </div>
    </div>

    <!-- กราฟยอดขายสินค้า -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm p-3 h-100 position-relative">
            <button class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-2"
                onclick="showFullScreenChart('salesChart')">
                <i class="fas fa-expand"></i> ขยาย
            </button>
            <h5 class="text-center mt-4">ยอดขายสินค้า</h5>
            <canvas id="salesChart" style="margin-top: 10px;"></canvas>
        </div>
    </div>

    <!-- กราฟยอดขายรวม -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm p-3 h-100 position-relative">
            <button class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-2"
                onclick="showFullScreenChart('totalSalesChart')">
                <i class="fas fa-expand"></i> ขยาย
            </button>
            <h5 class="text-center mt-4">ยอดขายรวมทุกช่วงเวลา</h5>
            <canvas id="totalSalesChart" style="margin-top: 10px;"></canvas>
        </div>
    </div>
</div>

    <!-- Modal แบบเต็มหน้าจอ -->
    <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- เปลี่ยนขนาดจาก fullscreen เป็น xl -->
            <div class="modal-content bg-white">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-4" id="chartModalLabel">กราฟแบบขยาย</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="w-100" style="height:500px;"> <!-- กำหนดความสูงกราฟ -->
                        <canvas id="fullScreenChart" style="width:100%; height:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: อัปโหลดไฟล์ Excel -->
    <div class="modal fade" id="uploadExcelModal" tabindex="-1" aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="import_excel.php" method="POST" enctype="multipart/form-data" class="modal-content">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <input type="hidden" name="year" value="<?= $selected_year ?>">
        <div class="modal-header">
            <h5 class="modal-title" id="uploadExcelModalLabel">นำเข้าไฟล์ Excel (.xlsx)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
            <label for="excelFile" class="form-label">เลือกไฟล์ Excel</label>
            <input class="form-control" type="file" id="excelFile" name="excel_file" accept=".xlsx" required>
            <small class="text-muted">รูปแบบไฟล์ต้องมีคอลัมน์: เดือน, ไตรมาส, สินค้า, ยอดขาย</small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">นำเข้า</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        </div>
        </form>
    </div>
    </div>
    
    <?php if (isset($_GET['imported'])): ?>
    <div class="alert alert-success mt-3">
        นำเข้าข้อมูลยอดขายสำเร็จจำนวน <?= intval($_GET['imported']) ?> รายการ
    </div>
<?php endif; ?>

<script>
    function updateTimePeriod() {
        let timePeriod = document.getElementById("timePeriodSelect").value;
        let year = <?= $selected_year ?>;
        window.location.href = "sales_details.php?user_id=<?= $user_id ?>&timePeriod=" + timePeriod + "&year=" + year;
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

const productLabels = Object.keys(processedData.productSales);
const productData = Object.values(processedData.productSales).map(obj =>
    Object.values(obj).reduce((a, b) => a + b, 0)
);

// สีพาสเทลสวยงาม (สำหรับ background)
const pastelColors = [
    'rgba(255, 99, 132, 0.6)',   // แดงอมชมพู
    'rgba(255, 159, 64, 0.6)',   // ส้ม
    'rgba(255, 205, 86, 0.6)',   // เหลือง
    'rgba(75, 192, 192, 0.6)',   // เขียวมิ้นต์
    'rgba(54, 162, 235, 0.6)',   // ฟ้า
    'rgba(153, 102, 255, 0.6)',  // ม่วงอ่อน
    'rgba(201, 203, 207, 0.6)'   // เทา
];

// สีเข้มสำหรับขอบ
const pastelBorders = [
    'rgba(255, 99, 132, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(255, 205, 86, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(54, 162, 235, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(201, 203, 207, 1)'
];

// ถ้าสินค้ามีมากกว่า 7 ชนิด ให้ทำสีวนซ้ำ
const backgroundColors = productLabels.map((_, i) => pastelColors[i % pastelColors.length]);
const borderColors = productLabels.map((_, i) => pastelBorders[i % pastelBorders.length]);

// 🔵 กราฟแท่ง: ยอดขายสินค้าแต่ละตัว
new Chart(document.getElementById("salesChart"), {
    type: "bar",
    data: {
        labels: productLabels,
        datasets: [{
            label: "ยอดขายสินค้า",
            data: productData,
            backgroundColor: backgroundColors,
            borderColor: borderColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false // ไม่แสดง legend ถ้ามีเพียงชุดข้อมูลเดียว
            },
            tooltip: {
                callbacks: {
                    label: context => ` ${context.dataset.label}: ${context.raw.toLocaleString()} บาท`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => value.toLocaleString() + ' บาท'
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

<script>
    const amountInput = document.querySelector('input[name="amount"]');
    amountInput.addEventListener('blur', function () {
        let value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.value = value.toFixed(2);
        }
    });
</script>
<!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="assets/js/main-123.js"></script>

<script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
        $('#tabledata').dataTable( {
        "oLanguage": {
        "sLengthMenu": "แสดง _MENU_ ข้อมูล",
        "sZeroRecords": "ไม่พบข้อมูล",
        "sInfo": "แสดง _START_ ถึง _END_ ของ _TOTAL_ ข้อมูล",
        "sInfoEmpty": "แสดง 0 ถึง 0 ของ 0 ข้อมูล",
        "sInfoFiltered": "(จากข้อมูลทั้งหมด _MAX_ ข้อมูล)",
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
    
<script>
    //เต็มจอ
    let fullScreenChartInstance;

    function showFullScreenChart(originalChartId) {
        const originalChart = Chart.getChart(originalChartId);
        if (!originalChart) return;

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
                    legend: {
                        labels: {
                            font: {
                                size: 16 // เพิ่มขนาดตัวอักษรของ legend
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: originalChart.options.plugins?.title?.text || 'กราฟ',
                        font: {
                            size: 20 // ขนาดหัวข้อกราฟ
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 14 // แกน X
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 14 // แกน Y
                            }
                        }
                    }
                }
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

</body>
</html>
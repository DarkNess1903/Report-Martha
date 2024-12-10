<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// รับค่า user_id จาก URL
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

// ดึงข้อมูลปีทั้งหมดจากฐานข้อมูล
$sql_years = "SELECT DISTINCT year FROM sales ORDER BY year ASC";
$result_years = $conn->query($sql_years);
$all_years = [];
while ($row = $result_years->fetch_assoc()) {
    $all_years[] = $row['year'];
}

// ดึงข้อมูลไตรมาสทั้งหมดจากฐานข้อมูล
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

// สร้าง SQL คำสั่ง IN ให้ถูกต้อง
$placeholders_years = implode(',', array_fill(0, count($selected_years), '?'));
$placeholders_quarters = implode(',', array_fill(0, count($selected_quarters), '?'));

// ดึงข้อมูลยอดขายของพนักงานตามปีและไตรมาสที่เลือก
$sql = "SELECT sales.year, sales.quarter, SUM(sales.amount) AS total_sales 
        FROM sales 
        WHERE sales.user_id = ? 
        AND sales.year IN ($placeholders_years)
        AND sales.quarter IN ($placeholders_quarters)
        GROUP BY sales.year, sales.quarter
        ORDER BY sales.year ASC, sales.quarter ASC";

$stmt = $conn->prepare($sql);

// เตรียมพารามิเตอร์สำหรับ bind_param
$params = array($user_id);
$params = array_merge($params, $selected_years, $selected_quarters);

// สร้างชนิดของพารามิเตอร์ที่ใช้
$types = str_repeat('i', count($params));

// bind parameters
$stmt->bind_param($types, ...$params);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();


if ($result->num_rows > 0) {
    $sales_data = [];
    while ($row = $result->fetch_assoc()) {
        $sales_data[$row['year']][$row['quarter']] = $row['total_sales'];
    }
} else {
    $sales_data = [];
}

// เพิ่มข้อมูลยอดขายใหม่
if (isset($_POST['add_sale'])) {
    $year = $_POST['year'];
    $quarter = $_POST['quarter'];
    $amount = $_POST['amount'];

    // แทรกข้อมูลยอดขายใหม่
    $stmt = $conn->prepare("INSERT INTO sales (user_id, year, quarter, amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $year, $quarter, $amount);

    if ($stmt->execute()) {
        $success_message = "เพิ่มข้อมูลยอดขายสำเร็จ";
        // รีเฟรชหน้าและส่ง user_id กลับไป
        header('Location: ' . $_SERVER['PHP_SELF'] . '?user_id=' . $user_id);
        exit();
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $stmt->error;
    }    
    $stmt->close();
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

    <title>ข้อมูลยอดขายของพนักงาน บริษัท มาร์ธา กรุ๊ปจำกัด </title>
</head>
<body>
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">ข้อมูลยอดขายของพนักงาน</h1>

        <!-- แบบฟอร์มเลือกปีและไตรมาส -->
            <div class="container mt-4">
                <h2>เลือกปีและไตรมาสเพื่อดูกราฟ</h2>
                <form action="sales_details.php" method="GET">
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
                            <button type="submit" class="btn btn-primary mt-3">ดูกราฟ</button>
                        </div>
                    </div>
                </form>

                <div class="row g-4">
                    <!-- กราฟ -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                กราฟแสดงยอดขาย
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                        
            <!-- ตาราง -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        ตารางยอดขาย
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabledata" class="table table-striped table-bordered">
                                <thead style="font-size: small;">
                                    <tr>
                                        <th>ปี</th>
                                        <th>ไตรมาส</th>
                                        <th>ยอดขาย (บาท)</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($sales_data)): ?>
                                        <?php foreach ($sales_data as $year => $quarters): ?>
                                            <?php foreach ($quarters as $quarter => $amount): ?>
                                                <tr>
                                                    <td><?= $year ?></td>
                                                    <td><?= "Q" . $quarter ?></td> <!-- แสดงไตรมาส -->
                                                    <td><?= number_format($amount, 2) ?> บาท</td>
                                                    <td>
                                                        <!-- ปุ่มแก้ไข -->
                                                        <button class="btn btn-warning btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editSaleModal" 
                                                                data-year="<?= $year ?>" 
                                                                data-quarter="<?= $quarter ?>" 
                                                                data-amount="<?= $amount ?>" 
                                                                data-user-id="<?= $user_id ?>">
                                                            แก้ไข
                                                        </button>

                                                        <!-- ปุ่มลบ -->
                                                        <form action="delete_sale.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                                            <input type="hidden" name="year" value="<?= $year ?>">
                                                            <input type="hidden" name="quarter" value="<?= $quarter ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('คุณต้องการลบยอดขายนี้หรือไม่?')">
                                                                ลบ
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">ไม่มีข้อมูลยอดขายสำหรับพนักงานคนนี้</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- จบตาราง -->

                    <!-- ปุ่มเพิ่มยอดขาย -->
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addSaleModal">เพิ่มยอดขาย</button>
                </div>
            </div>
        </div>
    </div>

        <!-- ปุ่มกลับไปหน้าหลัก -->
        <div class="text-center mt-4">
            <a href="manage_sales.php" class="btn btn-secondary">กลับไปที่หน้าหลัก</a>
        </div>
    </div>
    <br>

    <!-- Modal เพิ่มยอดขาย -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSaleModalLabel">เพิ่มยอดขายใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="sales_details.php?user_id=<?= $user_id ?>" method="POST">
                        <div class="mb-3">
                            <label for="year" class="form-label">ปี</label>
                            <input type="number" class="form-control" name="year" required>
                        </div>
                        <div class="mb-3">
                            <label for="quarter" class="form-label">ไตรมาส</label>
                            <select class="form-select" name="quarter" required>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">ยอดขาย (บาท)</label>
                            <input type="number" class="form-control" name="amount" required>
                        </div>
                        <button type="submit" name="add_sale" class="btn btn-primary">บันทึกยอดขาย</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขยอดขาย -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSaleModalLabel">แก้ไขยอดขาย</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_sale.php" method="POST">
                        <input type="hidden" name="user_id" id="editUserId">
                        <input type="hidden" name="old_year" id="editOldYear">
                        <input type="hidden" name="old_quarter" id="editOldQuarter">

                        <div class="mb-3">
                            <label for="editYear" class="form-label">ปี</label>
                            <input type="number" class="form-control" name="new_year" id="editYear" required>
                        </div>
                        <div class="mb-3">
                            <label for="editQuarter" class="form-label">ไตรมาส</label>
                            <select class="form-select" name="new_quarter" id="editQuarter" required>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editAmount" class="form-label">ยอดขาย (บาท)</label>
                            <input type="number" class="form-control" name="new_amount" id="editAmount" required>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- สคริปต์แสดงผลกราฟ -->
    <script>
    // เมื่อเปิด modal แก้ไข
    const editSaleModal = document.getElementById('editSaleModal');
        editSaleModal.addEventListener('show.bs.modal', function(event) {
            // รับค่าจากปุ่มที่ถูกคลิก
            const button = event.relatedTarget; 
            const year = button.getAttribute('data-year');
            const quarter = button.getAttribute('data-quarter');
            const amount = button.getAttribute('data-amount');
            const userId = button.getAttribute('data-user-id');

            // กำหนดค่าให้กับฟอร์มใน modal
            document.getElementById('editYear').value = year;
            document.getElementById('editQuarter').value = quarter;
            document.getElementById('editAmount').value = amount;
            document.getElementById('editUserId').value = userId;
            document.getElementById('editOldYear').value = year;
            document.getElementById('editOldQuarter').value = quarter;
    });

    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesData = {
        labels: <?php 
            $labels = [];
            foreach ($sales_data as $year => $quarters) {
                foreach ($quarters as $quarter => $amount) {
                    $labels[] = "ปี $year Q$quarter";
                }
            }
            echo json_encode($labels);
        ?>,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: <?php 
                $data = [];
                foreach ($sales_data as $quarters) {
                    foreach ($quarters as $amount) {
                        $data[] = $amount;
                    }
                }
                echo json_encode($data);
            ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };

    var salesChart = new Chart(ctx, {
        type: 'bar',
        data: salesData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'ยอดขาย (บาท)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'ช่วงเวลา'
                    }
                }
            }
        }
    });
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#tabledata').DataTable({
            "language": {
                "sLengthMenu": "แสดง _MENU_ ข้อมูล",
                "sZeroRecords": "ไม่พบข้อมูล",
                "sInfo": "แสดง _START_ ถึง _END_ ของ _TOTAL_ ข้อมูล",
                "sInfoEmpty": "แสดง 0 ถึง 0 ของ 0 ข้อมูล",
                "sInfoFiltered": "(จากข้อมูลทั้งหมด _MAX_ ข้อมูล)",
                "sSearch": "ค้นหา :",
                "oPaginate": {
                    "sFirst": "หน้าแรก",
                    "sPrevious": "ก่อนหน้า",
                    "sNext": "ถัดไป",
                    "sLast": "หน้าสุดท้าย"
                }
            },
            "aaSorting": [[0, 'desc']]  // เรียงลำดับจากคอลัมน์แรก
        });
    });
</script>
</body>
</html>

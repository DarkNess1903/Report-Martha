<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลยอดขายเฉพาะของพนักงานขายที่ล็อกอิน
$user_id = $_SESSION['user_id'];

// ดึงยอดขายทั้งหมดในแต่ละปี
$sql_yearly_sales = "SELECT year, SUM(amount) AS total_sales FROM sales WHERE user_id = ? GROUP BY year ORDER BY year DESC";
$stmt_yearly_sales = $conn->prepare($sql_yearly_sales);
$stmt_yearly_sales->bind_param("i", $user_id);
$stmt_yearly_sales->execute();
$yearly_sales_result = $stmt_yearly_sales->get_result();

// ดึงยอดขายทั้งหมด
$sql_total_sales = "SELECT SUM(amount) AS total_sales FROM sales WHERE user_id = ?";
$stmt_total_sales = $conn->prepare($sql_total_sales);
$stmt_total_sales->bind_param("i", $user_id);
$stmt_total_sales->execute();
$total_sales_result = $stmt_total_sales->get_result();
$stmt_yearly_sales->close();
$stmt_total_sales->close();

// แปลงไตรมาสเป็นเดือน
$quarter_to_month = [
    '1' => 'ไตรมาส 1',
    '2' => 'ไตรมาส 2',
    '3' => 'ไตรมาส 3',
    '4' => 'ไตรมาส 4'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>ดูข้อมูลยอดขาย</title>

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
</head>
<body>
    <?php include 'topnavbar.php'; ?>
    <div class="container mt-5">
    <div class="col-md-12">
            <div class="card shadow-sm">
                 <div class="card-body">
        <h2 class="mb-4">ข้อมูลยอดขายของคุณ</h2>

        <!-- ยอดขายรวมทั้งหมด -->
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">ยอดขายรวมทั้งหมด:</h4>
            <span><?= number_format($total_sales_result->fetch_assoc()['total_sales'], 2) ?> บาท</span>
        </div>
        
        <!-- ตารางยอดขายตามปี -->
        <div class="table-responsive">
            <table id="tabledata" class="table table-striped table-bordered">
                <thead style="font-size: small;">
                    <tr>
                        <th>ปี</th>
                        <th>ยอดขายรวม (บาท)</th>
                        <th>ดูข้อมูล</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($yearly_sales_result->num_rows > 0): ?>
                        <?php while ($row = $yearly_sales_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['year']) ?></td>
                                <td><?= number_format($row['total_sales'], 2) ?> บาท</td>
                                <td>
                                    <a href="sales_details_by_year.php?year=<?= $row['year'] ?>" class="btn btn-sm btn-info">
                                        ดูข้อมูล
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">ไม่มีข้อมูลยอดขาย</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
            </div>
                </div>
                    </div></br>

        <!-- ข้อมูลยอดขายตามไตรมาส -->
        <div class="col-md-12">
            <div class="card shadow-sm">
                 <div class="card-body">
        <h3 class="mb-3">ข้อมูลยอดขายตามไตรมาส (ปีและไตรมาส):</h3>
        <div class="table table-responsive">
            <table id= "tabledata1" class="table table-striped table-boredered">
                <thead style="font-size: small;">
                <tr>
                    <th>ปี</th>
                    <th>ไตรมาส</th>
                    <th>ยอดขาย (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงข้อมูลยอดขายตามปีและไตรมาส
                $sql_quarterly_sales = "SELECT year, quarter, SUM(amount) AS total_amount FROM sales WHERE user_id = ? GROUP BY year, quarter ORDER BY year DESC, quarter ASC";
                $stmt_quarterly_sales = $conn->prepare($sql_quarterly_sales);
                $stmt_quarterly_sales->bind_param("i", $user_id);
                $stmt_quarterly_sales->execute();
                $quarterly_sales_result = $stmt_quarterly_sales->get_result();
                $stmt_quarterly_sales->close();

                if ($quarterly_sales_result->num_rows > 0):
                    while ($row = $quarterly_sales_result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['year']) ?></td>
                        <td><?= $quarter_to_month[$row['quarter']] ?></td>
                        <td><?= number_format($row['total_amount'], 2) ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" class="text-center">ไม่มีข้อมูลยอดขายตามไตรมาส</td>
                    </tr>
                <?php endif; ?>
            </tbody>
          </table>
        </div>
            </div>
            </div>
                </div></br>
        <a href="employee_dashboard.php" class="btn btn-secondary">กลับไปยังหน้าหลัก</a>
                </br></br>
        </div>

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
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
        $('#tabledata1').dataTable( {
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

<?php
session_start();
include 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// แปลงเดือนเป็นชื่อภาษาไทย
$thai_months = [
    "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

// ดึงข้อมูลยอดขายรายเดือนตามปี
$sql = "SELECT year, month, product, SUM(amount) AS total_sales 
        FROM sales 
        WHERE user_id = ? AND year = ?
        GROUP BY month, product 
        ORDER BY month ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $year);
$stmt->execute();
$result = $stmt->get_result();

$sales_data = [];
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


 <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <!-- ลิงค์ของตาราง -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">


    <!-- Template Main CSS File -->
    <link href="//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- จบลิงค์ของตาราง -->
</head>
<body>
<!-- Include Top Navbar -->
<?php include 'topnavbar.php'; ?>

 <!-- ตารางแสดงข้อมูลพนักงาน -->
<div class="container mt-4">
    <h3>ยอดขายของคุณในปี <?= $year ?></h3>
    <?php if (!empty($sales_data)): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-body">
              <div class="table table-responsive">
                <table id= "tabledata" class="table table-striped table-boredered">
                    <thead style="font-size: small;">
                            <tr>
                                <th>ปี</th>
                                <th>เดือน</th>
                                <th>สินค้า</th>
                                <th>ยอดขายรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_data as $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($data['year']) ?></td>
                                    <td><?= $thai_months[intval($data['month'])] ?></td>
                                    <td><?= htmlspecialchars($data['product']) ?></td>
                                    <td><?= number_format($data['total_sales'], 2) ?> บาท</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p class="text-muted">ยังไม่มีข้อมูลยอดขายในปีนี้</p>
    <?php endif; ?>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

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

</body>
</html>

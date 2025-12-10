<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// ✅ สร้างรายการปีเอง (เปลี่ยนตามต้องการ)
$currentYear = date("Y");
$startYear = 2022; // ปีเริ่มต้น
$endYear = $currentYear + 2; // ปีอนาคต เช่น ล่วงหน้า 2 ปี

$years = range($endYear, $startYear); // เรียงจากมากไปน้อย

// รับค่าปีที่เลือกจาก GET หรือใช้ปีปัจจุบัน
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;

// ดึงยอดขายพนักงานในปีที่เลือก
$sql = "SELECT users.id, users.username, 
               COALESCE(SUM(sales.amount), 0) AS total_sales 
        FROM users 
        LEFT JOIN sales ON users.id = sales.user_id 
            AND sales.year = $selected_year
        WHERE users.role = 'sales'
        GROUP BY users.id, users.username";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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

    <title>จัดการข้อมูลยอดขาย</title>
</head>
<body>
    <?php include 'topnavbar.php'; ?>
    <div class="container mt-5">
        <h2>จัดการข้อมูลยอดขาย</h2><br>
         <div class="col-md-12">
            <div class="card shadow-sm">
                 <div class="card-body">

      <form method="GET" class="mb-3">
        <label for="year">เลือกปี:</label>
        <select name="year" id="year" onchange="this.form.submit()" class="form-select w-auto d-inline-block">
            <?php foreach ($years as $year): ?>
                <option value="<?= $year ?>" <?= ($year == $selected_year) ? 'selected' : '' ?>>
                    <?= $year ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

        <!-- ตารางแสดงข้อมูลพนักงาน -->
        <div class="table table-responsive">
            <table id= "tabledata" class="table table-striped table-boredered">
                <thead style="font-size: small;">
                    <tr>
                        <th>ลำดับ</th>
                        <th>พนักงานขาย</th>
                        <th>ยอดขายรวม (บาท) ปี <?= $selected_year ?></th>
                        <th>ดูข้อมูลยอดขาย</th>
                    </tr>
    </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= number_format($row['total_sales'], 2) ?> บาท</td>
                        <td>
                           <a href="sales_details.php?user_id=<?= $row['id'] ?>&year=<?= $selected_year ?>" class="btn btn-info">ดูข้อมูล</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">ไม่มีข้อมูลพนักงานขาย</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
     </div>
         </div>
             </div> <br><br>
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>บริษัท มาร์ธา กรุ๊ปจำกัด</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <!-- Favicons -->
  <link href="assets/img/ma2.png" rel="icon">
  <link href="assets/img/ma2.png" rel="apple-touch-icon">

</head>



<!-- เนื้อหา -->

<main id="main" class="main">

    <!-- <div class="rounded h-100 p-4" style="background: #ebf7fa;">
      <div class="floating mt-12"> -->

      <div class="pagetitle">
      <h1>ข้อมูลยอดขายพนักงาน</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item active"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active"><a href="add-sales.php">Add sales</a></li>
          <li class="breadcrumb-item active"><a href="tables-sales.php">Refresh</a></li>

        </ol>
      </nav>
    
    </div><!-- End Page Title -->

<section class="section">
    <div class="row">
      <div class="col-lg-12">

      <?php
        include "connect_db.php";

        $sql = "select * from sales 
        INNER JOIN employee on sales.employee_id = employee.employee_id";
        $result = mysqli_query($conn,$sql);
      ?>

        <div class="card">
          <div class="card-body"> 

            <a href="add-sales.php"><br> 
              <button type="button" class="btn btn-primary mb-3" >เพิ่มข้อมูล</button>
            </a>  
            
            <!-- ข้อมูลหัวตาราง-->
            <div class="table table-responsive">
            <table id= "tabledata" class="table table-striped table-boredered">
                      <thead>
                  <tr style="font-size: small;">
                      <th style="text-align: center;">ลำดับ</th>
                      <th style="text-align: center;">ชื่อพนักงาน</th>
                      <th style="text-align: center;">ปี</th>
                      <th style="text-align: center;">ยอดรวม</th>
                      <th style="text-align: center;">รายละเอียด</th>
                      <th style="text-align: center;"> จัดการ</th>
                  </tr>
              </thead>
              <tbody>
                  <?php
           
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>"; 
                      echo "<td style='text-align: center;'>" . $row["sales_id"] . "</td>"; // ชื่อพนักงาน
                      echo "<td style='text-align: center;'>" . $row["employee_name"] . "</td>"; // ชื่อพนักงาน
                      echo "<td style='text-align: center;'>" . $row["year_id"] . "</td>"; // ปี
                      echo "<td style='text-align: right;'>" . number_format($row["sumyear"], 2) . "</td>"; // ยอดรวม 12 เดือน
                    
                      // ลิงก์ไปยังหน้ารายละเอียดพนักงาน
                      echo "<td style='text-align: center;'> <a href='detail-sales.php?sales_id=" . $row['sales_id'] . "' title='รายละเอียด'><i class='bi bi-folder2-open fa-lg' style='font-size:24px;color:MediumSeaGreen' aria-hidden='true'></i></a></td>";
                      // ลิงก์ไปยังหน้าแก้ไขและลบข้อมูลพนักงาน
                      echo "<td style='text-align: center;'>";
                      echo "<a href='Update-sales.php?sales_id=" . $row['sales_id'] . "' title='แก้ไข'><i class='bi bi-pencil-square fa-lg' style='font-size:24px;color:Orange; margin-right:15px;' aria-hidden='true'></i></a>";
                      echo "<a href='Delete-sales.php?sales_id=" . $row['sales_id'] . "' title='ลบ' onClick=\"return confirm('ต้องการลบข้อมูลหรือไม่')\"><i class='bi bi-trash fa-lg' style='font-size:24px;color:red;' aria-hidden='true'></i></a>";
                      echo "</td>";
                      echo "</tr>"; // ปิดแท็ก <tr>
                  }
                  ?>
              </tbody>
          </table>
            <!-- จบข้อมูลในตาราง -->

            </div>
          </div>
        </div>
      
    </div>
</section>

</main><!-- End #main -->
<!-- จบเนื้อหา -->


<?php include 'navbar.php'; ?> 



</html>
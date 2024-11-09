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
      <h1>ข้อมูลพนักงาน</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item active"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active"><a href="add-year.php">Add year</a></li>
          <li class="breadcrumb-item active"><a href="tables-year.php">Refresh</a></li>

        </ol>
      </nav>
    
    </div><!-- End Page Title -->

<section class="section">
    <div class="row">
      <div class="col-lg-6">

            <?php
                include "connect_db.php";
                $sql = "select * from year";
                $result = mysqli_query($conn,$sql);
            ?>

        <div class="card">
          <div class="card-body"> 

            <a href="add-year.php"><br> 
              <button type="button" class="btn btn-primary mb-3" >เพิ่มข้อมูล</button>
            </a>  
            
            <!-- ข้อมูลหัวตาราง-->
            <div class="table table-responsive">
            <table id= "tabledata" class="table table-striped table-boredered">
                                <thead>
                                  <tr style="font-size: small;">
                                      <th>ลำดับ</th>
                                      <th>ปี</th>
                                      <th>จัดการ</th>
                                  </tr>
                                </thead>
                <tbody>
                          <?php
                            $counter = 1; // ตัวแปรเริ่มต้นของลำดับอัติโนมัติ
                            if ($result->num_rows > 0) {
                              while($row = $result->fetch_assoc()) {
                                  echo "<tr style='font-size: small;'>";
                                  echo "<td>".$counter."</td>"; // แสดงลำดับอัติโนมัติ
                                  echo "<td>".$row["year_id"]."</td>";
                                  echo "<td class=''>";
                                  echo "<a href='Delete-year.php?year_id=".$row['year_id']."' title='ลบ' onClick=\"return window.confirm('ต้องการลบข้อมูลหรือไม่')\"><i class='bi bi-trash fa-lg' style='font-size:24px;color:red;' aria-hidden='true'></i></a></td>";
                                  echo "</tr>";
                                  
                                  $counter++; // เพิ่มลำดับขึ้นทีละ 1
                              }
                            } else {
                              echo "<tr><td colspan='7'>ไม่พบข้อมูล</td></tr>";
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
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

<!-- เนื้อหาใน pages -->
<main id="main" class="main">
<div class="pagetitle">
      <h1>เพิ่มปี</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item active"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active"><a href="tables-year.php">tables year</a></li>
          <li class="breadcrumb-item active"><a href="add-year.php">Refresh</a></li>

        </ol>
      </nav>
    
    </div><!-- End Page Title -->
       
   <section class="section">
      <div class="row">
      <div class="col-lg-6">
          <?php
                include "connect_db.php";
                $sql1 = "select * from year order by year_id";
                $result1 = mysqli_query($conn,$sql1)
          ?>

        <div class="card">
        <div class="card-body"><br>
             <form action="insert_year.php" method="post">

                <div class="row">
                  <label for="inputyear_id" class="col-sm-2 col-form-label">ปี</label>
                    <div class="col-sm-3">
                      <input type="text" placeholder="กรอก พ.ศ. ปี" id="year_id" name="year_id" class="form-control">
                    </div>
                </div><br>                

                <div class="col-12 mb-6">
                <div class="col-sm-10">
                   <button type="submit" class="btn btn-success" href = "tables-general.php">ตกลง</button>
                   <button type="submit" class="btn btn-danger" href = "tables-employee.php">ยกเลิก</button>
                </div><br>
                </div>

             </form><!-- End General Form Elements -->
           </div>
         </div>

         </div>
         </div>
       </div>
     </div>
   </section>
</main><!-- End #main -->

  <?php include 'navbar.php'; ?> 


</html>
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
      <h1>กรอกข้อมูลยอดขาย</h1>
      <nav>
        <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active"><a href="tables-sales.php">tables sales</a></li>
        <li class="breadcrumb-item active"><a href="add-sales.php">Refresh</a></li>
        </ol>
      </nav>
</div><!-- End Page Title -->
       
   <section class="section">
      <div class="row">
      <div class="col-lg-12">
          <?php
                include "connect_db.php";
                $sql1 = "select * from year order by year_id";
                $result1 = mysqli_query($conn,$sql1);

                $sql2 = "select * from employee  order by employee_id ";
                $result2 = mysqli_query($conn,$sql2);

          ?>

        <div class="card">
        <div class="card-body"><br>
             <form action="insert_sales.php" method="post">

             <div class="row">
               <label for="inputsales_id" class="col-sm-2 col-form-label">ลำดับ</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="** ห้ามกรอก ลำดับ ซ้ำ **" id="sales_id" name="sales_id" class="form-control">
                  </div> 
               </div><br>

               <div class="row">
               <label for="inputyear_id" class="col-sm-2 col-form-control">ปี:</label>
                      <div class="col-sm-3">
                        <select class="form-control " id="year_id" name="year_id" >
                        <option>เลือกปี</option>
                                <?php
                                    while($row = $result1->fetch_assoc()){
                                  ?>
                                    <option value="<?=$row['year_id'];?>"><?=$row['year_id'];?></option>
                                <?php } ?>
                        </select>
                  </div>
               </div><br>

               <div class="row">
               <label for="inputemployee_id " class="col-sm-2 col-form-control">ชื่อพนักงาน:</label>
                      <div class="col-sm-3">
                        <select class="form-control " id="employee_id" name="employee_id" >
                        <option>เลือกชื่อพนักงาน</option>
                                <?php
                                    while($row = $result2->fetch_assoc()){
                                  ?>
                                    <option value="<?=$row['employee_id'];?>"><?=$row['employee_name'];?></option>
                                <?php } ?>
                        </select>
                  </div>
               </div><br>

               <div class="row">
               <label for="inputjan" class="col-sm-2 col-form-label">มกราคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนมกราคม" id="่jan" name="jan" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputfeb" class="col-sm-2 col-form-label">กุมภาพันธ์</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนกุมภาพันธ์" id="feb" name="feb" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputmar" class="col-sm-2 col-form-label">มีนาคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนมีนาคม" id="mar" name="mar" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputapr" class="col-sm-2 col-form-label">เมษายน</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนเมษายน" id="apr" name="apr" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputmay" class="col-sm-2 col-form-label">พฤษภาคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนพฤษภาคม" id="may" name="may" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputjun" class="col-sm-2 col-form-label">มิถุนายน</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนมิถุนายน" id="jun" name="jun" class="form-control">
                  </div>
               </div><br>

              <div class="row">
               <label for="inputjul" class="col-sm-2 col-form-label">กรกฎาคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนกรกฎาคม" id="jul" name="jul" class="form-control">
                  </div>
               </div><br>

                <div class="row">
               <label for="inputaug" class="col-sm-2 col-form-label">สิงหาคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนสิงหาคม" id="aug" name="aug" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputsept" class="col-sm-2 col-form-label">กันยายน</label>
                  <div class="col-sm-4">
                  <input type="text" placeholder="ยอดเดือนกันยายน" id="sept" name="sept" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputoct" class="col-sm-2 col-form-label">ตุลาคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนตุลาคม" id="oct" name="oct" class="form-control">
                  </div>
               </div><br>
       
               <div class="row">
               <label for="inputnov" class="col-sm-2 col-form-label">พฤศจิกายน</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนพฤศจิกายน" id="nov" name="nov" class="form-control">
                  </div>
               </div><br>

               <div class="row">
               <label for="inputdecember" class="col-sm-2 col-form-label">ธันวาคม</label>
                  <div class="col-sm-4">
                    <input type="text" placeholder="ยอดเดือนธันวาคม" id="december" name="december" class="form-control">
                  </div>
               </div><br>


               

                <div class="col-12 mb-6">
                <div class="col-sm-10">
                   <button type="submit" class="btn btn-success" href = "tables-general.php">ตกลง</button>
                   <button type="submit" class="btn btn-danger" href = "tables-employee.php">ยกเลิก</button>
                </div><br><br>
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
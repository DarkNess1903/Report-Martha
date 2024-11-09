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
      <h1>เพิ่มข้อมูลพนักงาน</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item active"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active"><a href="tables-employee.php">tables employee</a></li>
          <li class="breadcrumb-item active"><a href="add-employee.php">Refresh</a></li>

        </ol>
      </nav>
    
    </div><!-- End Page Title -->
       
   <section class="section">
      <div class="row">
      <div class="col-lg-12">
          <?php
                include "connect_db.php";
                $sql1 = "select * from position order by position_id";
                $result1 = mysqli_query($conn,$sql1)
          ?>

        <div class="card">
        <div class="card-body"><br>
             <form action="insert_employee.php" method="post">

                <div class="row">
                  <label for="inputemployee_id" class="col-sm-2 col-form-label">รหัสพนักงาน</label>
                    <div class="col-sm-3">
                      <input type="text" placeholder="รหัสพนักงาน" id="employee_id" name="employee_id" class="form-control ">
                    </div>
                  <label for="inputemployee_name" class="col-sm-2 col-form-label">ชื่อพนักงาน</label>
                    <div class="col-sm-3">
                      <input type="text" placeholder="ชื่อพนักงาน" id="employee_name" name="employee_name" class="form-control">
                    </div>
                </div><br>

               <div class="row">
               <label for="inputemployee_phone" class="col-sm-2 col-form-label">เบอร์โทรศัพท์</label>
                  <div class="col-sm-3">
                    <input type="text" placeholder="เบอร์โทรศัพท์" id="employee_phone" name="employee_phone" class="form-control">
                  </div>
                  <label for="inputposition_id" class="col-sm-2 col-form-control">ตำแหน่ง:</label>
                      <div class="col-sm-3">
                        <select class="form-control " id="position_id" name="position_id" >
                        <option>เลือกตำแหน่ง</option>
                                <?php
                                    while($row = $result1->fetch_assoc()){
                                  ?>
                                    <option value="<?=$row['position_id'];?>"><?=$row['position_name'];?></option>
                                <?php } ?>
                        </select>
                  </div>
               </div><br>

                  <div class="row">
                    <label for="inputemployee_username" class="col-sm-2 col-form-label">ชื่อผู้ใช้งาน</label>
                      <div class="col-sm-3">
                        <input type="text" placeholder="ชื่อผู้ใช้งาน" id="employee_username" name="employee_username" class="form-control">
                      </div>              
                      <label for="inputemployee_password" class="col-sm-2 col-form-label">รหัสผ่าน</label>
                            <div class="col-sm-3">
                                <input type="password" placeholder="รหัสผ่าน" id="employee_password" name="employee_password" class="form-control">
                                <input type="checkbox" id="togglePassword" style="margin-left: 10px;"> Show Password
                            </div>

                            <script>
                                const passwordField = document.getElementById('employee_password');
                                const togglePassword = document.getElementById('togglePassword');
                                
                                togglePassword.addEventListener('change', function() {
                                    // Toggle the password field between text and password
                                    if (this.checked) {
                                        passwordField.type = 'text';
                                    } else {
                                        passwordField.type = 'password';
                                    }
                                });
                            </script>
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
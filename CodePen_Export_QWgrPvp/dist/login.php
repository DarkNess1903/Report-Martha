<?php
    session_start();
    session_destroy();
?>

<!DOCTYPE html>
<html lang="en">

  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>login</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Favicons -->
  <link href="assets/img/02.png" rel="icon">
  <link href="assets/img/02.png" rel="apple-touch-icon">

  <!-- ไลบารี่ไอคอน -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css'><link rel="stylesheet" href="style1.css">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
  

  <!-- =======================================================
  * Template Name: NiceAdmin - v2.5.0
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>
      
  </header>

<body>
<!-- partial:index.partial.html -->
<div class="container">

	<div class="screen">
		<div class="screen__content">

		

			<form class="login" action="../../check_login.php" class="login100-form validate-form" method="POST">

				<img src="../../assets/img/0907.png"style="width:300px;height:300px; position: fixed; margin-top:-180px; margin-left:200px;" >

			<div style="left: 20px;">
			<!--	<p style="width:605px; margin-left: 60px;  font-size: 24px;">ระบบจัดการข้อมูลคลังสินค้า<p>-->
				<!--<img src="assets/img/0907.png"> -->
			</div>

				<div class="login__field" style="left: 280px; bottom: -25px;">
					<i class="login__icon fas fa-user"></i>
					<input type="text" class="login__input"id="username" name="username" placeholder="ชื่อผู้ใช้" style=" font-family: 'Anuphan', sans-serif; font-size: 18px;">
				</div>

				<div class="login__field" style="left: 280px; bottom: -25px;">
					<i class="login__icon fas fa-lock"></i>
					<input type="password" class="login__input" id="password" name="password" placeholder="รหัสผ่าน " style=" font-family: 'Anuphan', sans-serif; font-size: 18px;">
				</div>

				<div style="margin-right: 50px;">
				<button class="button login__submit">
					<span class="button__text" style=" font-family: 'Anuphan', sans-serif; font-size: 18px;">เข้าสู่ระบบ</span>
					<i class="button__icon fas fa-chevron-right"></i>
				</button>	
				</div>
							
			</form>
		<!--	<div class="social-login">
				<h3>log in via</h3>
				<div class="social-icons">
					<a href="#" class="social-login__icon fab fa-instagram"></a>
					<a href="#" class="social-login__icon fab fa-facebook"></a>
					<a href="#" class="social-login__icon fab fa-twitter"></a>
				</div>
			</div>-->
		</div>
	
		<div class="screen__background">
			<span class="screen__background__shape screen__background__shape4"></span>
			<span class="screen__background__shape screen__background__shape3"></span>		
			<span class="screen__background__shape screen__background__shape2"></span>
			<span class="screen__background__shape screen__background__shape1"></span>
		</div>		
	</div>
</div>
<!-- partial -->
  
</body>
</html>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title></title>
		<!-- sweet alert js & css -->
		<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
	</head>

<?php
 include "connect_db.php";

 $employee_id = $_GET['employee_id'];
 $sql = "delete from employee where employee_id = '$employee_id'";
 mysqli_query($conn,$sql);

 $sql1 = "delete from login where employee_id = '$employee_id '";
 mysqli_query($conn,$sql1);

 echo $sql;
?>
<body>
		<script>
		setTimeout(function() {
			swal({
					title: "ลบข้อมูลเรียบร้อย", //ข้อความ เปลี่ยนได้ เช่น บันทึกข้อมูลสำเร็จ!!
					text: "กลับหน้าหลัก", //ข้อความเปลี่ยนได้ตามการใช้งาน
					type: "success", //success, warning, danger
					timer: 3000, //ระยะเวลา redirect 3000 = 3 วิ เพิ่มลดได้
					showConfirmButton: false //ปิดการแสดงปุ่มคอนเฟิร์ม ถ้าแก้เป็น true จะแสดงปุ่ม ok ให้คลิกเหมือนเดิม
				}, function(){
					window.location.href = "tables-employee.php"; //หน้าเพจที่เราต้องการให้ redirect ไป อาจใส่เป็นชื่อไฟล์ภายในโปรเจคเราก็ได้ครับ เช่น admin.php
					});
			});
			
		</script>

	</body>
</html>
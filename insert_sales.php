<?php
include "connect_db.php";

// รับค่าจาก POST
$sales_id = $_POST['sales_id'];
$year_id = $_POST['year_id'];   
$employee_id = $_POST['employee_id'];
$jan = $_POST['jan'];
$feb = $_POST['feb'];
$mar = $_POST['mar'];
$apr = $_POST['apr'];
$may = $_POST['may'];
$jun = $_POST['jun']; 
$jul = $_POST['jul'];
$aug = $_POST['aug'];
$sept = $_POST['sept'];
$oct = $_POST['oct'];
$nov = $_POST['nov'];
$december = $_POST['december'];



// สร้าง SQL query
$sql = "INSERT INTO sales(sales_id, year_id, employee_id, jan, feb, mar, apr, may, jun, jul, aug, sept, oct, nov, december, quarter1, quarter2, quarter3, quarter4, sumyear)
VALUES ('$sales_id', '$year_id', '$employee_id', '$jan', '$feb', '$mar', '$apr', '$may', '$jun', '$jul', '$aug', '$sept', '$oct', '$nov', '$december', '$quarter1', '$quarter2', '$quarter3', '$quarter4', '$sumyear')";

// รัน query
mysqli_query($conn, $sql);

// แสดง SQL query (ใช้สำหรับการตรวจสอบ)
echo $sql;
?>
<meta http-equiv="refresh" content="0; url=tables-sales.php">

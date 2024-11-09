<?php
include "connect_db.php";

$cut_id = $_POST['cut_id'];
$cut_num = $_POST['cut_num'];
$program = $_POST['program'];
$date_off = $_POST['date_off'];
$brand_id = $_POST['brand_id'];
$pname = $_POST['pname'];
$number = $_POST['number'];
$unit_name = $_POST['unit_name'];
$pay_id = $_POST['pay_id'];
$note = $_POST['note'];

$sql = "UPDATE cut_stock SET cut_num ='$cut_num' 
,program='$program'
,date_off='$date_off'
 ,brand_id='$brand_id'
 ,pname='$pname'
 ,unit_name='$unit_name'
 ,pay_id='$pay_id'
 ,note='$note'
WHERE cut_id = '$cut_id'";
mysqli_query($conn,$sql);

   
//----------------------------------------------------------------------[เงื่อนไขการบวกและการลบ]
$sql1 = "SELECT * FROM `product` WHERE pname = '$pname'";
$result1 =  mysqli_query($conn,$sql1);
$row1 = mysqli_fetch_assoc($result1);

$sql2 = "SELECT * FROM `cut_stock` WHERE pname = '$pname' AND cut_id = '$cut_id'";
$result2 =  mysqli_query($conn,$sql2);
$row2 = mysqli_fetch_assoc($result2);

$product_total = $row1['pnumber'];
$amount_database = $row2['number'];

//------------------------------------------------------------[จำนวนสินค้าใหม่ > จำนวนสินค้าเก่า]
if($number > $amount_database) {
    $value = $product_total - ($number - $amount_database);

    $update1 = "UPDATE `product` SET `pnumber`='$value' WHERE pname = '$pname'";
    $update2 = "UPDATE `cut_stock` SET `number`='$number' WHERE pname = '$pname' AND cut_id = '$cut_id'";
    mysqli_query($conn,$update1);
    mysqli_query($conn,$update2);

    header("location: charts-chartjs.php");
}

//------------------------------------------------------------[จำนวนสินค้าใหม่ < จำนวนสินค้าเก่า]
if($number < $amount_database) {
    $value = $product_total + ($amount_database - $number);

    $update1 = "UPDATE `product` SET `pnumber`='$value' WHERE pname = '$pname'";
    $update2 = "UPDATE `cut_stock` SET `number`='$number' WHERE pname = '$pname' AND cut_id = '$cut_id'";
    mysqli_query($conn,$update1);
    mysqli_query($conn,$update2);

    header("location:charts-chartjs.php");
}

//------------------------------------------------------------[จำนวนสินค้าใหม่ = จำนวนสินค้าเก่า]
if($number
 == $amount_database) {
    $value = $product_total;

    $update1 = "UPDATE `product` SET `pnumber`='$value' WHERE pname = '$pname'";
    $update2 = "UPDATE `cut_stock` SET `number`='$number' WHERE pname = '$pname' AND cut_id = '$cut_id'";
    mysqli_query($conn,$update1);
    mysqli_query($conn,$update2);

    header("location: charts-chartjs.php");
}

  




mysqli_close($conn);
    
?>
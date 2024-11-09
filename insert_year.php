<?php

include "connect_db.php";
$year_id = $_POST['year_id'];  

$sql = "insert into year(year_id)
value ('$year_id')";

mysqli_query($conn,$sql);
echo $sql;

?>
<meta http-equiv="refresh" content="0; url=tables-year.php">

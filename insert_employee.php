        <?php

            include "connect_db.php";
            $employee_id = $_POST['employee_id'];
            $employee_name = $_POST['employee_name'];   
            $employee_phone = $_POST['employee_phone'];
            $position_id = $_POST['position_id'];
            $employee_username = $_POST['employee_username'];
            $employee_password = $_POST['employee_password'];

            $sql = "insert into employee(employee_id,employee_name,employee_phone,position_id,employee_username,employee_password)
            value ('$employee_id','$employee_name','$employee_phone','$position_id','$employee_username','$employee_password')";

            mysqli_query($conn,$sql);
            echo $sql;

            $sql1 = "insert into login(position_id,employee_username,employee_password,employee_id)
            value ('$position_id','$employee_username','$employee_password','$employee_id')";
            mysqli_query($conn,$sql1);

        ?>
        <meta http-equiv="refresh" content="0; url=tables-employee.php">

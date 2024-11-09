<?php
    session_start();
    session_destroy();
    header("Location:CodePen_Export_QWgrPvp/dist/login.php");
    //unset($_SESSION["uname]);
    //unset($_SESSION["umemberName]);
?>
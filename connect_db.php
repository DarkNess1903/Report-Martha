<?php

    $conn = new mysqli("localhost", "root", "", "salesgraph");
    if ($conn->connect_error){
            die("Connection failed: " . $conn->connect_error);
    }

?>
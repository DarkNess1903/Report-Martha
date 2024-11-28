<?php
// เชื่อมต่อกับฐานข้อมูล
include 'db_connection.php'; // แทนที่ด้วยไฟล์ที่ใช้เชื่อมต่อฐานข้อมูลของคุณ

// ตรวจสอบว่าได้ส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $user_id = $_POST['user_id'];
    $year = $_POST['year'];
    $quarter = $_POST['quarter'];

    // ตรวจสอบค่าที่รับมา
    if (!empty($user_id) && !empty($year) && !empty($quarter)) {
        // เตรียมคำสั่ง SQL สำหรับลบยอดขาย
        $sql = "DELETE FROM sales WHERE user_id = ? AND year = ? AND quarter = ?";

        // เตรียมคำสั่ง SQL
        if ($stmt = $conn->prepare($sql)) {
            // ผูกค่าพารามิเตอร์
            $stmt->bind_param('iis', $user_id, $year, $quarter);

            // ดำเนินการลบ
            if ($stmt->execute()) {
                // ถ้าลบสำเร็จ เปลี่ยนเส้นทางไปที่หน้าข้อมูลยอดขาย
                header("Location: sales_details.php?user_id=$user_id");
                exit();
            } else {
                // ถ้าลบไม่สำเร็จ
                echo "ไม่สามารถลบข้อมูลได้!";
            }

            // ปิดการเชื่อมต่อ
            $stmt->close();
        } else {
            echo "ไม่สามารถเตรียมคำสั่ง SQL ได้!";
        }
    } else {
        echo "ข้อมูลที่ส่งมาครบถ้วนหรือไม่!";
    }

    // ปิดการเชื่อมต่อฐานข้อมูล
    $conn->close();
}
?>

<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $old_year = $_POST['old_year'];
    $old_quarter = $_POST['old_quarter'];
    $new_year = $_POST['new_year'];
    $new_quarter = $_POST['new_quarter'];
    $new_amount = $_POST['new_amount'];

    // อัปเดตข้อมูลยอดขายในฐานข้อมูล
    $stmt = $conn->prepare("UPDATE sales SET year = ?, quarter = ?, amount = ? WHERE user_id = ? AND year = ? AND quarter = ?");
    $stmt->bind_param("iiisis", $new_year, $new_quarter, $new_amount, $user_id, $old_year, $old_quarter);

    if ($stmt->execute()) {
        $success_message = "ข้อมูลยอดขายถูกอัปเดตสำเร็จ";
        // รีเฟรชหน้าเพื่อแสดงข้อมูลล่าสุด
        header('Location: sales_details.php?user_id=' . $user_id);
        exit();
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $stmt->error;
    }

    $stmt->close();
}
?>

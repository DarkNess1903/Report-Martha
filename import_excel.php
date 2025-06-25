<?php
session_start();
include 'db.php';

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $user_id = intval($_POST['user_id']);
    $year = intval($_POST['year']);

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        die('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
    }

    $fileTmpPath = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($fileTmpPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $countInserted = 0;

        // เริ่มวนอ่านข้อมูลจากแถวที่ 2 (ข้ามหัวตาราง)
        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $month = intval($row[0]);
            $quarter = intval($row[1]);
            $product = trim($row[2]);
            $amount = floatval($row[3]);

            if ($month < 1 || $month > 12 || $quarter < 1 || $quarter > 4 || $product === '' || $amount <= 0) {
                // ข้ามแถวข้อมูลที่ไม่ถูกต้อง
                continue;
            }

            // ตรวจสอบข้อมูลซ้ำ (ถ้าต้องการ) - ตัวอย่างเช็คก่อน insert
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM sales WHERE user_id=? AND year=? AND month=? AND quarter=? AND product=?");
            $stmtCheck->bind_param("iiiss", $user_id, $year, $month, $quarter, $product);
            $stmtCheck->execute();
            $stmtCheck->bind_result($count);
            $stmtCheck->fetch();
            $stmtCheck->close();

            if ($count == 0) {
                $stmtInsert = $conn->prepare("INSERT INTO sales (user_id, year, month, quarter, product, amount) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtInsert->bind_param("iiiisd", $user_id, $year, $month, $quarter, $product, $amount);
                $stmtInsert->execute();
                $stmtInsert->close();
                $countInserted++;
            }
        }

        // ปิดการเชื่อมต่อฐานข้อมูล
        $conn->close();

        // ส่งกลับพร้อมพารามิเตอร์แจ้งผล
        header("Location: sales_details.php?user_id=$user_id&year=$year&imported=$countInserted");
        exit();

    } catch (Exception $e) {
        die('เกิดข้อผิดพลาดในการอ่านไฟล์ Excel: ' . $e->getMessage());
    }
} else {
    header('Location: sales_details.php');
    exit();
}
?>

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

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // ข้ามหัวตาราง

            $month = intval($row[0]);
            $product = strtoupper(trim($row[1]));
            $amount = floatval($row[2]);

            // ตรวจสอบข้อมูลเบื้องต้น
            if ($month < 1 || $month > 12 || $product === '' || $amount <= 0 || $year < 2000) {
                continue;
            }

            // คำนวณไตรมาสจากเดือน
            $quarter = ceil($month / 3);

            // ตรวจสอบข้อมูลซ้ำ
            $stmtCheck = $conn->prepare("
                SELECT COUNT(*) FROM sales 
                WHERE user_id = ? AND year = ? AND month = ? AND quarter = ? AND UPPER(product) = UPPER(?)
            ");
            $stmtCheck->bind_param("iiiss", $user_id, $year, $month, $quarter, $product);
            $stmtCheck->execute();
            $stmtCheck->bind_result($count);
            $stmtCheck->fetch();
            $stmtCheck->close();

            // ถ้าไม่ซ้ำ ให้เพิ่มข้อมูล
            if ($count == 0) {
                $stmtInsert = $conn->prepare("
                    INSERT INTO sales (user_id, year, month, quarter, product, amount) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->bind_param("iiiisd", $user_id, $year, $month, $quarter, $product, $amount);
                $stmtInsert->execute();
                $stmtInsert->close();
                $countInserted++;
            }
        }

        $conn->close();
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

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
            if ($index === 0) continue;

            $month = intval($row[0]);
            $product = strtoupper(preg_replace('/\s+/', ' ', trim($row[1])));
            $amount = floatval($row[2]);

            if ($month < 1 || $month > 12 || $product === '' || $amount <= 0 || $year < 2000) {
                continue;
            }

            $quarter = ceil($month / 3);
            
            $stmtInsert = $conn->prepare("INSERT INTO sales (user_id, year, month, quarter, product, amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("iiiisd", $user_id, $year, $month, $quarter, $product, $amount);
            $stmtInsert->execute();
            $stmtInsert->close();

            $countInserted++;
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
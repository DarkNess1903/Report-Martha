<?php
session_start();
include 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sales') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// แปลงเดือนเป็นชื่อภาษาไทย
$thai_months = [
    "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

// ดึงข้อมูลยอดขายรายเดือนตามปี
$sql = "SELECT year, month, product, SUM(amount) AS total_sales 
        FROM sales 
        WHERE user_id = ? AND year = ?
        GROUP BY month, product 
        ORDER BY month ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $year);
$stmt->execute();
$result = $stmt->get_result();

$sales_data = [];
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<!-- Include Top Navbar -->
<?php include 'topnavbar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">แดชบอร์ดยอดขายย้อนหลัง</h2>

<!-- ส่วนแสดงผล -->
<div class="container mt-4">
    <h3>ยอดขายของคุณในปี <?= $year ?></h3>
    <?php if (!empty($sales_data)): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead style="font-size: small;">
                            <tr>
                                <th>ปี</th>
                                <th>เดือน</th>
                                <th>ยอดขายรวม (บาท)</th>
                                <th>สินค้า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_data as $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($data['year']) ?></td>
                                    <td><?= $thai_months[intval($data['month'])] ?></td>
                                    <td><?= number_format($data['total_sales'], 2) ?> บาท</td>
                                    <td><?= htmlspecialchars($data['product']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p class="text-muted">ยังไม่มีข้อมูลยอดขายในปีนี้</p>
    <?php endif; ?>
</div>

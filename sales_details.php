<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// รับค่า user_id จาก URL
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

// ดึงข้อมูลยอดขายของพนักงาน
$sql = "SELECT sales.year, sales.quarter, SUM(sales.amount) AS total_sales 
        FROM sales 
        WHERE sales.user_id = ? 
        GROUP BY sales.year, sales.quarter
        ORDER BY sales.year ASC, sales.quarter ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $sales_data = [];
    while ($row = $result->fetch_assoc()) {
        $sales_data[$row['year']][$row['quarter']] = $row['total_sales'];
    }
} else {
    $sales_data = [];
}

// เพิ่มข้อมูลยอดขายใหม่
if (isset($_POST['add_sale'])) {
    $year = $_POST['year'];
    $quarter = $_POST['quarter'];
    $amount = $_POST['amount'];

    // แทรกข้อมูลยอดขายใหม่
    $stmt = $conn->prepare("INSERT INTO sales (user_id, year, quarter, amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $year, $quarter, $amount);

    if ($stmt->execute()) {
        $success_message = "เพิ่มข้อมูลยอดขายสำเร็จ";
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>ข้อมูลยอดขายของพนักงาน</title>
</head>
<body>
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">ข้อมูลยอดขายของพนักงาน</h1>

        <div class="row g-4">
            <!-- กราฟ -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        กราฟแสดงยอดขาย
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ตาราง -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        ตารางยอดขาย
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>ปี</th>
                                    <th>ไตรมาส</th>
                                    <th>ยอดขาย (บาท)</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sales_data)): ?>
                                    <?php foreach ($sales_data as $year => $quarters): ?>
                                        <?php foreach ($quarters as $quarter => $amount): ?>
                                            <tr>
                                                <td><?= $year ?></td>
                                                <td><?= $quarter ?></td>
                                                <td><?= number_format($amount, 2) ?> บาท</td>
                                                <td>
                                                    <!-- ปุ่มแก้ไข -->
                                                    <button class="btn btn-warning btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editSaleModal" 
                                                            data-year="<?= $year ?>" 
                                                            data-quarter="<?= $quarter ?>" 
                                                            data-amount="<?= $amount ?>">
                                                        แก้ไข
                                                    </button>

                                                    <!-- ปุ่มลบ -->
                                                    <form action="delete_sale.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                                        <input type="hidden" name="year" value="<?= $year ?>">
                                                        <input type="hidden" name="quarter" value="<?= $quarter ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('คุณต้องการลบยอดขายนี้หรือไม่?')">
                                                            ลบ
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">ไม่มีข้อมูลยอดขายสำหรับพนักงานคนนี้</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- ปุ่มเพิ่มยอดขาย -->
                        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addSaleModal">เพิ่มยอดขาย</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ปุ่มกลับไปหน้าหลัก -->
        <div class="text-center mt-4">
            <a href="manage_sales.php" class="btn btn-secondary">กลับไปที่หน้าหลัก</a>
        </div>
    </div>

    <!-- Modal เพิ่มยอดขาย -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSaleModalLabel">เพิ่มยอดขายใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="sales_details.php?user_id=<?= $user_id ?>" method="POST">
                        <div class="mb-3">
                            <label for="year" class="form-label">ปี</label>
                            <input type="number" class="form-control" name="year" required>
                        </div>
                        <div class="mb-3">
                            <label for="quarter" class="form-label">ไตรมาส</label>
                            <select class="form-select" name="quarter" required>
                                <option value="1">มกราคม</option>
                                <option value="2">เมษายน</option>
                                <option value="3">กรกฎาคม</option>
                                <option value="4">ตุลาคม</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">ยอดขาย (บาท)</label>
                            <input type="number" class="form-control" name="amount" required>
                        </div>
                        <button type="submit" name="add_sale" class="btn btn-primary">บันทึกยอดขาย</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขยอดขาย -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSaleModalLabel">แก้ไขยอดขาย</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_sale.php" method="POST">
                        <input type="hidden" name="user_id" id="editUserId">
                        <input type="hidden" name="old_year" id="editOldYear">
                        <input type="hidden" name="old_quarter" id="editOldQuarter">

                        <div class="mb-3">
                            <label for="editYear" class="form-label">ปี</label>
                            <input type="number" class="form-control" name="new_year" id="editYear" required>
                        </div>
                        <div class="mb-3">
                            <label for="editQuarter" class="form-label">ไตรมาส</label>
                            <select class="form-select" name="new_quarter" id="editQuarter" required>
                                <option value="1">มกราคม</option>
                                <option value="2">เมษายน</option>
                                <option value="3">กรกฎาคม</option>
                                <option value="4">ตุลาคม</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editAmount" class="form-label">ยอดขาย (บาท)</label>
                            <input type="number" class="form-control" name="new_amount" id="editAmount" required>
                        </div>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- สคริปต์แสดงผลกราฟ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // ดึง Modal แก้ไขยอดขาย
        var editSaleModal = document.getElementById('editSaleModal');
        
        // เมื่อ Modal ถูกแสดง
        editSaleModal.addEventListener('show.bs.modal', function(event) {
            // ปุ่มที่กดเรียก Modal
            var button = event.relatedTarget;

            // ดึงค่าจาก data-* attributes
            var year = button.getAttribute('data-year');
            var quarter = button.getAttribute('data-quarter');
            var amount = button.getAttribute('data-amount');

            // เติมข้อมูลในฟอร์ม
            editSaleModal.querySelector('#editUserId').value = <?= $user_id ?>; // ID ของผู้ใช้งาน
            editSaleModal.querySelector('#editOldYear').value = year; // ปีเดิม
            editSaleModal.querySelector('#editOldQuarter').value = quarter; // ไตรมาสเดิม
            editSaleModal.querySelector('#editYear').value = year; // ปีใหม่
            editSaleModal.querySelector('#editQuarter').value = quarter; // ไตรมาสใหม่
            editSaleModal.querySelector('#editAmount').value = amount; // ยอดขายใหม่
        });
    });

        var ctx = document.getElementById('salesChart').getContext('2d');
        var salesData = {
            labels: <?php 
                $labels = [];
                foreach ($sales_data as $year => $quarters) {
                    foreach ($quarters as $quarter => $amount) {
                        $labels[] = "ปี $year Q$quarter";
                    }
                }
                echo json_encode($labels);
            ?>,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: <?php 
                    $data = [];
                    foreach ($sales_data as $quarters) {
                        foreach ($quarters as $amount) {
                            $data[] = $amount;
                        }
                    }
                    echo json_encode($data);
                ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };

        var salesChart = new Chart(ctx, {
            type: 'bar',
            data: salesData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'ยอดขาย (บาท)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'ช่วงเวลา'
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> 
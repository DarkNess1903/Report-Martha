<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// เพิ่มข้อมูลยอดขาย
if (isset($_POST['add_sale'])) {
    $user_id = $_POST['user_id'];
    $year = $_POST['year'];
    
    // แปลงค่าของ quarter เป็นตัวเลข (1-4)
    switch ($_POST['quarter']) {
        case 'Q1':
            $quarter = 1;
            break;
        case 'Q2':
            $quarter = 2;
            break;
        case 'Q3':
            $quarter = 3;
            break;
        case 'Q4':
            $quarter = 4;
            break;
        default:
            $error_message = "กรุณากรอกไตรมาสที่ถูกต้อง (Q1, Q2, Q3, Q4)";
            break;
    }

    $amount = $_POST['amount'];

    if (!isset($error_message)) {
        // ใช้การเตรียมคำสั่ง SQL (prepared statement) เพื่อป้องกัน SQL Injection
        $stmt = $conn->prepare("INSERT INTO sales (user_id, year, quarter, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $user_id, $year, $quarter, $amount);
    
        if ($stmt->execute()) {
            $success_message = "เพิ่มข้อมูลยอดขายสำเร็จ";
        } else {
            $error_message = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }
}


// ลบข้อมูลยอดขาย
if (isset($_GET['delete_sale'])) {
    $sale_id = $_GET['delete_sale'];

    // ใช้การเตรียมคำสั่ง SQL (prepared statement) เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param("i", $sale_id);

    if ($stmt->execute()) {
        $success_message = "ลบข้อมูลยอดขายสำเร็จ";
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    $stmt->close();
}

// แสดงข้อมูลยอดขายทั้งหมด
$sql = "SELECT sales.id, users.username, sales.year, sales.quarter, sales.amount FROM sales INNER JOIN users ON sales.user_id = users.id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>จัดการข้อมูลยอดขาย</title>
</head>
<body>
    <?php include 'topnavbar.php'; ?>

    <div class="container mt-5">
        <h1>จัดการข้อมูลยอดขาย</h1>

        <!-- ข้อความแจ้งเตือน -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- ฟอร์มเพิ่มข้อมูลยอดขาย -->
        <form action="manage_sales.php" method="POST">
            <div class="mb-3">
                <label for="user_id" class="form-label">พนักงานขาย</label>
                <select class="form-select" name="user_id" required>
                    <?php
                    // ดึงข้อมูลพนักงานขาย
                    $user_result = $conn->query("SELECT id, username FROM users WHERE role = 'sales'");
                    while ($user = $user_result->fetch_assoc()) {
                        echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['username']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="year" class="form-label">ปี</label>
                <input type="number" class="form-control" name="year" required>
            </div>
            <div class="mb-3">
                <label for="quarter" class="form-label">ไตรมาส</label>
                <select class="form-select" name="quarter" required>
                    <option value="Q1">Q1</option>
                    <option value="Q2">Q2</option>
                    <option value="Q3">Q3</option>
                    <option value="Q4">Q4</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">ยอดขาย (บาท)</label>
                <input type="number" class="form-control" name="amount" required>
            </div>
            <button type="submit" name="add_sale" class="btn btn-primary">เพิ่มยอดขาย</button>
        </form>

        <hr>

        <!-- ตารางแสดงข้อมูลยอดขาย -->
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>พนักงานขาย</th>
                    <th>ปี</th>
                    <th>ไตรมาส</th>
                    <th>ยอดขาย (บาท)</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $row['year'] ?></td>
                            <td><?= $row['quarter'] ?></td>
                            <td><?= number_format($row['amount'], 2) ?></td>
                            <td>
                                <a href="manage_sales.php?delete_sale=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('คุณต้องการลบข้อมูลนี้?')">ลบ</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">ไม่มีข้อมูลยอดขาย</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

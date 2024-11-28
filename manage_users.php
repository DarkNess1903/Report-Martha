<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์ผู้บริหาร
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// เพิ่มข้อมูลพนักงานใหม่
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // บทบาทจะถูกกำหนดเป็น 'sales' เท่านั้น
    $role = 'sales';

    $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    if ($conn->query($sql) === TRUE) {
        $success_message = "เพิ่มพนักงานใหม่สำเร็จ";
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// ลบพนักงาน
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM users WHERE id = $delete_id";
    if ($conn->query($sql) === TRUE) {
        $success_message = "ลบพนักงานสำเร็จ";
    } else {
        $error_message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// แสดงข้อมูลพนักงานที่มีบทบาทเป็น 'sales' เท่านั้น
$sql = "SELECT * FROM users WHERE role = 'sales'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>จัดการข้อมูลพนักงาน</title>
</head>
<body>
    <?php include 'topnavbar.php'; ?> <!-- เรียกใช้ topnavbar.php ในหน้าอื่นๆ -->
    <div class="container mt-5">
        <h1>จัดการข้อมูลพนักงาน</h1>

        <!-- ข้อความแจ้งเตือนหากมีการเพิ่มหรือลบพนักงาน -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- ฟอร์มเพิ่มพนักงานใหม่ -->
        <form action="manage_users.php" method="POST" class="mt-4">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">เพิ่มพนักงาน</button>
        </form>

        <hr>

        <!-- ตารางแสดงข้อมูลพนักงาน -->
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <a href="manage_users.php?delete_id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('คุณต้องการลบพนักงานนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

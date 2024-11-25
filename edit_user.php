<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM users WHERE id = $id AND role = 'sales'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header('Location: manage_users.php');
    exit();
}

$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = empty($_POST['password']) ? $user['password'] : md5($_POST['password']); // ถ้าไม่กรอกรหัสผ่านใหม่ ใช้ของเดิม

    $sql = "UPDATE users SET username = '$username', password = '$password' WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        header('Location: manage_users.php');
        exit();
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>แก้ไขพนักงาน</title>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">แก้ไขพนักงาน</h1>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน (ปล่อยว่างหากไม่ต้องการเปลี่ยน)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <button type="submit" class="btn btn-warning">บันทึก</button>
            <a href="manage_users.php" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</body>
</html>

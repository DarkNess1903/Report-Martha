<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // ดึงข้อมูลผู้ใช้ที่ต้องการแก้ไข
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
        $role = $_POST['role'];

        // อัปเดตข้อมูลผู้ใช้งาน
        $sql = "UPDATE users SET username = :username, password = :password, role = :role WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username, 'password' => $password, 'role' => $role, 'id' => $user_id]);

        header('Location: dashboard.php');
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">แก้ไขข้อมูลผู้ใช้งาน</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">บทบาท</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>ผู้บริหาร</option>
                    <option value="sales" <?php if ($user['role'] == 'sales') echo 'selected'; ?>>พนักงานขาย</option>
                </select>
            </div>
            <button type="submit" class="btn btn-warning">แก้ไขผู้ใช้งาน</button>
        </form>
    </div>
</body>
</html>

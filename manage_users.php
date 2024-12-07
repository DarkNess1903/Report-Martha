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
    $password = $_POST['password'];

    // บทบาทจะถูกกำหนดเป็น 'sales' เท่านั้น
    $role = 'sales';

    $sql = "INSERT INTO users (username, password, role, created_at) VALUES ('$username', '$password', '$role', NOW())";
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

// แก้ไขพนักงาน
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    
    // อัปเดตข้อมูลผู้ใช้
    $sql = "UPDATE users SET username = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $username, $user_id);
    if ($stmt->execute()) {
        $success_message = "ข้อมูลพนักงานได้รับการอัปเดต";
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

        <!-- ปุ่มเพิ่มพนักงาน -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> เพิ่มพนักงาน
        </button>

        <!-- Modal ฟอร์มเพิ่มพนักงาน -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">เพิ่มพนักงานใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="manage_users.php" method="POST">
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
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <!-- ตารางแสดงข้อมูลพนักงาน -->
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>เวลาที่สร้าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= date('d-m-Y H:i:s', strtotime($row['created_at'])) ?></td>
                        <td>
                            <!-- ปุ่มแก้ไข -->
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i> แก้ไข
                            </button>

                            <!-- Modal ฟอร์มแก้ไขพนักงาน -->
                            <div class="modal fade" id="editUserModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editUserModalLabel">แก้ไขข้อมูลพนักงาน</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="manage_users.php" method="POST">
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
                                                </div>
                                                <button type="submit" name="edit_user" class="btn btn-primary">บันทึกการแก้ไข</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ปุ่มลบ -->
                            <a href="manage_users.php?delete_id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('คุณต้องการลบพนักงานนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>

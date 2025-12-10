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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


  <!-- ลิงค์ของตาราง -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">


  <!-- Template Main CSS File -->
  <link href="//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
 <!-- จบลิงค์ของตาราง -->

    
    <title>จัดการข้อมูลพนักงาน</title>
</head>
<body>
    <?php include 'topnavbar.php' ?> <!-- เรียกใช้ topnavbar.php ในหน้าอื่นๆ -->
    <div class="container mt-5">
        <h2>จัดการข้อมูลพนักงาน</h2><br>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <br>

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

                        <!-- ปุ่มเพิ่มพนักงาน -->
                        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus"></i> เพิ่มพนักงาน
                        </button>

                        <!-- Modal ฟอร์มเพิ่มพนักงาน -->
                        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered"> <!-- เพิ่ม modal-dialog-centered ให้ modal อยู่ตรงกลาง -->
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
                        <div class="table-responsive"> <!-- แก้ไข class เป็น table-responsive แทน table table-responsive -->
                            <table id="tabledata" class="table table-striped table-bordered">
                                <thead style="font-size: small;">
                                    <tr>
                                        <th>ลำดับ</th>
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
                                                <button class="btn btn-warning btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $row['id'] ?>">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>

                                                <!-- Modal ฟอร์มแก้ไขพนักงาน -->
                                                <div class="modal fade" id="editUserModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
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
                                                <a href="manage_users.php?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณต้องการลบพนักงานนี้?')">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div><br><br>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
        $('#tabledata').dataTable( {
        "oLanguage": {
        "sLengthMenu": "แสดง _MENU_ ข้อมูล",
        "sZeroRecords": "ไม่พบข้อมูล",
        "sInfo": "แสดง _START_ ถึง _END_ ของ _TOTAL_ ข้อมูล",
        "sInfoEmpty": "แสดง 0 ถึง 0 ของ 0 ข้อมูล",
        "sInfoFiltered": "(จากข้อมูลทั้งหมด _MAX_ ข้อมูล)",
        "sSearch": "ค้นหา :",
        "aaSorting" :[[0,'desc']],
        "oPaginate": {
        "sFirst":    "หน้าแรก",
        "sPrevious": "ก่อนหน้า",
        "sNext":     "ถัดไป",
        "sLast":     "หน้าสุดท้าย"
        },
        }
        } );
        } );
</script>
</body>
</html>

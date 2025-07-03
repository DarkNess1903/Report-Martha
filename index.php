<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // เข้ารหัสรหัสผ่าน

    // ตรวจสอบข้อมูลผู้ใช้
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // ตรวจสอบบทบาทของผู้ใช้และเปลี่ยนเส้นทางไปยังหน้า Dashboard ที่เหมาะสม
        if ($_SESSION['role'] == 'admin') {
            // ถ้าเป็น admin ให้ไปหน้า dashboard.php
            header('Location: dashboard.php');
        } else if ($_SESSION['role'] == 'sales') {
            // ถ้าเป็น sales (พนักงานขาย) ให้ไปหน้า employee_dashboard.php
            header('Location: employee_dashboard.php');
        }
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง!";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบจัดการยอดขาย Martha Group.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>เข้าสู่ระบบ</title>
    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            background-attachment: fixed;
            background-size: cover;
            height: 100vh;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .card-body {
            padding: 2rem;
        }
        .input-group-text {
            background-color: #f0f0f0;
        }
        .card-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .footer-text {
            font-size: 0.8rem;
            color:rgb(0, 0, 0);
            text-align: center;
            margin-top: 2rem;
        }
        .btn-outline-primary {
            width: 100%;
            padding: 0.8rem;
        }
        .input-group-text, .form-control {
            border-radius: 30px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
            <!-- โลโก้ระบบ -->
            <div class="text-center mb-3">
                <img src="https://martha-group.com/PIC/LogoMartha380px.png" alt="Logo" width="250"> </br></br>
                <h4 class="mt-2">ระบบจัดการยอดขาย</h4>
            </div>
            <!-- ข้อความต้อนรับ -->
            <p class="text-center text-muted">กรุณากรอกข้อมูลเพื่อเข้าสู่ระบบ</p>

            <!-- ฟอร์มล็อกอิน -->
            <form method="POST" action="index.php">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert"><?= $error ?></div>
                <?php endif; ?>
                
                <!-- ชื่อผู้ใช้ -->
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" name="username" id="username" placeholder="ชื่อผู้ใช้" required>
                </div>
                
                <!-- รหัสผ่าน -->
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" name="password" id="password" placeholder="รหัสผ่าน" required>
                    <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </span>
                </div>
                
                <!-- ปุ่มเข้าสู่ระบบ -->
                <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            </form>

            <!-- ข้อความ Footer -->
            <p class="footer-text">© 2025 Martha Group</p>
        </div>
    </div>

    <!-- สคริปต์สำหรับเปิด/ปิดการแสดงรหัสผ่าน -->
    <script>
        function togglePassword() {
            var passwordField = document.getElementById('password');
            var toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

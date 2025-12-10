<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Martha Group Sales</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-dTfge+FZrj+6uW2rq1n+ePzlH7wmlR2q2vQogpj0K7RkEunY5Tqeb+7Mq8MZ1KzvpsfAw2yg3A1xX1zRL/8Qxg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Padding ไม่ให้ Navbar บังเนื้อหา */
        body {
            padding-top: 70px;
        }

        /* ให้เมนูสามารถ wrap ได้เมื่อหน้าจอแคบ */
        .navbar-nav {
            flex-wrap: wrap;
        }

        /* ข้อความใน Navbar wrap ได้ ไม่ตัดคำ */
        .nav-link,
        .navbar-text {
            white-space: normal;
        }

        /* ปรับขนาด font สำหรับ iPad/Tablet */
        @media (min-width: 768px) and (max-width: 1024px) {

            .nav-link,
            .navbar-text {
                font-size: 0.9rem;
            }
        }

        /* ปุ่มออกจากระบบไม่ตัดคำ */
        .navbar .btn {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm fixed-top" style="background-color: #1d1ca1;">
        <div class="container">
            <!-- โลโก้ -->
            <a class="navbar-brand fw-bold text-white" href="#">Martha Group Sales</a>

            <!-- ปุ่มสำหรับเปิดเมนูบนมือถือ -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- เมนูหลัก -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- เมนูสำหรับ Admin -->
                    <?php if (isset($_SESSION['role'])): ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="dashboard.php">ยอดขายผู้แทน</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="sales_products.php">ยอดขาย Products รายปี</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="retrospect.php">เปรียบเทียบยอดขายรายปี</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="manage_sales.php">จัดการยอดขาย</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="manage_users.php">จัดการพนักงาน</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'sales'): ?>
                            <!-- เมนูสำหรับ Sales -->
                            <li class="nav-item">
                                <a class="nav-link text-white" href="employee_dashboard.php">แดชบอร์ด</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="view_sales.php">ดูยอดขายของคุณ</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- ชื่อผู้ใช้งาน พร้อมไอคอน -->
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item d-flex align-items-center">
                            <i class="fas fa-user-circle me-2 text-white" style="font-size: 1.2rem;"></i>
                            <span class="navbar-text text-white me-3">
                                <?= ($_SESSION['username']) ?>
                            </span>
                        </li>
                    <?php endif; ?>

                    <!-- ปุ่มออกจากระบบ -->
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
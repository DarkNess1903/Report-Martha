<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #1d1ca1;">
    <div class="container">
        <!-- โลโก้ -->
        <a class="navbar-brand fw-bold text-white" href="#">Martha Group Sales</a>
        
        <!-- ปุ่มสำหรับเปิดเมนูบนมือถือ -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- เมนูหลัก -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- เมนูสำหรับ Admin -->
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">หน้าแรก</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="retrospect.php">แดชบอร์ดยอดขาย</a>
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
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                    </li>
                <?php endif; ?>

                <!-- ปุ่มออกจากระบบ -->
                <li class="nav-item">
                    <a class="btn btn-outline-light" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

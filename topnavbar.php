<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Sales Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php">Dashboard</a>
                </li>
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_users.php">จัดการพนักงาน</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_sales.php">จัดการยอดขาย</a>
                        </li>
                    <?php elseif ($_SESSION['role'] == 'sales'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="view_sales.php">ดูยอดขายของคุณ</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- แสดงชื่อผู้ใช้งาน พร้อมไอคอน -->
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item d-flex align-items-center">
                        <i class="fas fa-user-circle me-2 text-white" style="font-size: 1.2rem;"></i> <!-- ไอคอน user -->
                        <span class="navbar-text text-white me-3">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                    </li>
                <?php endif; ?>      
                <li class="nav-item">
                    <a class="btn btn-outline-light" href="logout.php">ออกจากระบบ</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

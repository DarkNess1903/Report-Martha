<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $sale_id = $_GET['id'];

    // ดึงข้อมูลยอดขายที่ต้องการแก้ไข
    $sql = "SELECT * FROM sales WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $sale_id]);
    $sale = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_id = $_POST['user_id'];
        $sale_amount = $_POST['sale_amount'];
        $sale_date = $_POST['sale_date'];

        // อัปเดตยอดขาย
        $sql = "UPDATE sales SET user_id = :user_id, sale_amount = :sale_amount, sale_date = :sale_date WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'sale_amount' => $sale_amount, 'sale_date' => $sale_date, 'id' => $sale_id]);

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
    <title>แก้ไขยอดขาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">แก้ไขยอดขาย</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="user_id" class="form-label">พนักงานขาย</label>
                <select class="form-select" id="user_id" name="user_id" required>
                    <!-- เลือกพนักงานขาย -->
                    <?php
                    $sql = "SELECT * FROM users WHERE role = 'sales'";
                    $stmt = $pdo->query($sql);
                    while ($user = $stmt->fetch()) {
                        $selected = ($user['id'] == $sale['user_id']) ? 'selected' : '';
                        echo "<option value='{$user['id']}' $selected>{$user['username']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="sale_amount" class="form-label">ยอดขาย</label>
                <input type="number" class="form-control" id="sale_amount" name="sale_amount" value="<?php echo $sale['sale_amount']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="sale_date" class="form-label">วันที่</label>
                <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo $sale['sale_date']; ?>" required>
            </div>
            <button type="submit" class="btn btn-warning">อัปเดตยอดขาย</button>
        </form>
    </div>
</body>
</html>

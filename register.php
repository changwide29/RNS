<?php
// ไฟล์: register.php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

// ถ้า Login อยู่แล้ว ให้กลับไปหน้าหลัก
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$statusMsg = "";
$statusType = "";

// เมื่อมีการกดปุ่ม สมัครสมาชิก
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $fullname = htmlspecialchars(strip_tags($_POST['fullname']));
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $statusMsg = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน!";
        $statusType = "error";
    } else {
        if ($user->register($fullname, $email, $password)) {
            $statusMsg = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            $statusType = "success";
        } else {
            $statusMsg = "อีเมลนี้มีในระบบแล้ว หรือเกิดข้อผิดพลาด!";
            $statusType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Helpdesk System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f6f9; }
        .register-box { max-width: 500px; margin: 50px auto; }
    </style>
</head>
<body>

<div class="container">
    <div class="register-box">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white text-center py-3">
                <h4 class="mb-0">ลงทะเบียนผู้ใช้งานใหม่</h4>
            </div>
            <div class="card-body p-4">
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="fullname" class="form-label fw-bold">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required placeholder="นายตัวอย่าง ทดสอบ">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">อีเมล <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="user@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">รหัสผ่าน <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-bold">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="register" class="btn btn-success btn-lg">สมัครสมาชิก</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none text-secondary">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($statusMsg)): ?>
<script>
    Swal.fire({
        icon: '<?= $statusType ?>',
        title: '<?= $statusType === "success" ? "สำเร็จ!" : "ผิดพลาด!" ?>',
        text: '<?= $statusMsg ?>',
        confirmButtonColor: '#198754'
    }).then((result) => {
        <?php if($statusType === "success"): ?>
            window.location.href = 'index.php';
        <?php endif; ?>
    });
</script>
<?php endif; ?>

</body>
</html>
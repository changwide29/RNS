<?php
// ไฟล์: index.php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'technician') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $password = $_POST['password'];

    if ($user->login($email, $password)) {
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'technician') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit;
    } else {
        $errorMsg = "อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Helpdesk System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- ตั้งค่าฟอนต์ภาษาไทยให้สวยงาม -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-blue-600 px-6 py-8 text-center">
            <div class="mx-auto bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-wide">ระบบแจ้งซ่อมออนไลน์</h2>
            <p class="text-blue-100 mt-1 text-sm">IT Helpdesk System</p>
        </div>
        
        <div class="p-8">
            <form action="index.php" method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมลผู้ใช้งาน</label>
                    <input type="email" id="email" name="email" required placeholder="admin@admin.com" 
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" 
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                
                <button type="submit" name="login" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                    เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">ยังไม่มีบัญชีผู้ใช้งาน? 
                    <a href="register.php" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors">สมัครสมาชิกที่นี่</a>
                </p>
            </div>
        </div>
    </div>

    <?php if (!empty($errorMsg)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'เข้าสู่ระบบไม่สำเร็จ!',
            text: '<?= $errorMsg ?>',
            confirmButtonColor: '#2563eb',
            customClass: { popup: 'rounded-2xl' }
        });
    </script>
    <?php endif; ?>
</body>
</html>
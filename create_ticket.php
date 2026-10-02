<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Ticket.php';

// เรียกใช้งาน PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ดึงไฟล์ PHPMailer เข้ามาทำงาน (ต้องอัปโหลดโฟลเดอร์ PHPMailer ไว้ตามขั้นตอนที่ 1)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$ticket = new Ticket($db);

$statusMsg = "";
$statusType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['fullname'];
    $contact_email = htmlspecialchars(strip_tags($_POST['contact_email'])); 
    $device_type = htmlspecialchars(strip_tags($_POST['device_type']));
    $problem_desc = htmlspecialchars(strip_tags($_POST['problem_desc']));
    
    $image_path = null;
    $uploadOk = true;

    // 1. ระบบอัปโหลดรูปภาพ
    if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "assets/uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

        $file_extension = strtolower(pathinfo($_FILES["repair_image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "webp"];
        
        if (!in_array($file_extension, $allowed_types)) {
            $statusMsg = "อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น";
            $statusType = "error";
            $uploadOk = false;
        } else {
            $new_filename = uniqid("REP_IMG_") . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES["repair_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $statusMsg = "อัปโหลดรูปภาพไม่สำเร็จ";
                $statusType = "error";
                $uploadOk = false;
            }
        }
    }

    // 2. บันทึกข้อมูลและส่งอีเมลด้วย PHPMailer (SMTP)
    if ($uploadOk) {
        if ($ticket->createTicket($user_id, $device_type, $problem_desc, $image_path)) {
            
            // เริ่มการตั้งค่า SMTP
            $mail = new PHPMailer(true);

            try {
                // ตั้งค่าเซิร์ฟเวอร์ SMTP ของ Hostinger
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com'; // เซิร์ฟเวอร์ของ Hostinger
                $mail->SMTPAuth   = true;
                $mail->Username   = 'no-reply@cgracingth.com'; // อีเมลผู้ส่ง
                $mail->Password   = '!Chang2992'; // ** แก้ไขรหัสผ่านตรงนี้ **
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ใช้ SSL
                $mail->Port       = 465; // พอร์ต SSL
                $mail->CharSet    = 'UTF-8'; // รองรับภาษาไทย

                // ตั้งค่าผู้ส่ง
                $mail->setFrom('no-reply@cgracingth.com', 'IT Helpdesk');
                
                // ==========================================
                // ฉบับที่ 1: ส่งหา Admin
                // ==========================================
                $mail->addAddress('no-reply@cgracingth.com', 'System Admin'); // อีเมลแอดมินรับแจ้งเตือน
                $mail->isHTML(true);
                $mail->Subject = "มีรายการแจ้งซ่อมใหม่จากคุณ: " . $user_name;
                $mail->Body    = "
                    <h2>มีการแจ้งซ่อมอุปกรณ์เข้ามาใหม่</h2>
                    <p><strong>ผู้แจ้ง:</strong> {$user_name}</p>
                    <p><strong>อีเมลติดต่อลูกค้า:</strong> {$contact_email}</p>
                    <p><strong>อุปกรณ์ที่เสีย:</strong> {$device_type}</p>
                    <p><strong>อาการเบื้องต้น:</strong><br/>" . nl2br($problem_desc) . "</p>
                    <hr>
                    <p><a href='https://cgracingth.com/admin/dashboard.php'>เข้าสู่ระบบเพื่อดูรายละเอียด</a></p>
                ";
                $mail->send(); // สั่งส่งอีเมลหา Admin

                // เคลียร์ค่าผู้รับเดิมออก เพื่อเตรียมส่งให้ลูกค้าต่อ
                $mail->clearAddresses();

                // ==========================================
                // ฉบับที่ 2: ส่งหา ลูกค้า
                // ==========================================
                $mail->addAddress($contact_email, $user_name); // ส่งไปที่อีเมลลูกค้า
                $mail->Subject = "ระบบได้รับเรื่องแจ้งซ่อมของคุณแล้ว";
                $mail->Body    = "
                    <h2>สวัสดีคุณ {$user_name}</h2>
                    <p>ระบบ Helpdesk ได้รับเรื่องแจ้งซ่อมอุปกรณ์ <strong>{$device_type}</strong> ของคุณเรียบร้อยแล้ว</p>
                    <p><strong>อาการเบื้องต้น:</strong><br/>" . nl2br($problem_desc) . "</p>
                    <hr>
                    <p>เจ้าหน้าที่จะทำการตรวจสอบและอัปเดตสถานะให้ทราบโดยเร็วที่สุด</p>
                    <p><i>นี่คืออีเมลอัตโนมัติ กรุณาอย่าตอบกลับ</i></p>
                ";
                $mail->send(); // สั่งส่งอีเมลหาลูกค้า

                $statusMsg = "ส่งใบแจ้งซ่อมสำเร็จ! ระบบได้ส่งอีเมลแจ้งเตือนแล้ว";
                $statusType = "success";

            } catch (Exception $e) {
                // ถ้าบันทึก DB สำเร็จ แต่ส่งเมลพัง
                $statusMsg = "แจ้งซ่อมสำเร็จ แต่ระบบไม่สามารถส่งอีเมลได้ (Error: {$mail->ErrorInfo})";
                $statusType = "warning";
            }

        } else {
            $statusMsg = "เกิดข้อผิดพลาด ไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้";
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
    <title>สร้างรายการแจ้งซ่อม</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>
<body class="bg-slate-100 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span class="font-bold text-xl text-gray-800">แจ้งซ่อมอุปกรณ์</span>
                </div>
                <div class="flex items-center">
                    <a href="user_dashboard.php" class="text-sm font-medium text-gray-500 hover:text-gray-900">กลับหน้าหลัก</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Form Container -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                <h3 class="text-lg font-semibold text-white">กรอกรายละเอียดเพื่อแจ้งซ่อม</h3>
            </div>
            
            <div class="p-6 sm:p-8">
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">อีเมลของคุณ (สำหรับรับการแจ้งเตือน) <span class="text-red-500">*</span></label>
                        <input type="email" name="contact_email" placeholder="ตัวอย่าง: user@gmail.com" required 
                            class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ประเภทอุปกรณ์ <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select name="device_type" required class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 appearance-none focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                <option value="" selected disabled>-- เลือกอุปกรณ์ที่พบปัญหา --</option>
                                <option value="Computer">คอมพิวเตอร์ / โน้ตบุ๊ก</option>
                                <option value="Printer">เครื่องพิมพ์ (Printer)</option>
                                <option value="Network">ระบบเครือข่าย / อินเทอร์เน็ต</option>
                                <option value="Other">อื่นๆ</option>
                            </select>
                            <!-- Custom dropdown arrow -->
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียดอาการเสีย <span class="text-red-500">*</span></label>
                        <textarea name="problem_desc" rows="4" required placeholder="อธิบายอาการเสียให้ช่างทราบอย่างละเอียด..."
                            class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพอาการเสีย (ถ้ามี)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition-colors bg-gray-50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500 px-1">
                                        <span>อัปโหลดรูปภาพ</span>
                                        <input type="file" name="repair_image" class="sr-only" accept=".jpg, .jpeg, .png, .webp">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, WEBP ขนาดไม่เกิน 5MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="w-full sm:w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-sm transition-colors flex justify-center items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            ยืนยันการส่งซ่อม
                        </button>
                        <a href="user_dashboard.php" class="w-full sm:w-1/3 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-3 px-4 rounded-lg text-center transition-colors">
                            ยกเลิก
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php if (!empty($statusMsg)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: '<?= $statusType ?>',
            title: '<?= ($statusType === "success" || $statusType === "warning") ? "แจ้งเตือน" : "ผิดพลาด!" ?>',
            text: '<?= $statusMsg ?>',
            confirmButtonText: 'ตกลง'
        }).then((result) => {
            <?php if($statusType === "success" || $statusType === "warning"): ?>
                window.location.href = 'user_dashboard.php';
            <?php endif; ?>
        });
    });
</script>
<?php endif; ?>

</body>
</html>
<?php
// ไฟล์: admin/manage_ticket.php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'technician')) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';
require_once '../classes/Ticket.php';

$database = new Database();
$db = $database->getConnection();
$ticket = new Ticket($db);

$statusMsg = "";
$statusType = "";

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}
$ticket_id = $_GET['id'];

// เมื่อมีการกดปุ่มอัปเดตสถานะ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['status'];
    $repair_note = htmlspecialchars(strip_tags($_POST['repair_note']));
    $tech_id = $_SESSION['user_id'];

    if ($ticket->updateTicketStatus($ticket_id, $new_status, $tech_id, $repair_note)) {
        $statusMsg = "อัปเดตสถานะงานซ่อมสำเร็จ!";
        $statusType = "success";
    } else {
        $statusMsg = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
        $statusType = "error";
    }
}

// ดึงข้อมูลปัจจุบัน
$ticket_data = $ticket->getTicketById($ticket_id);
if (!$ticket_data) {
    die("ไม่พบข้อมูลใบแจ้งซ่อม");
}

// กำหนดสีป้ายสถานะปัจจุบัน
$badgeClass = 'bg-gray-100 text-gray-800'; $statusText = 'ไม่ทราบสถานะ';
if($ticket_data['status'] == 'pending') { $badgeClass = 'bg-yellow-100 text-yellow-800'; $statusText = 'รอดำเนินการ'; }
elseif($ticket_data['status'] == 'in_progress') { $badgeClass = 'bg-blue-100 text-blue-800'; $statusText = 'กำลังดำเนินการซ่อม'; }
elseif($ticket_data['status'] == 'completed') { $badgeClass = 'bg-green-100 text-green-800'; $statusText = 'เสร็จสิ้น'; }
elseif($ticket_data['status'] == 'cancelled') { $badgeClass = 'bg-red-100 text-red-800'; $statusText = 'ยกเลิก'; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการใบแจ้งซ่อม - Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen">

    <!-- Admin Navbar -->
    <nav class="bg-slate-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span class="font-bold text-xl tracking-wider">Admin Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-sm text-gray-300 hover:text-white transition-colors">กลับหน้ารวม</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    จัดการใบแจ้งซ่อม <span class="ml-3 text-blue-600">#<?= $ticket_data['ticket_no']; ?></span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">แจ้งเมื่อ: <?= date('d/m/Y เวลา H:i', strtotime($ticket_data['created_at'])); ?></p>
            </div>
            <div>
                <span class="px-3 py-1 text-sm font-semibold rounded-full border <?= $badgeClass; ?> border-current border-opacity-20">
                    สถานะปัจจุบัน: <?= $statusText; ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- ด้านซ้าย: รายละเอียดการแจ้งซ่อม -->
            <div class="lg:col-span-2 space-y-6">
                <!-- ข้อมูลลูกค้าและอุปกรณ์ -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="font-semibold text-gray-800">รายละเอียดข้อมูล</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">ชื่อผู้แจ้ง</p>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($ticket_data['user_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">ประเภทอุปกรณ์</p>
                            <p class="font-medium text-gray-900"><?= $ticket_data['device_type']; ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500 mb-2">อาการเสีย / รายละเอียดปัญหา</p>
                            <div class="p-4 bg-red-50 text-red-800 rounded-lg text-sm leading-relaxed border border-red-100">
                                <?= nl2br(htmlspecialchars($ticket_data['problem_desc'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- รูปภาพประกอบ (ถ้ามี) -->
                <?php if (!empty($ticket_data['image_path'])): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="font-semibold text-gray-800">รูปภาพประกอบการแจ้งซ่อม</h3>
                    </div>
                    <div class="p-6 flex justify-center bg-gray-100">
                        <!-- เรียกรูปจากโฟลเดอร์ด้านนอกโดยใช้ ../ -->
                        <img src="../<?= $ticket_data['image_path']; ?>" alt="รูปภาพอาการเสีย" class="max-h-96 rounded-lg shadow-sm border border-gray-300">
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ด้านขวา: ฟอร์มอัปเดตสถานะโดยช่าง -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                    <div class="border-b border-gray-200 bg-blue-600 px-6 py-4">
                        <h3 class="font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            ส่วนของช่างเทคนิค
                        </h3>
                    </div>
                    <div class="p-6">
                        <form action="manage_ticket.php?id=<?= $ticket_id; ?>" method="POST" class="space-y-5">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">อัปเดตสถานะงาน <span class="text-red-500">*</span></label>
                                <select name="status" required class="w-full px-4 py-2.5 rounded-lg bg-gray-50 border border-gray-300 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                    <option value="pending" <?= $ticket_data['status'] == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                    <option value="in_progress" <?= $ticket_data['status'] == 'in_progress' ? 'selected' : ''; ?>>กำลังดำเนินการซ่อม</option>
                                    <option value="completed" <?= $ticket_data['status'] == 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น (ซ่อมเสร็จแล้ว)</option>
                                    <option value="cancelled" <?= $ticket_data['status'] == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก (ซ่อมไม่ได้/ไม่ซ่อม)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">บันทึกการซ่อม / ค่าใช้จ่าย</label>
                                <textarea name="repair_note" rows="5" placeholder="ระบุการแก้ไข หรือค่าใช้จ่ายเพื่อให้ลูกค้าทราบ..."
                                    class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"><?= htmlspecialchars($ticket_data['repair_note'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-sm transition-colors mt-2">
                                บันทึกการอัปเดต
                            </button>
                            
                            <a href="dashboard.php" class="block text-center w-full bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-2.5 px-4 rounded-lg transition-colors mt-3">
                                ยกเลิก / กลับ
                            </a>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script SweetAlert -->
    <?php if (!empty($statusMsg)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: '<?= $statusType ?>',
                title: '<?= $statusType === "success" ? "สำเร็จ!" : "แจ้งเตือน" ?>',
                text: '<?= $statusMsg ?>',
                confirmButtonColor: '#2563eb',
                customClass: { popup: 'rounded-2xl' }
            }).then((result) => {
                if('<?= $statusType ?>' === 'success') {
                    window.location.href = 'dashboard.php';
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>
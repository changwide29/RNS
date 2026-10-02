<?php
// ไฟล์: admin/dashboard.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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

$stmt = $ticket->getAllTickets();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Helpdesk</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- DataTables TailwindCSS Integration -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
    
   <style> 
        body { font-family: 'Prompt', sans-serif; } 
        
        /* ปรับแต่ง DataTables ให้เข้ากับ Tailwind */
        table.dataTable.no-footer { 
            border-bottom: 1px solid #e5e7eb; 
        }
        
        /* จัด Layout ส่วนหัว (ค้นหา & เลือกจำนวน) */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_filter input { 
            border: 1px solid #d1d5db; 
            border-radius: 0.5rem; 
            padding: 0.4rem 0.75rem; 
            outline: none; 
            margin-left: 0.5rem;
        }
        .dataTables_wrapper .dataTables_filter input:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .dataTables_wrapper .dataTables_length select { 
            border: 1px solid #d1d5db; 
            border-radius: 0.5rem; 
            padding: 0.3rem 2rem 0.3rem 0.75rem; 
            outline: none; 
            margin: 0 0.5rem;
        }

        /* จัด Layout ส่วนท้าย (Info & Pagination) ให้แยกซ้าย-ขวา */
        .dataTables_wrapper::after {
            content: "";
            display: table;
            clear: both;
        }
        .dataTables_wrapper .dataTables_info {
            padding-top: 1.25rem;
            float: left;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem;
            float: right;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* ตกแต่งปุ่ม Pagination */
        .dataTables_wrapper .dataTables_paginate .paginate_button { 
            padding: 0.375rem 0.75rem !important; 
            margin-left: 0.25rem !important; 
            border-radius: 0.375rem !important; 
            border: 1px solid #e5e7eb !important;
            background: #ffffff !important;
            color: #374151 !important;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f3f4f6 !important;
            border-color: #d1d5db !important;
            color: #111827 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { 
            background: #2563eb !important; 
            color: white !important; 
            border-color: #2563eb !important; 
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Admin Navbar -->
    <nav class="bg-slate-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span class="font-bold text-xl tracking-wider">Admin Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-300">ผู้ดูแลระบบ: <?= htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="../logout.php" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded transition-colors">
                        ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">รายการแจ้งซ่อมทั้งหมด</h1>
                <p class="text-sm text-gray-500 mt-1">จัดการและอัปเดตสถานะงานซ่อมของลูกค้า</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <table id="ticketTable" class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-gray-200 text-sm text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">รหัส</th>
                        <th class="px-4 py-3 font-semibold">ผู้แจ้ง</th>
                        <th class="px-4 py-3 font-semibold">อุปกรณ์</th>
                        <th class="px-4 py-3 font-semibold">อาการเบื้องต้น</th>
                        <th class="px-4 py-3 font-semibold">วันที่แจ้ง</th>
                        <th class="px-4 py-3 font-semibold">สถานะ</th>
                        <th class="px-4 py-3 font-semibold text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                    <?php
                    if ($stmt && $stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // สีของสถานะ (Tailwind)
                            $badge = 'bg-gray-100 text-gray-800'; $status = 'ไม่ทราบสถานะ';
                            if($row['status'] == 'pending') { $badge = 'bg-yellow-100 text-yellow-800'; $status = 'รอดำเนินการ'; }
                            elseif($row['status'] == 'in_progress') { $badge = 'bg-blue-100 text-blue-800'; $status = 'กำลังซ่อม'; }
                            elseif($row['status'] == 'completed') { $badge = 'bg-green-100 text-green-800'; $status = 'เสร็จสิ้น'; }
                            elseif($row['status'] == 'cancelled') { $badge = 'bg-red-100 text-red-800'; $status = 'ยกเลิก'; }
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-blue-600"><?= $row['ticket_no']; ?></td>
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['user_name']); ?></td>
                        <td class="px-4 py-3"><?= $row['device_type']; ?></td>
                        <td class="px-4 py-3 truncate max-w-[150px]"><?= htmlspecialchars($row['problem_desc']); ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full border <?= $badge; ?> border-current border-opacity-20">
                                <?= $status; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="manage_ticket.php?id=<?= $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-200 rounded text-xs font-medium transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                ดู/อัปเดต
                            </a>
                        </td>
                    </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- ไม่ต้องใช้ Bootstrap DataTables แล้ว เราเขียน CSS ทับไว้ให้เข้ากับ Tailwind ด้านบนแล้วครับ -->
    <script>
        $(document).ready(function() {
            $('#ticketTable').DataTable({
                "order": [[ 4, "desc" ]], // เรียงตามวันที่ล่าสุด
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
                }
            });
        });
    </script>
</body>
</html>
<?php
// ไฟล์: classes/Ticket.php

class Ticket {
    private $conn;
    private $table_name = "repair_tickets";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. สร้างใบแจ้งซ่อมใหม่ (สำหรับ User)
    public function createTicket($user_id, $device, $desc, $image) {
        $ticket_no = "REP-" . date("Ym") . "-" . rand(1000, 9999);
        $query = "INSERT INTO " . $this->table_name . " (ticket_no, user_id, device_type, problem_desc, image_path) VALUES (:ticket_no, :user_id, :device, :desc, :image)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ticket_no", $ticket_no);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":device", $device);
        $stmt->bindParam(":desc", $desc);
        $stmt->bindParam(":image", $image);
        return $stmt->execute();
    }

    // 2. ดึงงานซ่อมของตัวเอง (สำหรับ User)
    public function getUserTickets($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt;
    }

    // 3. ดึงงานซ่อมทั้งหมด (สำหรับ Admin)
    public function getAllTickets() {
        $query = "SELECT t.*, u.fullname as user_name 
                  FROM " . $this->table_name . " t
                  LEFT JOIN users u ON t.user_id = u.id
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 4. ดึงข้อมูลงานซ่อมรายรายการ (สำหรับ Admin)
    public function getTicketById($id) {
        $query = "SELECT t.*, u.fullname as user_name 
                  FROM " . $this->table_name . " t
                  LEFT JOIN users u ON t.user_id = u.id
                  WHERE t.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 5. อัปเดตสถานะงานซ่อม (สำหรับ Admin)
    public function updateTicketStatus($id, $status, $tech_id, $repair_note) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, tech_id = :tech_id, repair_note = :repair_note 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":tech_id", $tech_id);
        $stmt->bindParam(":repair_note", $repair_note);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>
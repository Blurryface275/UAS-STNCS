<?php

class Kehadiran {
    private $conn;
    private $table_name = "kehadirans";

    public $id;
    public $tanggal;
    public $clock_in;
    public $clock_out;
    public $users_id;
    public $user_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT k.*, u.nama AS user_name FROM " . $this->table_name . " k "
            . "LEFT JOIN users u ON k.users_id = u.id "
            . "ORDER BY k.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readPaging($from_record_num, $records_per_page) {
        $query = "SELECT k.*, u.nama AS user_name FROM " . $this->table_name . " k "
            . "LEFT JOIN users u ON k.users_id = u.id "
            . "ORDER BY k.id DESC LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function count() {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }

    public function getTodayAttendance($users_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE users_id = ? AND tanggal = CURDATE()
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $users_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function clockIn($users_id) {
        $existing = $this->getTodayAttendance($users_id);

        if ($existing) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  (tanggal, clock_in, users_id)
                  VALUES (CURDATE(), NOW(), ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $users_id);

        return $stmt->execute();
    }

    public function clockOut($users_id) {
        $existing = $this->getTodayAttendance($users_id);

        if (!$existing || !empty($existing['clock_out'])) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET clock_out = NOW()
                  WHERE users_id = ? AND tanggal = CURDATE()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $users_id);

        return $stmt->execute();
    }
}
?>
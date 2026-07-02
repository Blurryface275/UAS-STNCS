<?php

class Kehadiran
{
    private $conn;
    private $table_name = "kehadirans";

    public $id;
    public $tanggal;
    public $clock_in;
    public $clock_out;
    public $latitude_in;
    public $longitude_in;
    public $latitude_out;
    public $longitude_out;
    public $users_id;
    public $user_name;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = "SELECT k.*, u.nama AS user_name FROM " . $this->table_name . " k "
            . "LEFT JOIN users u ON k.users_id = u.id "
            . "ORDER BY k.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function countTotal($userId)
    {
        $query = "SELECT COUNT(k.id) as total FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.users_id = u.id 
                  WHERE k.users_id = ? && k.status_kehadiran = 'Hadir'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function readPaging($from_record_num, $records_per_page, $powerLevel = null, $currentUserId = null)
    {
        $query = "SELECT k.*, u.nama AS user_name FROM " . $this->table_name . " k "
            . "LEFT JOIN users u ON k.users_id = u.id ";

        if ($powerLevel !== null && $currentUserId !== null) {
            $query .= "WHERE k.users_id = :current_user OR (6 - u.tipe_users_id) < :powerLevel ";
        }

        $query .= "ORDER BY k.id DESC LIMIT :from, :limit";
        $stmt = $this->conn->prepare($query);

        if ($powerLevel !== null && $currentUserId !== null) {
            $stmt->bindParam(':current_user', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':powerLevel', $powerLevel, PDO::PARAM_INT);
        }

        $stmt->bindParam(':from', $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function count($powerLevel = null, $currentUserId = null)
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name . " k LEFT JOIN users u ON k.users_id = u.id";

        if ($powerLevel !== null && $currentUserId !== null) {
            $query .= " WHERE k.users_id = :current_user OR (6 - u.tipe_users_id) < :powerLevel";
        }

        $stmt = $this->conn->prepare($query);

        if ($powerLevel !== null && $currentUserId !== null) {
            $stmt->bindParam(':current_user', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':powerLevel', $powerLevel, PDO::PARAM_INT);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }

    public function getTodayAttendance($users_id)
    {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE users_id = ? AND tanggal = CURDATE()
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $users_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAttendanceByUser($userId)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE users_id = ? ORDER BY tanggal DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getAttendanceByUserPaging($from_record_num, $records_per_page, $userId)
    {
        $query = "SELECT a.*, u.nama AS user_name 
              FROM " . $this->table_name . " a
              JOIN users u ON a.users_id = u.id
              WHERE a.users_id = ?
              ORDER BY a.tanggal DESC
              LIMIT ?, ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(3, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function countByUser($userId)
    {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name . " WHERE users_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_rows'];
    }

    public function readPagingByTipeUser($from_record_num, $records_per_page, $division, $tipeUserId)
    {
        $query = "SELECT a.*, u.nama AS user_name, u.divisi, tu.nama AS tipe_user
              FROM " . $this->table_name . " a
              JOIN users u ON a.users_id = u.id
              JOIN tipe_users tu ON u.tipe_users_id = tu.id
              WHERE 1=1";

        if ($tipeUserId == 5) { // Staff
            $query .= " AND u.id = :userId";
        } elseif ($tipeUserId == 4) { // Supervisor
            $query .= " AND u.divisi = :division AND tu.nama = 'Staff'";
        } elseif ($tipeUserId == 3) { // Manager
            $query .= " AND u.divisi = :division AND tu.nama IN ('Supervisor','Staff')";
        } elseif ($tipeUserId == 2) { // Direktur
            $query .= " AND tu.nama IN ('Manager','Supervisor','Staff')";
        } elseif ($tipeUserId == 1) { // Admin
            // tidak ada filter
        }

        $query .= " ORDER BY a.tanggal DESC 
                LIMIT :from_record_num, :records_per_page";

        $stmt = $this->conn->prepare($query);

        if ($tipeUserId == 4 || $tipeUserId == 3) {
            $stmt->bindParam(':division', $division);
        }
        if ($tipeUserId == 5) {
            $stmt->bindParam(':userId', $_SESSION['user_id']);
        }

        $stmt->bindParam(':from_record_num', $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }

    public function countByTipeUser($division, $tipeUserId)
    {
        $query = "SELECT COUNT(k.id) as total_rows
              FROM " . $this->table_name . " k
              JOIN users u ON k.users_id = u.id
              JOIN tipe_users tu ON u.tipe_users_id = tu.id
              WHERE 1=1";

        if ($tipeUserId == 5) {
            $query .= " AND u.id = ?";
        } elseif ($tipeUserId == 4) {
            $query .= " AND u.divisi = ? AND tu.nama = 'Staff'";
        } elseif ($tipeUserId == 3) {
            $query .= " AND u.divisi = ? AND tu.nama IN ('Supervisor','Staff')";
        } elseif ($tipeUserId == 2) {
            $query .= " AND tu.nama IN ('Manager','Supervisor','Staff')";
        } elseif ($tipeUserId == 1) {
        }

        $stmt = $this->conn->prepare($query);

        if ($tipeUserId == 4 || $tipeUserId == 3) {
            $stmt->bindParam(1, $division);
        }
        if ($tipeUserId == 5) {
            $stmt->bindParam(2, $_SESSION['user_id']);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_rows'];
    }

    public function readPagingAll($from_record_num, $records_per_page, $division, $tipeUserId, $userId)
    {
        $query = "SELECT a.*, u.nama AS user_name, u.divisi, tu.nama AS tipe_user
              FROM " . $this->table_name . " a
              JOIN users u ON a.users_id = u.id
              JOIN tipe_users tu ON u.tipe_users_id = tu.id
              WHERE (u.id = :userId)"; // selalu ambil pribadi

        if ($tipeUserId == 4) { // Supervisor
            $query .= " OR (u.divisi = :division AND tu.nama = 'Staff')";
        } elseif ($tipeUserId == 3) { // Manager
            $query .= " OR (u.divisi = :division AND tu.nama IN ('Supervisor','Staff'))";
        } elseif ($tipeUserId == 2) { // Direktur
            $query .= " OR (tu.nama IN ('Manager','Supervisor','Staff'))";
        } elseif ($tipeUserId == 1) { // Admin
            $query .= " OR 1=1"; // semua data
        }

        $query .= " ORDER BY a.tanggal DESC 
                LIMIT :from_record_num, :records_per_page";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);

        if ($tipeUserId == 4 || $tipeUserId == 3) {
            $stmt->bindParam(':division', $division);
        }

        $stmt->bindParam(':from_record_num', $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function countAll($division, $tipeUserId, $userId)
    {
        $query = "SELECT COUNT(*) as total_rows
              FROM " . $this->table_name . " a
              JOIN users u ON a.users_id = u.id
              JOIN tipe_users tu ON u.tipe_users_id = tu.id
              WHERE (u.id = :userId)";

        if ($tipeUserId == 4) {
            $query .= " OR (u.divisi = :division AND tu.nama = 'Staff')";
        } elseif ($tipeUserId == 3) {
            $query .= " OR (u.divisi = :division AND tu.nama IN ('Supervisor','Staff'))";
        } elseif ($tipeUserId == 2) {
            $query .= " OR (tu.nama IN ('Manager','Supervisor','Staff'))";
        } elseif ($tipeUserId == 1) {
            $query .= " OR 1=1";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);

        if ($tipeUserId == 4 || $tipeUserId == 3) {
            $stmt->bindParam(':division', $division);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_rows'];
    }

    public function clockIn($users_id, $latitude_in, $longitude_in)
    {
        $existing = $this->getTodayAttendance($users_id);

        if ($existing) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  (tanggal, clock_in, users_id, latitude_in, longitude_in)
                  VALUES (CURDATE(), NOW(), ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $users_id);
        $stmt->bindValue(2, $latitude_in);
        $stmt->bindValue(3, $longitude_in);

        return $stmt->execute();
    }

    public function clockOut($users_id, $latitude_out, $longitude_out)
    {
        $existing = $this->getTodayAttendance($users_id);

        if (!$existing || !empty($existing['clock_out'])) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET clock_out = NOW(), latitude_out = ?, longitude_out = ?
                  WHERE users_id = ? AND tanggal = CURDATE()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $latitude_out);
        $stmt->bindValue(2, $longitude_out);
        $stmt->bindValue(3, $users_id);

        return $stmt->execute();
    }
}

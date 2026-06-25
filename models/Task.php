<?php

class Task
{
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $tanggal;
    public $aktivitas;
    public $deskripsi;
    public $durasi_jam;
    public $users_id;
    public $user_name;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = "SELECT t.*, u.nama AS user_name, v.status AS verification_status 
            FROM " . $this->table_name . " t "
            . "LEFT JOIN users u ON t.users_id = u.id "
            . "LEFT JOIN verifications v ON v.tasks_idtasks = t.id "
            . "ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readPaging($from_record_num, $records_per_page, $powerLevel = null, $currentUserId = null)
    {
        $query = "SELECT t.*, u.nama AS user_name, v.status AS verification_status 
            FROM " . $this->table_name . " t "
            . "LEFT JOIN users u ON t.users_id = u.id "
            . "LEFT JOIN verifications v ON v.tasks_idtasks = t.id ";
            
        if ($powerLevel !== null && $currentUserId !== null) {
            $query .= "WHERE t.users_id = :current_user OR (6 - u.tipe_users_id) < :power_level ";
        }
            
        $query .= "ORDER BY t.id DESC LIMIT :from, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        if ($powerLevel !== null && $currentUserId !== null) {
            $stmt->bindParam(':current_user', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':power_level', $powerLevel, PDO::PARAM_INT);
        }
        $stmt->bindParam(':from', $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }

    public function create($users_id, $tanggal, $aktivitas, $deskripsi, $durasi_jam)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (tanggal, aktivitas, deskripsi, durasi_jam, users_id)
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $tanggal);
        $stmt->bindParam(2, $aktivitas);
        $stmt->bindParam(3, $deskripsi);
        $stmt->bindParam(4, $durasi_jam);
        $stmt->bindParam(5, $users_id);

        return $stmt->execute();
    }

    public function count($powerLevel = null, $currentUserId = null)
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name . " t LEFT JOIN users u ON t.users_id = u.id";
        
        if ($powerLevel !== null && $currentUserId !== null) {
            $query .= " WHERE t.users_id = :current_user OR (6 - u.tipe_users_id) < :power_level";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($powerLevel !== null && $currentUserId !== null) {
            $stmt->bindParam(':current_user', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':power_level', $powerLevel, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }
}
?>
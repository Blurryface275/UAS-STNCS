<?php

class Task
{
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $tanggal;
    public $aktivitas;
    public $deskripsi;
    public $deadline;
    public $status;
    public $file_lampiran;
    public $file_hash;
    public $latitude;
    public $longitude;
    public $submitted_at;
    public $creator_id;
    public $assignee_id;
    public $user_name;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = "SELECT t.*, u.nama AS user_name, v.status AS verification_status 
            FROM " . $this->table_name . " t "
            . "LEFT JOIN users u ON t.assignee_id = u.id "
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
            . "LEFT JOIN users u ON t.assignee_id = u.id "
            . "LEFT JOIN verifications v ON v.tasks_idtasks = t.id ";
            
        if ($powerLevel !== null && $currentUserId !== null) {
            $query .= "WHERE t.assignee_id = :current_user OR t.creator_id = :current_user OR (6 - u.tipe_users_id) < :power_level ";
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

    public function create($creator_id, $assignee_id, $tanggal, $aktivitas, $deskripsi, $deadline)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (tanggal, aktivitas, deskripsi, deadline, creator_id, assignee_id, status)
                  VALUES (?, ?, ?, ?, ?, ?, 'Pending')";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(1, $tanggal);
        $stmt->bindParam(2, $aktivitas);
        $stmt->bindParam(3, $deskripsi);
        $stmt->bindParam(4, $deadline);
        $stmt->bindParam(5, $creator_id);
        $stmt->bindParam(6, $assignee_id);

        return $stmt->execute();
    }

    public function count($powerLevel = null, $currentUserId = null)
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name . " t LEFT JOIN users u ON t.assignee_id = u.id";
        
        if ($powerLevel !== null && $currentUserId !== null) {
            $query .= " WHERE t.assignee_id = :current_user OR t.creator_id = :current_user OR (6 - u.tipe_users_id) < :power_level";
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
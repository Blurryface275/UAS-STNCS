<?php

class Verification
{
    private $conn;
    private $table_name = "verifications";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = "SELECT 
                    v.*, 
                    u.nama AS user_name,
                    t.aktivitas,
                    t.deskripsi,
                    t.tanggal,
                    t.durasi_jam
                  FROM " . $this->table_name . " v
                  LEFT JOIN users u ON v.users_id = u.id
                  LEFT JOIN tasks t ON v.tasks_idtasks = t.id
                  ORDER BY v.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readPaging($from_record_num, $records_per_page, $powerLevel = null)
    {
        $query = "SELECT 
                    v.*, 
                    u.nama AS user_name,
                    t.aktivitas,
                    t.deskripsi,
                    t.tanggal,
                    t.durasi_jam
                  FROM " . $this->table_name . " v
                  LEFT JOIN users u ON v.users_id = u.id
                  LEFT JOIN tasks t ON v.tasks_idtasks = t.id";

        if ($powerLevel !== null && $powerLevel < 5) {
            $query .= " WHERE (6 - u.tipe_users_id) < :powerLevel";
        }

        $query .= " ORDER BY v.id DESC LIMIT :from, :limit";

        $stmt = $this->conn->prepare($query);
        
        if ($powerLevel !== null && $powerLevel < 5) {
            $stmt->bindParam(':powerLevel', $powerLevel, PDO::PARAM_INT);
        }
        $stmt->bindParam(':from', $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function count($powerLevel = null, $status = null)
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name . " v LEFT JOIN users u ON v.users_id = u.id";
        
        $conditions = [];
        if ($powerLevel !== null && $powerLevel < 5) {
            $conditions[] = "(6 - u.tipe_users_id) < :powerLevel";
        }
        if ($status !== null) {
            $conditions[] = "v.status = :status";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($powerLevel !== null && $powerLevel < 5) {
            $stmt->bindParam(':powerLevel', $powerLevel, PDO::PARAM_INT);
        }
        if ($status !== null) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }

    public function updateStatus($id, $status, $catatan)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET status = ?, catatan = ?, tanggal_approval = NOW()
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $catatan);
        $stmt->bindParam(3, $id);

        return $stmt->execute();
    }

    public function create($tasks_id, $users_id)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (tasks_idtasks, users_id, status, catatan)
                  VALUES (?, ?, 'Pending', 'Menunggu verifikasi')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $tasks_id);
        $stmt->bindParam(2, $users_id);

        return $stmt->execute();
    }
}
?>
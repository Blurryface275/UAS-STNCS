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

    public function readPaging($from_record_num, $records_per_page)
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
                  ORDER BY v.id DESC
                  LIMIT ?, ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function count()
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
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
}
?>
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
        $query = "SELECT t.*, u.nama AS user_name FROM " . $this->table_name . " t "
            . "LEFT JOIN users u ON t.users_id = u.id "
            . "ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readPaging($from_record_num, $records_per_page)
    {
        $query = "SELECT t.*, u.nama AS user_name FROM " . $this->table_name . " t "
            . "LEFT JOIN users u ON t.users_id = u.id "
            . "ORDER BY t.id DESC LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
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

    public function count()
    {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }
}
?>
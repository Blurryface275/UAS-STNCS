<?php

class Task {
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $tanggal;
    public $aktivitas;
    public $deskripsi;
    public $durasi_jam;
    public $users_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Example basic method: Fetch all
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>

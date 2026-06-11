<?php

class Kehadiran {
    private $conn;
    private $table_name = "kehadirans";

    public $id;
    public $tanggal;
    public $clock_in;
    public $clock_out;
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
<?php

class Verification {
    private $conn;
    private $table_name = "verifications";

    public $id;
    public $status;
    public $catatan;
    public $tanggal_approval;
    public $tasks_idtasks;
    public $users_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>

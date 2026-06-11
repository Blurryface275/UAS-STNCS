<?php

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $nama;
    public $email;
    public $password;
    public $divisi;
    public $jabatan;
    public $status;
    public $tipe_users_id;

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

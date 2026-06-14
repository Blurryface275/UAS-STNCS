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
    public $role_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT u.*, t.nama AS role_name FROM " . $this->table_name . " u "
            . "LEFT JOIN tipe_users t ON u.tipe_users_id = t.id "
            . "ORDER BY u.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readPaging($from_record_num, $records_per_page) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC LIMIT ?, ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function login($email, $password) {
        $query = "SELECT u.*, t.nama AS role_name FROM " . $this->table_name . " u "
            . "LEFT JOIN tipe_users t ON u.tipe_users_id = t.id "
            . "WHERE u.email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        if (password_verify($password, $row['password']) || $password === $row['password']) {
            return $row;
        }

        return false;
    }

    public function count() {
        $query = "SELECT COUNT(*) as total_row FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'];
    }
}
?>

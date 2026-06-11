<?php

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->loadEnv();
    }

    private function loadEnv() {
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                switch ($name) {
                    case 'DB_HOST':
                        $this->host = $value;
                        break;
                    case 'DB_PORT':
                        $this->port = $value;
                        break;
                    case 'DB_DATABASE':
                        $this->db_name = $value;
                        break;
                    case 'DB_USERNAME':
                        $this->username = $value;
                        break;
                    case 'DB_PASSWORD':
                        $this->password = $value;
                        break;
                }
            }
        }
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>

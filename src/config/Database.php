<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'db';
        $this->db_name = $_ENV['DB_NAME'] ?? 'estacionamento';
        $this->username = $_ENV['DB_USER'] ?? '';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}

?>

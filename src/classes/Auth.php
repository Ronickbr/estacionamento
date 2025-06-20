<?php

require_once __DIR__ . '/../config/Database.php';

class Auth {
    private $conn;
    private $table_name = "usuarios";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($email, $senha) {
        $query = "SELECT id, nome, email, senha, tipo FROM " . $this->table_name . " WHERE email = :email AND ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if (password_verify($senha, $row['senha'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_nome'] = $row['nome'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_tipo'] = $row['tipo'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if ($_SESSION['user_tipo'] !== 'admin') {
            header('Location: /dashboard.php');
            exit();
        }
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'nome' => $_SESSION['user_nome'],
                'email' => $_SESSION['user_email'],
                'tipo' => $_SESSION['user_tipo']
            ];
        }
        return null;
    }
    
    public function createUser($nome, $email, $senha, $tipo = 'operador') {
        $query = "INSERT INTO " . $this->table_name . " (nome, email, senha, tipo) VALUES (:nome, :email, :senha, :tipo)";
        
        $stmt = $this->conn->prepare($query);
        
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':tipo', $tipo);
        
        return $stmt->execute();
    }
}

?>
<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "<h2>Conexão com banco: OK</h2>";
    
    try {
        // Verificar se a tabela usuarios existe
        $stmt = $conn->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "<p>Tabela 'usuarios' existe: OK</p>";
            
            // Verificar se o usuário admin existe
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = 'admin@estacionamento.com'");
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                echo "<p>Usuário admin encontrado: OK</p>";
                echo "<p>Nome: " . $user['nome'] . "</p>";
                echo "<p>Email: " . $user['email'] . "</p>";
                echo "<p>Tipo: " . $user['tipo'] . "</p>";
                echo "<p>Ativo: " . ($user['ativo'] ? 'Sim' : 'Não') . "</p>";
                
                // Testar verificação de senha
                $senha_teste = 'Admin123';
                if (password_verify($senha_teste, $user['senha'])) {
                    echo "<p style='color: green;'>Senha 'Admin123' está correta: OK</p>";
                } else {
                    echo "<p style='color: red;'>Senha 'Admin123' está incorreta</p>";
                    echo "<p>Hash armazenado: " . $user['senha'] . "</p>";
                }
            } else {
                echo "<p style='color: red;'>Usuário admin NÃO encontrado</p>";
            }
        } else {
            echo "<p style='color: red;'>Tabela 'usuarios' NÃO existe</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2 style='color: red;'>Falha na conexão com o banco</h2>";
}
?>
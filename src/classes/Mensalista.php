<?php

require_once __DIR__ . '/../config/Database.php';

class Mensalista {
    private $conn;
    private $table_name = "mensalistas";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function criar($dados) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nome, cpf, telefone, email, endereco, placa_veiculo, modelo_veiculo, cor_veiculo, 
                   estacionamento_id, vaga_fixa_id, valor_mensal, data_inicio, data_fim) 
                  VALUES (:nome, :cpf, :telefone, :email, :endereco, :placa_veiculo, :modelo_veiculo, 
                          :cor_veiculo, :estacionamento_id, :vaga_fixa_id, :valor_mensal, :data_inicio, :data_fim)";
        
        $stmt = $this->conn->prepare($query);
        
        $placa_upper = strtoupper($dados['placa_veiculo']);
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf', $dados['cpf']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':placa_veiculo', $placa_upper);
        $stmt->bindParam(':modelo_veiculo', $dados['modelo_veiculo']);
        $stmt->bindParam(':cor_veiculo', $dados['cor_veiculo']);
        $stmt->bindParam(':estacionamento_id', $dados['estacionamento_id']);
        $stmt->bindParam(':vaga_fixa_id', $dados['vaga_fixa_id']);
        $stmt->bindParam(':valor_mensal', $dados['valor_mensal']);
        $stmt->bindParam(':data_inicio', $dados['data_inicio']);
        $stmt->bindParam(':data_fim', $dados['data_fim']);
        
        if ($stmt->execute()) {
            $mensalista_id = $this->conn->lastInsertId();
            
            // Gerar primeira cobrança
            $this->gerarCobrancaMensal($mensalista_id, $dados['data_inicio'], $dados['valor_mensal']);
            
            return ['success' => true, 'id' => $mensalista_id, 'message' => 'Mensalista cadastrado com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao cadastrar mensalista'];
    }
    
    public function atualizar($id, $dados) {
        // Construir query dinamicamente baseada nos campos fornecidos
        $setClauses = [];
        $params = [];
        
        foreach ($dados as $campo => $valor) {
            $setClauses[] = "$campo = :$campo";
            $params[$campo] = $valor;
        }
        
        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $setClauses) . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind dos parâmetros dinamicamente
        foreach ($params as $campo => $valor) {
            if ($campo === 'placa_veiculo' && $valor !== null) {
                $valor = strtoupper($valor);
            }
            $stmt->bindValue(":$campo", $valor);
        }
        
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Mensalista atualizado com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao atualizar mensalista'];
    }
    
    public function buscarPorId($id) {
        $query = "SELECT m.*, e.nome as estacionamento_nome, v.numero as vaga_numero 
                  FROM " . $this->table_name . " m 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id 
                  LEFT JOIN vagas v ON m.vaga_fixa_id = v.id 
                  WHERE m.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function buscarPorPlaca($placa) {
        $query = "SELECT m.*, e.nome as estacionamento_nome, v.numero as vaga_numero 
                  FROM " . $this->table_name . " m 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id 
                  LEFT JOIN vagas v ON m.vaga_fixa_id = v.id 
                  WHERE m.placa_veiculo = :placa AND m.status = 'ativo'";
        
        $stmt = $this->conn->prepare($query);
        $placa_upper = strtoupper(str_replace('-', '', $placa)); // Remove hífen e converte para maiúscula
        $stmt->bindParam(':placa', $placa_upper);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function listar($search = '', $estacionamento_id = null, $status = null) {
        $query = "SELECT m.*, e.nome as estacionamento_nome, v.numero as vaga_numero 
                  FROM " . $this->table_name . " m 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id 
                  LEFT JOIN vagas v ON m.vaga_fixa_id = v.id 
                  WHERE 1=1";
        
        if ($search) {
            $query .= " AND (m.nome LIKE :search OR m.cpf LIKE :search OR m.placa_veiculo LIKE :search)";
        }
        
        if ($estacionamento_id) {
            $query .= " AND m.estacionamento_id = :estacionamento_id";
        }
        
        if ($status) {
            $query .= " AND m.status = :status";
        }
        
        $query .= " ORDER BY m.nome ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }
        
        if ($estacionamento_id) {
            $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        }
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function registrarAcesso($mensalista_id, $tipo, $vaga_id = null) {
        $query = "INSERT INTO acessos_mensalistas (mensalista_id, data_acesso, tipo, vaga_id) 
                  VALUES (:mensalista_id, NOW(), :tipo, :vaga_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mensalista_id', $mensalista_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':vaga_id', $vaga_id);
        
        return $stmt->execute();
    }
    
    public function getHistoricoAcessos($mensalista_id, $limite = 50) {
        $query = "SELECT a.*, v.numero as vaga_numero 
                  FROM acessos_mensalistas a 
                  LEFT JOIN vagas v ON a.vaga_id = v.id 
                  WHERE a.mensalista_id = :mensalista_id 
                  ORDER BY a.data_acesso DESC 
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mensalista_id', $mensalista_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function gerarCobrancaMensal($mensalista_id, $mes_referencia, $valor) {
        // Verificar se já existe cobrança para o mês
        $query_check = "SELECT id FROM cobrancas_mensais 
                        WHERE mensalista_id = :mensalista_id 
                        AND DATE_FORMAT(mes_referencia, '%Y-%m') = DATE_FORMAT(:mes_referencia, '%Y-%m')";
        
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(':mensalista_id', $mensalista_id);
        $stmt_check->bindParam(':mes_referencia', $mes_referencia);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Cobrança já existe para este mês'];
        }
        
        // Calcular data de vencimento (dia 10 do mês seguinte)
        $data_vencimento = date('Y-m-10', strtotime($mes_referencia . ' +1 month'));
        
        $query = "INSERT INTO cobrancas_mensais (mensalista_id, mes_referencia, valor, data_vencimento) 
                  VALUES (:mensalista_id, :mes_referencia, :valor, :data_vencimento)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mensalista_id', $mensalista_id);
        $stmt->bindParam(':mes_referencia', $mes_referencia);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':data_vencimento', $data_vencimento);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId(), 'message' => 'Cobrança gerada com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao gerar cobrança'];
    }
    
    public function registrarPagamento($cobranca_id, $forma_pagamento, $data_pagamento = null) {
        if (!$data_pagamento) {
            $data_pagamento = date('Y-m-d');
        }
        
        $query = "UPDATE cobrancas_mensais 
                  SET data_pagamento = :data_pagamento, 
                      forma_pagamento = :forma_pagamento, 
                      status = 'pago' 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_pagamento', $data_pagamento);
        $stmt->bindParam(':forma_pagamento', $forma_pagamento);
        $stmt->bindParam(':id', $cobranca_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Pagamento registrado com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao registrar pagamento'];
    }
    
    public function getCobrancasPendentes($mensalista_id = null) {
        $query = "SELECT c.*, m.nome as mensalista_nome, m.placa_veiculo 
                  FROM cobrancas_mensais c 
                  JOIN mensalistas m ON c.mensalista_id = m.id 
                  WHERE c.status = 'pendente' AND c.data_vencimento < CURDATE()";
        
        if ($mensalista_id) {
            $query .= " AND c.mensalista_id = :mensalista_id";
        }
        
        $query .= " ORDER BY c.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($mensalista_id) {
            $stmt->bindParam(':mensalista_id', $mensalista_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getRelatorioMensalistas($estacionamento_id = null) {
        $query = "SELECT 
                    COUNT(*) as total_mensalistas,
                    COUNT(CASE WHEN status = 'ativo' THEN 1 END) as ativos,
                    COUNT(CASE WHEN status = 'inativo' THEN 1 END) as inativos,
                    COUNT(CASE WHEN status = 'suspenso' THEN 1 END) as suspensos,
                    SUM(CASE WHEN status = 'ativo' THEN valor_mensal ELSE 0 END) as receita_potencial
                  FROM " . $this->table_name;
        
        if ($estacionamento_id) {
            $query .= " WHERE estacionamento_id = :estacionamento_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($estacionamento_id) {
            $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function verificarVencimentos() {
        // Marcar cobranças vencidas
        $query = "UPDATE cobrancas_mensais 
                  SET status = 'vencido' 
                  WHERE status = 'pendente' AND data_vencimento < CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    public function gerarRelatorio($data_inicio, $data_fim, $estacionamento_id = null) {
        $query = "SELECT 
                    c.id,
                    c.mes_referencia,
                    c.valor,
                    c.data_pagamento,
                    c.forma_pagamento,
                    c.status,
                    m.nome as nome_mensalista,
                    m.placa_veiculo,
                    e.nome as estacionamento_nome,
                    COALESCE(c.valor, 0) as valor_pago
                  FROM cobrancas_mensais c
                  JOIN mensalistas m ON c.mensalista_id = m.id
                  JOIN estacionamentos e ON m.estacionamento_id = e.id
                  WHERE c.mes_referencia BETWEEN :data_inicio AND :data_fim";
        
        if ($estacionamento_id) {
            $query .= " AND m.estacionamento_id = :estacionamento_id";
        }
        
        $query .= " ORDER BY c.mes_referencia DESC, m.nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        
        if ($estacionamento_id) {
            $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

?>
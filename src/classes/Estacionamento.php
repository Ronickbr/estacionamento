<?php

require_once __DIR__ . '/../config/Database.php';

class Estacionamento {
    private $conn;
    private $table_name = "estacionamentos";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function criar($dados) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nome, endereco, total_vagas, valor_hora, valor_fracao, tempo_fracao) 
                  VALUES (:nome, :endereco, :total_vagas, :valor_hora, :valor_fracao, :tempo_fracao)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':total_vagas', $dados['total_vagas']);
        $stmt->bindParam(':valor_hora', $dados['valor_hora']);
        $stmt->bindParam(':valor_fracao', $dados['valor_fracao']);
        $stmt->bindParam(':tempo_fracao', $dados['tempo_fracao']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId(), 'message' => 'Estacionamento criado com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar estacionamento'];
    }
    
    public function atualizar($id, $dados) {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome = :nome, endereco = :endereco, total_vagas = :total_vagas, 
                      valor_hora = :valor_hora, valor_fracao = :valor_fracao, tempo_fracao = :tempo_fracao 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':total_vagas', $dados['total_vagas']);
        $stmt->bindParam(':valor_hora', $dados['valor_hora']);
        $stmt->bindParam(':valor_fracao', $dados['valor_fracao']);
        $stmt->bindParam(':tempo_fracao', $dados['tempo_fracao']);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Estacionamento atualizado com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao atualizar estacionamento'];
    }
    
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function listar() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ativo = 1 ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getVagas($estacionamento_id, $tipo = null) {
        $query = "SELECT * FROM vagas WHERE estacionamento_id = :estacionamento_id AND ativo = 1";
        
        if ($tipo) {
            $query .= " AND (tipo = :tipo OR tipo = 'ambas')";
        }
        
        $query .= " ORDER BY numero ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        
        if ($tipo) {
            $stmt->bindParam(':tipo', $tipo);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getVagasDisponiveis($estacionamento_id, $tipo = 'rotativa') {
        $query = "SELECT * FROM vagas 
                  WHERE estacionamento_id = :estacionamento_id 
                  AND ativo = 1 
                  AND ocupada = 0 
                  AND (tipo = :tipo OR tipo = 'ambas') 
                  ORDER BY numero ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function criarVaga($estacionamento_id, $numero, $tipo = 'rotativa') {
        // Verificar se já existe vaga com este número
        $query_check = "SELECT id FROM vagas WHERE estacionamento_id = :estacionamento_id AND numero = :numero";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt_check->bindParam(':numero', $numero);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Já existe uma vaga com este número'];
        }
        
        $query = "INSERT INTO vagas (estacionamento_id, numero, tipo) VALUES (:estacionamento_id, :numero, :tipo)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':tipo', $tipo);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId(), 'message' => 'Vaga criada com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar vaga'];
    }
    
    public function atualizarVaga($vaga_id, $numero, $tipo) {
        $query = "UPDATE vagas SET numero = :numero, tipo = :tipo WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':id', $vaga_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Vaga atualizada com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao atualizar vaga'];
    }
    
    public function excluirVaga($vaga_id) {
        // Verificar se a vaga está ocupada
        $query_check = "SELECT ocupada FROM vagas WHERE id = :id";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(':id', $vaga_id);
        $stmt_check->execute();
        
        $vaga = $stmt_check->fetch();
        
        if ($vaga && $vaga['ocupada']) {
            return ['success' => false, 'message' => 'Não é possível excluir uma vaga ocupada'];
        }
        
        $query = "UPDATE vagas SET ativo = 0 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $vaga_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Vaga excluída com sucesso'];
        }
        
        return ['success' => false, 'message' => 'Erro ao excluir vaga'];
    }
    
    public function getEstatisticas($estacionamento_id) {
        // Buscar configurações de vagas
        $query_config = "SELECT chave, valor FROM configuracoes WHERE chave IN ('total_vagas', 'vagas_comuns', 'vagas_preferenciais', 'vagas_pcd')";
        $stmt_config = $this->conn->prepare($query_config);
        $stmt_config->execute();
        $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Usar configurações ou valores padrão
        $total_vagas = isset($configs['total_vagas']) ? (int)$configs['total_vagas'] : 25;
        
        // Se não há estacionamento_id, retornar estatísticas básicas
        if (!$estacionamento_id) {
            $vagas_ocupadas = 0;
            $query_ocupadas = "SELECT COUNT(*) as total FROM movimentacoes_rotativas WHERE status = 'ativo'";
            $stmt_ocupadas = $this->conn->prepare($query_ocupadas);
            $stmt_ocupadas->execute();
            $result = $stmt_ocupadas->fetch();
            if ($result) {
                $vagas_ocupadas = $result['total'];
            }
            
            return [
                'nome' => 'Estacionamento Principal',
                'total_vagas' => $total_vagas,
                'vagas_ocupadas' => $vagas_ocupadas,
                'vagas_livres' => $total_vagas - $vagas_ocupadas,
                'taxa_ocupacao' => $total_vagas > 0 ? round(($vagas_ocupadas / $total_vagas) * 100, 2) : 0,
                'veiculos_hoje' => 0,
                'faturamento_hoje' => 0,
                'mensalistas_ativos' => 0
            ];
        }
        
        // Calcular vagas ocupadas dinamicamente baseado nas movimentações ativas
        $query = "SELECT 
                    e.nome,
                    :total_vagas as total_vagas,
                    (
                        SELECT COUNT(*) FROM movimentacoes_rotativas 
                        WHERE estacionamento_id = e.id 
                        AND status = 'ativo'
                    ) as vagas_ocupadas,
                    (
                        :total_vagas - (
                            SELECT COUNT(*) FROM movimentacoes_rotativas 
                            WHERE estacionamento_id = e.id 
                            AND status = 'ativo'
                        )
                    ) as vagas_livres,
                    CASE 
                        WHEN :total_vagas > 0 THEN ROUND(((
                            SELECT COUNT(*) FROM movimentacoes_rotativas 
                            WHERE estacionamento_id = e.id 
                            AND status = 'ativo'
                        ) / :total_vagas) * 100, 2)
                        ELSE 0
                    END as taxa_ocupacao,
                    (
                        SELECT COUNT(*) FROM movimentacoes_rotativas 
                        WHERE estacionamento_id = e.id 
                        AND DATE(data_entrada) = CURDATE() 
                        AND status = 'ativo'
                    ) as veiculos_hoje,
                    (
                        SELECT COALESCE(SUM(valor_pago), 0) FROM movimentacoes_rotativas 
                        WHERE estacionamento_id = e.id 
                        AND DATE(data_entrada) = CURDATE() 
                        AND status = 'finalizado'
                    ) as faturamento_hoje,
                    (
                        SELECT COUNT(*) FROM mensalistas 
                        WHERE estacionamento_id = e.id 
                        AND status = 'ativo'
                    ) as mensalistas_ativos
                  FROM estacionamentos e 
                  WHERE e.id = :estacionamento_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->bindParam(':total_vagas', $total_vagas);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function getRelatorioOcupacao($estacionamento_id, $data_inicio, $data_fim) {
        $query = "SELECT 
                    DATE(m.data_entrada) as data,
                    COUNT(DISTINCT m.id) as total_entradas,
                    COUNT(DISTINCT CASE WHEN m.status = 'finalizado' THEN m.id END) as total_saidas,
                    AVG(CASE WHEN m.status = 'finalizado' THEN m.tempo_permanencia END) as tempo_medio,
                    MAX((
                        SELECT COUNT(*) FROM movimentacoes_rotativas m2 
                        WHERE m2.estacionamento_id = m.estacionamento_id 
                        AND DATE(m2.data_entrada) = DATE(m.data_entrada) 
                        AND m2.data_entrada <= m.data_entrada 
                        AND (m2.data_saida IS NULL OR m2.data_saida >= m.data_entrada)
                    )) as pico_ocupacao
                  FROM movimentacoes_rotativas m 
                  WHERE m.estacionamento_id = :estacionamento_id 
                  AND DATE(m.data_entrada) BETWEEN :data_inicio AND :data_fim 
                  GROUP BY DATE(m.data_entrada) 
                  ORDER BY data DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getVagasPorTipo($estacionamento_id) {
        $query = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    COUNT(CASE WHEN ocupada = 1 THEN 1 END) as ocupadas,
                    COUNT(CASE WHEN ocupada = 0 THEN 1 END) as livres
                  FROM vagas 
                  WHERE estacionamento_id = :estacionamento_id AND ativo = 1 
                  GROUP BY tipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function buscarVagaPorNumero($estacionamento_id, $numero) {
        $query = "SELECT * FROM vagas 
                  WHERE estacionamento_id = :estacionamento_id 
                  AND numero = :numero 
                  AND ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->bindParam(':numero', $numero);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function obterVagasDisponiveis($estacionamento_id, $tipo = 'rotativa') {
        return $this->getVagasDisponiveis($estacionamento_id, $tipo);
    }
    
    public function listarEstacionamentos() {
        return $this->listar();
    }
    
    public function gerarRelatorioOcupacao($data_inicio, $data_fim, $estacionamento_id = null) {
        $query = "SELECT 
                    DATE(m.data_entrada) as data,
                    COUNT(DISTINCT m.id) as total_entradas,
                    COUNT(DISTINCT CASE WHEN m.status = 'finalizado' THEN m.id END) as total_saidas,
                    ROUND(AVG(CASE WHEN m.status = 'finalizado' THEN m.tempo_permanencia END), 2) as tempo_medio_minutos,
                    MAX((
                        SELECT COUNT(*) FROM movimentacoes_rotativas m2 
                        WHERE m2.estacionamento_id = m.estacionamento_id 
                        AND DATE(m2.data_entrada) = DATE(m.data_entrada) 
                        AND m2.data_entrada <= m.data_entrada 
                        AND (m2.data_saida IS NULL OR m2.data_saida >= m.data_entrada)
                    )) as pico_ocupacao,
                    e.nome as estacionamento_nome
                  FROM movimentacoes_rotativas m 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id
                  WHERE DATE(m.data_entrada) BETWEEN :data_inicio AND :data_fim";
        
        if ($estacionamento_id) {
            $query .= " AND m.estacionamento_id = :estacionamento_id";
        }
        
        $query .= " GROUP BY DATE(m.data_entrada), m.estacionamento_id 
                   ORDER BY data DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        
        if ($estacionamento_id) {
            $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Sincroniza as vagas do estacionamento com as configurações do sistema
     */
    public function sincronizarVagasComConfiguracoes($estacionamento_id) {
        try {
            // Buscar configurações de vagas
            $query_config = "SELECT chave, valor FROM configuracoes WHERE chave IN ('total_vagas', 'vagas_comuns', 'vagas_preferenciais', 'vagas_pcd')";
            $stmt_config = $this->conn->prepare($query_config);
            $stmt_config->execute();
            $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Usar configurações ou valores padrão
            $total_vagas = isset($configs['total_vagas']) ? (int)$configs['total_vagas'] : 50;
            $vagas_comuns = isset($configs['vagas_comuns']) ? (int)$configs['vagas_comuns'] : 40;
            $vagas_preferenciais = isset($configs['vagas_preferenciais']) ? (int)$configs['vagas_preferenciais'] : 8;
            $vagas_pcd = isset($configs['vagas_pcd']) ? (int)$configs['vagas_pcd'] : 2;
            
            // Verificar se a soma das vagas especiais não excede o total
            if (($vagas_comuns + $vagas_preferenciais + $vagas_pcd) != $total_vagas) {
                return ['success' => false, 'message' => 'A soma das vagas por tipo não confere com o total configurado'];
            }
            
            // Buscar vagas existentes
            $query_vagas = "SELECT * FROM vagas WHERE estacionamento_id = :estacionamento_id AND ativo = 1 ORDER BY numero";
            $stmt_vagas = $this->conn->prepare($query_vagas);
            $stmt_vagas->bindParam(':estacionamento_id', $estacionamento_id);
            $stmt_vagas->execute();
            $vagas_existentes = $stmt_vagas->fetchAll();
            
            $vagas_existentes_count = count($vagas_existentes);
            
            // Se o número de vagas existentes é igual ao configurado, não fazer nada
            if ($vagas_existentes_count == $total_vagas) {
                return ['success' => true, 'message' => 'Vagas já estão sincronizadas com as configurações'];
            }
            
            $this->conn->beginTransaction();
            
            // Se há mais vagas que o configurado, desativar as excedentes
            if ($vagas_existentes_count > $total_vagas) {
                $vagas_para_desativar = array_slice($vagas_existentes, $total_vagas);
                foreach ($vagas_para_desativar as $vaga) {
                    // Verificar se a vaga está ocupada
                    if ($vaga['ocupada']) {
                        $this->conn->rollBack();
                        return ['success' => false, 'message' => 'Não é possível reduzir o número de vagas pois algumas estão ocupadas'];
                    }
                    
                    $query_desativar = "UPDATE vagas SET ativo = 0 WHERE id = :id";
                    $stmt_desativar = $this->conn->prepare($query_desativar);
                    $stmt_desativar->bindParam(':id', $vaga['id']);
                    $stmt_desativar->execute();
                }
            }
            
            // Se há menos vagas que o configurado, criar as faltantes
            if ($vagas_existentes_count < $total_vagas) {
                $vagas_para_criar = $total_vagas - $vagas_existentes_count;
                $proximo_numero = $vagas_existentes_count + 1;
                
                for ($i = 0; $i < $vagas_para_criar; $i++) {
                    $numero_vaga = str_pad($proximo_numero + $i, 2, '0', STR_PAD_LEFT);
                    
                    // Determinar o tipo da vaga baseado na sequência
                    $tipo_vaga = 'rotativa'; // padrão
                    $posicao_atual = $proximo_numero + $i;
                    
                    if ($posicao_atual <= $vagas_comuns) {
                        $tipo_vaga = 'rotativa';
                    } elseif ($posicao_atual <= ($vagas_comuns + $vagas_preferenciais)) {
                        $tipo_vaga = 'rotativa'; // vagas preferenciais também são rotativas
                        $numero_vaga = 'P' . str_pad($posicao_atual - $vagas_comuns, 2, '0', STR_PAD_LEFT);
                    } else {
                        $tipo_vaga = 'rotativa'; // vagas PCD também são rotativas
                        $numero_vaga = 'PCD' . str_pad($posicao_atual - $vagas_comuns - $vagas_preferenciais, 2, '0', STR_PAD_LEFT);
                    }
                    
                    $query_criar = "INSERT INTO vagas (estacionamento_id, numero, tipo, ocupada, ativo) VALUES (:estacionamento_id, :numero, :tipo, 0, 1)";
                    $stmt_criar = $this->conn->prepare($query_criar);
                    $stmt_criar->bindParam(':estacionamento_id', $estacionamento_id);
                    $stmt_criar->bindParam(':numero', $numero_vaga);
                    $stmt_criar->bindParam(':tipo', $tipo_vaga);
                    $stmt_criar->execute();
                }
            }
            
            // Atualizar o total de vagas no estacionamento
            $query_update_estacionamento = "UPDATE estacionamentos SET total_vagas = :total_vagas WHERE id = :id";
            $stmt_update = $this->conn->prepare($query_update_estacionamento);
            $stmt_update->bindParam(':total_vagas', $total_vagas);
            $stmt_update->bindParam(':id', $estacionamento_id);
            $stmt_update->execute();
            
            $this->conn->commit();
            
            return ['success' => true, 'message' => 'Vagas sincronizadas com sucesso conforme configurações'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erro ao sincronizar vagas: ' . $e->getMessage()];
        }
    }
}

?>
<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/TarifaManager.php';

class MovimentacaoRotativa {
    private $conn;
    private $table_name = "movimentacoes_rotativas";
    private $tarifaManager;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->tarifaManager = new TarifaManager();
    }
    
    public function registrarEntrada($estacionamento_id, $vaga_id, $placa_veiculo, $modelo_veiculo = '', $cor_veiculo = '', $usuario_id = null) {
        // Verificar se a vaga está disponível
        if (!$this->isVagaDisponivel($vaga_id)) {
            return ['success' => false, 'message' => 'Vaga não está disponível'];
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (estacionamento_id, vaga_id, placa_veiculo, modelo_veiculo, cor_veiculo, data_entrada, usuario_entrada_id) 
                  VALUES (:estacionamento_id, :vaga_id, :placa_veiculo, :modelo_veiculo, :cor_veiculo, NOW(), :usuario_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $placa_upper = strtoupper($placa_veiculo);
        
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->bindParam(':vaga_id', $vaga_id);
        $stmt->bindParam(':placa_veiculo', $placa_upper);
        $stmt->bindParam(':modelo_veiculo', $modelo_veiculo);
        $stmt->bindParam(':cor_veiculo', $cor_veiculo);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        if ($stmt->execute()) {
            $movimentacao_id = $this->conn->lastInsertId();
            
            // Marcar vaga como ocupada
            $this->marcarVagaOcupada($vaga_id, true);
            
            // Atualizar contador de vagas ocupadas
            $this->atualizarContadorVagas($estacionamento_id);
            
            // Gerar cupom de entrada
            $cupom_html = $this->gerarCupomEntrada($movimentacao_id, $placa_upper, $modelo_veiculo);
            
            return ['success' => true, 'id' => $movimentacao_id, 'message' => 'Entrada registrada com sucesso', 'cupom' => $cupom_html];
        }
        
        return ['success' => false, 'message' => 'Erro ao registrar entrada'];
    }
    
    /**
     * Gera o HTML do cupom de entrada para impressão
     * 
     * @param int $movimentacao_id ID da movimentação
     * @param string $placa Placa do veículo
     * @param string $modelo Modelo do veículo
     * @return string HTML do cupom formatado
     */
    public function gerarCupomEntrada($movimentacao_id, $placa, $modelo = '') {
        // Buscar dados da movimentação para obter data/hora exata
        $movimentacao = $this->getMovimentacaoById($movimentacao_id);
        
        if (!$movimentacao) {
            return '';
        }
        
        // Buscar configurações do sistema
        $query = "SELECT chave, valor FROM configuracoes WHERE chave IN (
            'sistema_nome', 'sistema_razao_social', 'sistema_cnpj', 'sistema_endereco',
            'sistema_telefone', 'cupom_instrucao', 'cupom_info_loja', 'cupom_largura'
        )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Formatar data e hora
        $data_entrada = new DateTime($movimentacao['data_entrada']);
        $data_formatada = $data_entrada->format('d/m/Y');
        $hora_formatada = $data_entrada->format('H:i');
        
        // Gerar número do ticket (pode ser o ID da movimentação ou outro formato)
        $ticket = str_pad($movimentacao_id, 4, '0', STR_PAD_LEFT);
        
        // Obter valores das configurações
        $nome_estacionamento = $configs['sistema_nome'] ?? 'ESTACIONAMENTO LTDA';
        $cnpj = $configs['sistema_cnpj'] ?? 'CNPJ 00.000.000/0000-00';
        $endereco = $configs['sistema_endereco'] ?? 'GONCALVES CHAVES 000';
        $telefone = $configs['sistema_telefone'] ?? 'FONE: (00) 0000 0000';
        $instrucao = $configs['cupom_instrucao'] ?? "Indispensavel a apresentacao deste\ncupom para a retirada do veiculo";
        $info_loja = $configs['cupom_info_loja'] ?? 'LOJA  04 4.52';
        $largura = (int)($configs['cupom_largura'] ?? 40);
        
        // Centralizar textos
        $nome_estacionamento_centralizado = $this->centralizarTexto($nome_estacionamento, $largura);
        $cnpj_centralizado = $this->centralizarTexto($cnpj, $largura);
        
        // Criar o HTML do cupom
        $html = "<div class='cupom-preview'>\n";
        $html .= "$nome_estacionamento_centralizado\n";
        $html .= "$cnpj_centralizado\n";
        $html .= "$endereco\n";
        $html .= "$telefone\n";
        $html .= "\n";
        $html .= "PLACA: $placa\n";
        $html .= "Tipo: " . ($modelo ?: 'CARRO') . "\n";
        $html .= "Ticket: $ticket\n";
        $html .= "Data: $data_formatada\n";
        $html .= "Hora: $hora_formatada\n";
        $html .= "\n";
        $html .= "$instrucao\n";
        $html .= "\n";
        
        // Adicionar código de barras simulado
        $html .= "<div class='barcode'>IIIIIIIIIIIIIIIIIIIIII</div>\n";
        $html .= "\n";
        $html .= "$info_loja\n";
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Centraliza um texto para impressão no cupom
     * 
     * @param string $texto Texto a ser centralizado
     * @param int $largura Largura do cupom em caracteres
     * @return string Texto centralizado
     */
    private function centralizarTexto($texto, $largura) {
        $texto_len = strlen($texto);
        if ($texto_len >= $largura) {
            return $texto;
        }
        
        $espacos = $largura - $texto_len;
        $espacos_esquerda = floor($espacos / 2);
        
        return str_repeat(' ', $espacos_esquerda) . $texto;
    }
    
    public function registrarSaida($movimentacao_id, $forma_pagamento = null, $usuario_id = null) {
        // Buscar dados da movimentação
        $movimentacao = $this->getMovimentacaoById($movimentacao_id);
        
        if (!$movimentacao || $movimentacao['status'] !== 'ativo') {
            return ['success' => false, 'message' => 'Movimentação não encontrada ou já finalizada'];
        }
        
        $data_saida = new DateTime();
        $data_entrada = new DateTime($movimentacao['data_entrada']);
        $diff = $data_saida->diff($data_entrada);
        $tempo_permanencia = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        // Calcular valor usando o novo sistema de tarifas
        $resultado_tarifa = $this->tarifaManager->calcularTarifa(
            $movimentacao['estacionamento_id'], 
            $tempo_permanencia, 
            $data_entrada, 
            $data_saida
        );
        
        $valor_calculado = $resultado_tarifa['valor'] ?? 0;
        
        $query = "UPDATE " . $this->table_name . " 
                  SET data_saida = NOW(), 
                      tempo_permanencia = :tempo_permanencia, 
                      valor_calculado = :valor_calculado, 
                      valor_pago = :valor_pago, 
                      forma_pagamento = :forma_pagamento, 
                      status = 'finalizado', 
                      usuario_saida_id = :usuario_id 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':tempo_permanencia', $tempo_permanencia);
        $stmt->bindParam(':valor_calculado', $valor_calculado);
        $stmt->bindParam(':valor_pago', $valor_calculado);
        $stmt->bindParam(':forma_pagamento', $forma_pagamento);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':id', $movimentacao_id);
        
        if ($stmt->execute()) {
            // Marcar vaga como livre
            $this->marcarVagaOcupada($movimentacao['vaga_id'], false);
            
            // Atualizar contador de vagas ocupadas
            $this->atualizarContadorVagas($movimentacao['estacionamento_id']);
            
            return [
                'success' => true, 
                'valor' => $valor_calculado, 
                'tempo' => $tempo_permanencia,
                'detalhes_tarifa' => $resultado_tarifa,
                'message' => 'Saída registrada com sucesso'
            ];
        }
        
        return ['success' => false, 'message' => 'Erro ao registrar saída'];
    }
    
    public function getMovimentacaoById($id) {
        $query = "SELECT m.*, v.numero as vaga_numero, e.nome as estacionamento_nome 
                  FROM " . $this->table_name . " m 
                  JOIN vagas v ON m.vaga_id = v.id 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id 
                  WHERE m.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function getMovimentacoesAtivas($estacionamento_id = null) {
        $query = "SELECT m.*, v.numero as vaga_numero, e.nome as estacionamento_nome 
                  FROM " . $this->table_name . " m 
                  JOIN vagas v ON m.vaga_id = v.id 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id 
                  WHERE m.status = 'ativo'";
        
        if ($estacionamento_id) {
            $query .= " AND m.estacionamento_id = :estacionamento_id";
        }
        
        $query .= " ORDER BY m.data_entrada DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($estacionamento_id) {
            $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function buscarPorPlaca($placa, $estacionamento_id = null) {
        $query = "SELECT m.*, v.numero as vaga_numero, e.nome as estacionamento_nome 
                  FROM " . $this->table_name . " m 
                  JOIN vagas v ON m.vaga_id = v.id 
                  JOIN estacionamentos e ON m.estacionamento_id = e.id 
                  WHERE m.placa_veiculo LIKE :placa AND m.status = 'ativo'";
        
        if ($estacionamento_id) {
            $query .= " AND m.estacionamento_id = :estacionamento_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $placa_search = '%' . strtoupper($placa) . '%';
        $stmt->bindParam(':placa', $placa_search);
        
        if ($estacionamento_id) {
            $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    private function isVagaDisponivel($vaga_id) {
        $query = "SELECT ocupada FROM vagas WHERE id = :vaga_id AND ativo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vaga_id', $vaga_id);
        $stmt->execute();
        
        $vaga = $stmt->fetch();
        return $vaga && !$vaga['ocupada'];
    }
    
    private function marcarVagaOcupada($vaga_id, $ocupada) {
        $query = "UPDATE vagas SET ocupada = :ocupada WHERE id = :vaga_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ocupada', $ocupada, PDO::PARAM_BOOL);
        $stmt->bindParam(':vaga_id', $vaga_id);
        $stmt->execute();
    }
    
    private function atualizarContadorVagas($estacionamento_id) {
        $query = "UPDATE estacionamentos 
                  SET vagas_ocupadas = (
                      SELECT COUNT(*) FROM vagas 
                      WHERE estacionamento_id = :estacionamento_id AND ocupada = 1
                  ) 
                  WHERE id = :estacionamento_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estacionamento_id', $estacionamento_id);
        $stmt->execute();
    }
    
    /**
     * Método legado mantido para compatibilidade
     * @deprecated Use TarifaManager::calcularTarifa() instead
     */
    private function calcularValor($estacionamento_id, $tempo_minutos) {
        $data_entrada = new DateTime();
        $data_entrada->sub(new DateInterval('PT' . $tempo_minutos . 'M'));
        
        $resultado = $this->tarifaManager->calcularTarifa(
            $estacionamento_id, 
            $tempo_minutos, 
            $data_entrada
        );
        
        return $resultado['valor'] ?? 0;
    }
    
    /**
     * Calcula valor usando o sistema de tarifas dinâmicas
     */
    public function calcularTarifaDinamica($estacionamento_id, $tempo_minutos, $data_entrada = null, $data_saida = null) {
        if ($data_entrada === null) {
            $data_entrada = new DateTime();
            $data_entrada->sub(new DateInterval('PT' . $tempo_minutos . 'M'));
        }
        
        return $this->tarifaManager->calcularTarifa(
            $estacionamento_id, 
            $tempo_minutos, 
            $data_entrada, 
            $data_saida
        );
    }
    
    /**
     * Simula cálculo de tarifa para um cenário específico
     */
    public function simularTarifa($estacionamento_id, $tempo_minutos, $data_entrada_str = null, $data_saida_str = null) {
        return $this->tarifaManager->simularTarifa($estacionamento_id, $tempo_minutos, $data_entrada_str, $data_saida_str);
    }
    
    public function getRelatorioMovimentacao($data_inicio, $data_fim, $estacionamento_id = null) {
        $query = "SELECT 
                    m.id,
                    m.data_entrada,
                    m.data_saida,
                    m.placa_veiculo,
                    m.valor_pago,
                    m.tempo_permanencia,
                    m.status,
                    v.numero as numero_vaga,
                    e.nome as estacionamento_nome
                  FROM " . $this->table_name . " m 
                  LEFT JOIN vagas v ON m.vaga_id = v.id
                  LEFT JOIN estacionamentos e ON m.estacionamento_id = e.id
                  WHERE DATE(m.data_entrada) BETWEEN :data_inicio AND :data_fim";
        
        if ($estacionamento_id) {
            $query .= " AND m.estacionamento_id = :estacionamento_id";
        }
        
        $query .= " ORDER BY m.data_entrada DESC";
        
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
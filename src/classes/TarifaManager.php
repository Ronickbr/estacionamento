<?php
require_once __DIR__ . '/../config/Database.php';

class TarifaManager {
    private $conn;
    private $configuracoes;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->carregarConfiguracoes();
    }
    
    /**
     * Carrega as configurações de tarifas do banco de dados
     */
    private function carregarConfiguracoes() {
        $query = "SELECT chave, valor FROM configuracoes WHERE chave LIKE 'tarifa_%' OR chave LIKE 'faixa_tarifa_%'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Configurações padrão
        $this->configuracoes = array_merge([
            'tarifa_tipo' => 'fracao',
            'tarifa_valor_fracao' => '5.00',
            'tarifa_tempo_fracao' => '15',
            'tarifa_valor_hora' => '8.00',
            'tarifa_valor_diaria' => '25.00',
            'tarifa_tolerancia' => '10',
            'tarifa_minutos_cortesia' => '15',
            'tarifa_minutos_excedentes' => '5',
        ], $configs);
    }
    
    /**
     * Carrega e organiza as faixas de tarifas configuradas
     */
    private function carregarFaixasTarifas() {
        $faixas = [];
        
        foreach ($this->configuracoes as $chave => $valor) {
            if (preg_match('/faixa_tarifa_(\d+)_(tempo|valor)/', $chave, $matches)) {
                $indice = $matches[1];
                $tipo = $matches[2];
                if (!isset($faixas[$indice])) {
                    $faixas[$indice] = ['tempo' => 0, 'valor' => 0];
                }
                $faixas[$indice][$tipo] = $tipo === 'tempo' ? intval($valor) : floatval($valor);
            }
        }
        
        // Filtrar faixas válidas e ordenar por tempo
        $faixas_validas = [];
        foreach ($faixas as $faixa) {
            if ($faixa['tempo'] > 0 && $faixa['valor'] > 0) {
                $faixas_validas[] = $faixa;
            }
        }
        
        // Ordenar por tempo crescente
        usort($faixas_validas, function($a, $b) {
            return $a['tempo'] - $b['tempo'];
        });
        
        return $faixas_validas;
    }
    
    /**
     * Calcula a tarifa baseada no tempo de permanência
     * 
     * @param int $estacionamento_id ID do estacionamento
     * @param int $tempo_minutos Tempo de permanência em minutos
     * @param DateTime $data_entrada Data/hora de entrada
     * @param DateTime $data_saida Data/hora de saída (opcional, padrão agora)
     * @param bool $is_mensalista Se é cliente mensalista
     * @return array Resultado do cálculo
     */
    public function calcularTarifa($estacionamento_id, $tempo_minutos, $data_entrada = null, $data_saida = null, $is_mensalista = false) {
        if ($data_entrada === null) {
            $data_entrada = new DateTime();
        }
        if ($data_saida === null) {
            $data_saida = new DateTime();
        }
        
        // Verificar cortesia
        $minutos_cortesia = intval($this->configuracoes['tarifa_minutos_cortesia']);
        if ($tempo_minutos <= $minutos_cortesia) {
            return [
                'valor' => 0.00,
                'tempo_minutos' => $tempo_minutos,
                'tipo_calculo' => 'cortesia',
                'detalhes' => 'Período de cortesia aplicado',
                'fracoes_cobradas' => 0,
                'horas_cobradas' => 0
            ];
        }
        
        // Tempo cobrável (descontando cortesia)
        $tempo_cobravel = $tempo_minutos - $minutos_cortesia;
        
        $valor = 0;
        $detalhes = '';
        $fracoes_cobradas = 0;
        $horas_cobradas = 0;
        $faixa_aplicada = null;
        
        // Carregar faixas de tarifas configuradas
        $faixas_tarifas = $this->carregarFaixasTarifas();
        
        if (!empty($faixas_tarifas)) {
            // Usar sistema de faixas de tarifas
            foreach ($faixas_tarifas as $faixa) {
                if ($tempo_cobravel <= $faixa['tempo']) {
                    $valor = $faixa['valor'];
                    $faixa_aplicada = $faixa;
                    $detalhes = "Faixa aplicada: até {$faixa['tempo']} min = R$ {$faixa['valor']}";
                    break;
                }
            }
            
            // Se não encontrou faixa aplicável, usar a última (maior tempo)
            if ($valor === 0 && !empty($faixas_tarifas)) {
                $ultima_faixa = end($faixas_tarifas);
                $valor = $ultima_faixa['valor'];
                $faixa_aplicada = $ultima_faixa;
                $detalhes = "Tempo excede todas as faixas, aplicada última faixa: R$ {$ultima_faixa['valor']}";
            }
        } else {
            // Fallback para sistema antigo se não há faixas configuradas
            $tipo_tarifa = $this->configuracoes['tarifa_tipo'];
            
            if ($tipo_tarifa === 'fracao') {
                $valor_fracao = floatval($this->configuracoes['tarifa_valor_fracao']);
                $tempo_fracao = intval($this->configuracoes['tarifa_tempo_fracao']);
                $minutos_excedentes = intval($this->configuracoes['tarifa_minutos_excedentes']);
                
                // Calcular frações
                $fracoes_cobradas = ceil($tempo_cobravel / $tempo_fracao);
                
                // Aplicar tolerância de excedentes
                $resto_minutos = $tempo_cobravel % $tempo_fracao;
                if ($resto_minutos > 0 && $resto_minutos <= $minutos_excedentes) {
                    $fracoes_cobradas = floor($tempo_cobravel / $tempo_fracao);
                }
                
                $valor = $fracoes_cobradas * $valor_fracao;
                $detalhes = "Cobrança por fração: {$fracoes_cobradas} x R$ {$valor_fracao}";
                
            } else {
                // Cobrança por hora
                $valor_hora = floatval($this->configuracoes['tarifa_valor_hora']);
                $minutos_excedentes = intval($this->configuracoes['tarifa_minutos_excedentes']);
                
                // Calcular horas
                $horas_cobradas = ceil($tempo_cobravel / 60);
            
                // Aplicar tolerância de excedentes
                $resto_minutos = $tempo_cobravel % 60;
                if ($resto_minutos > 0 && $resto_minutos <= $minutos_excedentes) {
                    $horas_cobradas = floor($tempo_cobravel / 60);
                }
                
                $valor = $horas_cobradas * $valor_hora;
                $detalhes = "Cobrança por hora: {$horas_cobradas} x R$ {$valor_hora}";
            }
        }
        
        // Cálculo de horário noturno removido
        
        // Aplicar desconto para mensalista
        if ($is_mensalista) {
            $desconto_percentual = intval($this->configuracoes['tarifa_desconto_mensalista']);
            if ($desconto_percentual > 0) {
                $desconto = $valor * ($desconto_percentual / 100);
                $valor -= $desconto;
                $detalhes .= " (Desconto mensalista: {$desconto_percentual}%)";
            }
        }
        
        // Verificar valor máximo diário
        $valor_diaria = floatval($this->configuracoes['tarifa_valor_diaria']);
        if ($valor_diaria > 0 && $valor > $valor_diaria) {
            $valor = $valor_diaria;
            $detalhes .= " (Valor limitado à diária)";
        }
        
        return [
            'valor' => round($valor, 2),
            'tempo_minutos' => $tempo_minutos,
            'tempo_cobravel' => $tempo_cobravel,
            'tipo_calculo' => !empty($faixas_tarifas) ? 'faixas' : ($this->configuracoes['tarifa_tipo'] ?? 'fracao'),
            'detalhes' => $detalhes,
            'fracoes_cobradas' => $fracoes_cobradas,
            'horas_cobradas' => $horas_cobradas,
            'cortesia_aplicada' => $minutos_cortesia,
            'faixa_aplicada' => $faixa_aplicada,
            'faixas_disponiveis' => !empty($faixas_tarifas) ? $faixas_tarifas : null
        ];
    }
    
    /**
     * Calcula tarifa completa com informações detalhadas
     * Método compatível com a API existente
     */
    public function calcularTarifaCompleta($estacionamento_id, $tempo_minutos, $data_entrada = null, $data_saida = null) {
        return $this->calcularTarifa($estacionamento_id, $tempo_minutos, $data_entrada, $data_saida);
    }
    
    /**
     * Simula o cálculo de tarifa
     */
    public function simularTarifa($estacionamento_id, $tempo_minutos, $data_entrada_str = null, $data_saida_str = null) {
        $data_entrada = $data_entrada_str ? new DateTime($data_entrada_str) : new DateTime();
        $data_saida = $data_saida_str ? new DateTime($data_saida_str) : new DateTime();
        
        return $this->calcularTarifa($estacionamento_id, $tempo_minutos, $data_entrada, $data_saida);
    }
    
    // Métodos de cálculo noturno removidos
    
    /**
     * Obtém as configurações atuais de tarifa
     */
    public function getConfiguracoes() {
        return $this->configuracoes;
    }
    
    /**
     * Atualiza uma configuração específica
     */
    public function atualizarConfiguracao($chave, $valor) {
        $query = "INSERT INTO configuracoes (chave, valor) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$chave, $valor]);
        
        if ($result) {
            $this->configuracoes[$chave] = $valor;
        }
        
        return $result;
    }
    
    /**
     * Formata valor monetário para exibição
     */
    public function formatarValor($valor) {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
    
    /**
     * Formata tempo em minutos para exibição
     */
    public function formatarTempo($minutos) {
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        
        if ($horas > 0) {
            return $horas . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
        } else {
            return $mins . 'min';
        }
    }
}
?>
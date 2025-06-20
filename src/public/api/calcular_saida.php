<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/MovimentacaoRotativa.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Verificar autenticação
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }

    // Obter dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['movimentacao_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID da movimentação não fornecido']);
        exit;
    }

    $movimentacao_id = $input['movimentacao_id'];
    $movimentacao = new MovimentacaoRotativa();
    
    // Buscar dados da movimentação
    $dados_movimentacao = $movimentacao->getMovimentacaoById($movimentacao_id);
    
    if (!$dados_movimentacao || $dados_movimentacao['status'] !== 'ativo') {
        echo json_encode(['success' => false, 'message' => 'Movimentação não encontrada ou já finalizada']);
        exit;
    }

    // Calcular tempo de permanência
    $data_entrada = new DateTime($dados_movimentacao['data_entrada']);
    $data_saida = new DateTime();
    $diff = $data_saida->diff($data_entrada);
    $tempo_minutos = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    
    // Calcular valor da tarifa (incluindo suporte a faixas)
    $tarifaManager = new TarifaManager();
    $resultado_tarifa = $tarifaManager->calcularTarifaCompleta(
        $dados_movimentacao['estacionamento_id'], 
        $tempo_minutos, 
        new DateTime($dados_movimentacao['data_entrada'])
    );
    
    $valor_calculado = $resultado_tarifa['valor'] ?? 0;
    
    // Formatar tempo
    $horas = floor($tempo_minutos / 60);
    $minutos = $tempo_minutos % 60;
    $tempo_formatado = sprintf('%02d:%02d', $horas, $minutos);
    
    // Preparar resposta
    $response = [
        'success' => true,
        'placa_veiculo' => $dados_movimentacao['placa_veiculo'],
        'modelo_veiculo' => $dados_movimentacao['modelo_veiculo'],
        'cor_veiculo' => $dados_movimentacao['cor_veiculo'],
        'vaga_numero' => $dados_movimentacao['vaga_numero'],
        'data_entrada_formatada' => $data_entrada->format('d/m/Y H:i'),
        'data_saida_formatada' => $data_saida->format('d/m/Y H:i'),
        'tempo_minutos' => $tempo_minutos,
        'tempo_formatado' => $tempo_formatado,
        'valor' => $valor_calculado,
        'valor_formatado' => number_format($valor_calculado, 2, ',', '.'),
        'detalhes_tarifa' => $resultado_tarifa
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Erro em calcular_saida.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>
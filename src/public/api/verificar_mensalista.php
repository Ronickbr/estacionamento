<?php
require_once '../config/database.php';
require_once '../classes/Mensalista.php';

// Configurar cabeçalhos para JSON
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
    // Ler dados JSON da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log para debug
    error_log('API verificar_mensalista - Input recebido: ' . json_encode($input));
    
    if (!isset($input['placa']) || empty($input['placa'])) {
        echo json_encode(['success' => false, 'message' => 'Placa não informada']);
        exit;
    }
    
    $placa = trim($input['placa']);
    error_log('API verificar_mensalista - Placa processada: ' . $placa);
    
    // Instanciar classe Mensalista
    $mensalista = new Mensalista();
    
    // Buscar mensalista pela placa
    $dadosMensalista = $mensalista->buscarPorPlaca($placa);
    error_log('API verificar_mensalista - Dados encontrados: ' . ($dadosMensalista ? 'SIM' : 'NÃO'));
    
    if ($dadosMensalista) {
        // Verificar se a mensalidade está válida
        $dataFim = new DateTime($dadosMensalista['data_fim']);
        $hoje = new DateTime();
        $valido = $dataFim >= $hoje;
        
        echo json_encode([
            'success' => true,
            'mensalista' => [
                'id' => $dadosMensalista['id'],
                'nome' => $dadosMensalista['nome'],
                'placa' => $dadosMensalista['placa'],
                'data_inicio' => $dadosMensalista['data_inicio'],
                'data_fim' => $dadosMensalista['data_fim'],
                'vaga_numero' => $dadosMensalista['vaga_numero'] ?? null
            ],
            'valido' => $valido
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'mensalista' => null,
            'valido' => false
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>
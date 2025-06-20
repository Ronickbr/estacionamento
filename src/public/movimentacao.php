<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Estacionamento.php';
require_once __DIR__ . '/../classes/MovimentacaoRotativa.php';
require_once __DIR__ . '/../classes/Mensalista.php';
require_once __DIR__ . '/../config/Database.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$estacionamento = new Estacionamento();
$movimentacao = new MovimentacaoRotativa();
$mensalista = new Mensalista();

// Buscar estacionamentos
$estacionamentos = $estacionamento->listar();
$estacionamento_atual = $estacionamentos[0] ?? null;

$message = '';
$message_type = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'sincronizar_vagas':
                if ($estacionamento_atual) {
                    $result = $estacionamento->sincronizarVagasComConfiguracoes($estacionamento_atual['id']);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                } else {
                    $message = 'Nenhum estacionamento encontrado para sincronizar';
                    $message_type = 'danger';
                }
                break;
            case 'entrada':
                // Verificar se a placa pertence a um mensalista ativo
                $placa_verificacao = $_POST['placa_veiculo'];
                $mensalista_encontrado = $mensalista->buscarPorPlaca($placa_verificacao);
                
                if ($mensalista_encontrado) {
                    // Verificar se a mensalidade está dentro da validade
                    $data_fim = new DateTime($mensalista_encontrado['data_fim']);
                    $hoje = new DateTime();
                    
                    if ($data_fim >= $hoje) {
                        $message = 'Esta placa pertence ao mensalista: ' . htmlspecialchars($mensalista_encontrado['nome']) . '. Mensalistas não precisam registrar entrada para movimentação rotativa.';
                        $message_type = 'info';
                        break; // Para aqui e não continua com o processamento
                    } else {
                        $message = 'Esta placa pertence ao mensalista: ' . htmlspecialchars($mensalista_encontrado['nome']) . ', mas a mensalidade está vencida desde ' . date('d/m/Y', strtotime($mensalista_encontrado['data_fim'])) . '. Será cobrada tarifa rotativa.';
                        $message_type = 'warning';
                        // Continua com o processamento normal para mensalistas vencidos
                    }
                }
                
                // Buscar primeira vaga disponível automaticamente
                $vaga_disponivel = null;
                if ($estacionamento_atual) {
                    $vagas_livres = $estacionamento->getVagasDisponiveis($estacionamento_atual['id'], 'rotativa');
                    $vaga_disponivel = !empty($vagas_livres) ? $vagas_livres[0] : null;
                }
                
                if (!$vaga_disponivel) {
                    $message = 'Não há vagas disponíveis no momento';
                    $message_type = 'danger';
                    break;
                }
                
                $dados = [
                    'estacionamento_id' => $_POST['estacionamento_id'],
                    'vaga_id' => $vaga_disponivel['id'], // Usar vaga selecionada automaticamente
                    'placa_veiculo' => $_POST['placa_veiculo'],
                    'modelo_veiculo' => $_POST['modelo_veiculo'] ?? '',
                    'cor_veiculo' => $_POST['cor_veiculo'] ?? '',
                    'usuario_id' => $user['id']
                ];
                
                $result = $movimentacao->registrarEntrada(
                    $dados['estacionamento_id'],
                    $dados['vaga_id'],
                    $dados['placa_veiculo'],
                    $dados['modelo_veiculo'],
                    $dados['cor_veiculo'],
                    $dados['usuario_id']
                );
                
                if ($result['success']) {
                    $message = $result['message'] . ' - Vaga: ' . $vaga_disponivel['numero'];
                    
                    // Exibir o cupom gerado em um modal
                    if (isset($result['cupom'])) {
                        // Escapar o conteúdo do cupom para JavaScript
                        $cupom_escaped = json_encode($result['cupom']);
                        
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                console.log('DOM carregado, iniciando criação do modal...');
                                
                                // Verificar se Bootstrap está disponível
                                if (typeof bootstrap === 'undefined') {
                                    console.error('Bootstrap não está carregado!');
                                    alert('Erro: Bootstrap não está carregado. O modal não pode ser exibido.');
                                    return;
                                }
                                
                                // Conteúdo do cupom escapado
                                const cupomContent = $cupom_escaped;
                                console.log('Conteúdo do cupom:', cupomContent);
                                
                                // Criar modal para o cupom
                                const modalHtml = '<div class=\"modal fade\" id=\"cupomModal\" tabindex=\"-1\" aria-labelledby=\"cupomModalLabel\" aria-hidden=\"true\">' +
                                    '<div class=\"modal-dialog modal-dialog-centered\">' +
                                        '<div class=\"modal-content\">' +
                                            '<div class=\"modal-header\">' +
                                                '<h5 class=\"modal-title\" id=\"cupomModalLabel\">Cupom de Entrada</h5>' +
                                                '<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Fechar\"></button>' +
                                            '</div>' +
                                            '<div class=\"modal-body text-center\">' +
                                                '<div class=\"cupom-container\" style=\"font-family: monospace; white-space: pre; text-align: left; background-color: white; padding: 10px; border: 1px solid #ddd; max-width: 300px; margin: 0 auto; overflow: auto;\">' +
                                                    cupomContent +
                                                '</div>' +
                                            '</div>' +
                                            '<div class=\"modal-footer\">' +
                                                '<button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Fechar</button>' +
                                                '<button type=\"button\" class=\"btn btn-primary\" onclick=\"window.print()\">Imprimir</button>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>';
                                
                                // Adicionar o modal ao corpo do documento
                                document.body.insertAdjacentHTML('beforeend', modalHtml);
                                console.log('Modal adicionado ao DOM');
                                
                                // Adicionar estilo de impressão
                                const style = document.createElement('style');
                                style.textContent = `
                                    @media print {
                                        @page {
                                            size: A4;
                                            margin: 10mm;
                                        }
                                        body * {
                                            visibility: hidden;
                                        }
                                        .modal-body, .modal-body * {
                                            visibility: visible;
                                        }
                                        .modal-body {
                                            position: absolute;
                                            left: 0;
                                            top: 0;
                                            width: 100%;
                                            height: auto;
                                            max-height: 100vh;
                                            overflow: hidden;
                                        }
                                        .cupom-container {
                                            font-size: 12px !important;
                                            line-height: 1.2 !important;
                                            max-width: 100% !important;
                                            width: auto !important;
                                            margin: 0 !important;
                                            padding: 5px !important;
                                            border: none !important;
                                            background: white !important;
                                            page-break-inside: avoid;
                                            transform: scale(0.8);
                                            transform-origin: top left;
                                        }
                                        .modal-footer, .modal-header {
                                            display: none !important;
                                        }
                                        .barcode {
                                            font-size: 10px !important;
                                            margin: 2px 0 !important;
                                        }
                                    }
                                `;
                                document.head.appendChild(style);
                                console.log('Estilos de impressão adicionados');
                                
                                // Função para imprimir
                                function imprimirCupom() {
                                    console.log('Iniciando impressão do cupom...');
                                    try {
                                        // Verificar se o modal está visível
                                        const modal = document.getElementById('cupomModal');
                                        if (!modal || !modal.classList.contains('show')) {
                                            console.warn('Modal não está visível, cancelando impressão');
                                            return;
                                        }
                                        
                                        window.print();
                                        console.log('window.print() executado com sucesso');
                                    } catch (error) {
                                        console.error('Erro ao imprimir:', error);
                                        alert('Erro ao imprimir o cupom. Tente usar o botão Imprimir no modal.');
                                    }
                                }
                                
                                // Criar instância do modal
                                const modalElement = document.getElementById('cupomModal');
                                const cupomModal = new bootstrap.Modal(modalElement);
                                
                                // Event listener para quando o modal for totalmente exibido
                                modalElement.addEventListener('shown.bs.modal', function() {
                                    console.log('Modal totalmente exibido - evento shown.bs.modal disparado');
                                    setTimeout(function() {
                                        console.log('Executando impressão após evento shown.bs.modal');
                                        imprimirCupom();
                                    }, 800);
                                });
                                
                                // Event listener para erros no modal
                                modalElement.addEventListener('hide.bs.modal', function() {
                                    console.log('Modal sendo fechado');
                                });
                                
                                // Mostrar o modal
                                console.log('Exibindo modal...');
                                cupomModal.show();
                                
                                // Fallback: tentar imprimir após um delay maior caso o evento não funcione
                                setTimeout(function() {
                                    console.log('Fallback: verificando se modal está visível após 3 segundos');
                                    const modal = document.getElementById('cupomModal');
                                    if (modal && modal.classList.contains('show')) {
                                        console.log('Modal visível no fallback, tentando imprimir...');
                                        imprimirCupom();
                                    } else {
                                        console.warn('Modal não está visível no fallback');
                                    }
                                }, 3000);
                            });
                        </script>";
                    }
                } else {
                    $message = $result['message'];
                }
                $message_type = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'saida':
                $result = $movimentacao->registrarSaida(
                    $_POST['movimentacao_id'],
                    $_POST['forma_pagamento'] ?? null,
                    $user['id']
                );
                
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                
                if ($result['success']) {
                    $message .= " - Valor: R$ " . number_format($result['valor'], 2, ',', '.');
                    $message .= " - Tempo: " . $result['tempo'] . " minutos";
                }
                break;
        }
    }
}

// Processar saída via GET (do dashboard)
if (isset($_GET['saida'])) {
    $movimentacao_dados = $movimentacao->getMovimentacaoById($_GET['saida']);
}

// Buscar vagas disponíveis
$vagas_disponiveis = [];
if ($estacionamento_atual) {
    $vagas_disponiveis = $estacionamento->getVagasDisponiveis($estacionamento_atual['id'], 'rotativa');
}

// Buscar configurações de vagas para exibir informações
$configuracoes_vagas = [];
$total_vagas_banco = 0;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Buscar configurações
    $query_config = "SELECT chave, valor FROM configuracoes WHERE chave IN ('total_vagas', 'vagas_comuns', 'vagas_preferenciais', 'vagas_pcd')";
    $stmt_config = $conn->prepare($query_config);
    $stmt_config->execute();
    $configuracoes_vagas = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Buscar total de vagas existentes no banco
    if ($estacionamento_atual) {
        $query_total = "SELECT COUNT(*) as total FROM vagas WHERE estacionamento_id = :estacionamento_id AND ativo = 1";
        $stmt_total = $conn->prepare($query_total);
        $stmt_total->bindParam(':estacionamento_id', $estacionamento_atual['id']);
        $stmt_total->execute();
        $result_total = $stmt_total->fetch();
        $total_vagas_banco = $result_total ? $result_total['total'] : 0;
    }
} catch (Exception $e) {
    // Em caso de erro, usar valores padrão
    $configuracoes_vagas = [
        'total_vagas' => '50',
        'vagas_comuns' => '40', 
        'vagas_preferenciais' => '8',
        'vagas_pcd' => '2'
    ];
}

// Buscar movimentações ativas
$movimentacoes_ativas = [];
if ($estacionamento_atual) {
    $movimentacoes_ativas = $movimentacao->getMovimentacoesAtivas($estacionamento_atual['id']);
}

// Buscar por placa se solicitado
$busca_resultado = [];
if (isset($_GET['buscar_placa']) && !empty($_GET['placa'])) {
    $busca_resultado = $movimentacao->buscarPorPlaca($_GET['placa'], $estacionamento_atual['id'] ?? null);
}

// Definir título da página
$page_title = 'Movimentação';

// Incluir header comum
require_once __DIR__ . '/includes/header.php';
?>

<link href="assets/css/style.css" rel="stylesheet">
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Movimentação de Veículos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Atualizar
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Buscar Veículo -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-search me-2"></i>
                                    Buscar Veículo
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="GET">
                                    <div class="mb-3">
                                        <label for="placa" class="form-label">Placa do Veículo</label>
                                        <input type="text" class="form-control" id="placa" name="placa" 
                                               placeholder="ABC-1234" value="<?php echo htmlspecialchars($_GET['placa'] ?? ''); ?>">
                                    </div>
                                    <button type="submit" name="buscar_placa" class="btn btn-primary btn-action w-100">
                                        <i class="bi bi-search me-2"></i>
                                        Buscar
                                    </button>
                                </form>
                                
                                <?php if (!empty($busca_resultado)): ?>
                                    <hr>
                                    <h6>Resultados:</h6>
                                    <?php foreach ($busca_resultado as $mov): ?>
                                        <div class="alert alert-info">
                                            <strong><?php echo htmlspecialchars($mov['placa_veiculo']); ?></strong><br>
                                            Vaga: <?php echo htmlspecialchars($mov['vaga_numero']); ?><br>
                                            Entrada: <?php echo date('d/m/Y H:i', strtotime($mov['data_entrada'])); ?><br>
                                            <button class="btn btn-sm btn-success mt-2" onclick="registrarSaida(<?php echo $mov['id']; ?>)">
                                                <i class="bi bi-box-arrow-right"></i> Registrar Saída
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php elseif (isset($_GET['buscar_placa'])): ?>
                                    <div class="alert alert-warning mt-3">
                                        Nenhum veículo encontrado com esta placa.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registrar Entrada -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Registrar Entrada
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($vagas_disponiveis)): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Não há vagas disponíveis no momento.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-3">
                                        <h6><i class="bi bi-info-circle me-2"></i>Seleção Automática de Vaga</h6>
                                        <p class="mb-0">O sistema selecionará automaticamente a primeira vaga disponível.</p>
                                        <?php 
                                        // Calcular vagas ocupadas baseado nas movimentações ativas
                                        $vagas_ocupadas_real = 0;
                                        if ($estacionamento_atual) {
                                            $query_ocupadas = "SELECT COUNT(*) as total FROM movimentacoes_rotativas WHERE estacionamento_id = :estacionamento_id AND status = 'ativo'";
                                            $stmt_ocupadas = $conn->prepare($query_ocupadas);
                                            $stmt_ocupadas->bindParam(':estacionamento_id', $estacionamento_atual['id']);
                                            $stmt_ocupadas->execute();
                                            $result_ocupadas = $stmt_ocupadas->fetch();
                                            $vagas_ocupadas_real = $result_ocupadas ? $result_ocupadas['total'] : 0;
                                        }
                                        $total_configurado = $configuracoes_vagas['total_vagas'] ?? 25;
                                        $vagas_livres_real = $total_configurado - $vagas_ocupadas_real;
                                        ?>
                                        <strong>Vagas disponíveis: <?php echo $vagas_livres_real; ?> / <?php echo $total_configurado; ?></strong>
                                    </div>
                                    
                                    <form method="POST" id="formEntrada">
                                        <input type="hidden" name="action" value="entrada">
                                        <input type="hidden" name="estacionamento_id" value="<?php echo $estacionamento_atual['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="placa_entrada" class="form-label">Placa do Veículo *</label>
                                                    <input type="text" class="form-control form-control-lg" id="placa_entrada" name="placa_veiculo" 
                                                           placeholder="ABC-1234" required maxlength="8" style="text-transform: uppercase;">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="modelo_veiculo" class="form-label">Modelo (Opcional)</label>
                                                    <input type="text" class="form-control" id="modelo_veiculo" name="modelo_veiculo" 
                                                           placeholder="Ex: Honda Civic">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cor_veiculo" class="form-label">Cor (Opcional)</label>
                                                    <select class="form-select" id="cor_veiculo" name="cor_veiculo">
                                                        <option value="">Selecione a cor...</option>
                                                        <option value="Branco">Branco</option>
                                                        <option value="Prata">Prata</option>
                                                        <option value="Preto">Preto</option>
                                                        <option value="Cinza">Cinza</option>
                                                        <option value="Vermelho">Vermelho</option>
                                                        <option value="Azul">Azul</option>
                                                        <option value="Verde">Verde</option>
                                                        <option value="Amarelo">Amarelo</option>
                                                        <option value="Outro">Outro</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button type="submit" class="btn btn-success btn-lg w-100" id="btnEntrada">
                                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                                    Registrar Entrada
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Veículos no Estacionamento -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-car-front-fill me-2"></i>
                            Veículos no Estacionamento (<?php echo count($movimentacoes_ativas); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($movimentacoes_ativas)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-car-front text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">Nenhum veículo no estacionamento</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>
                                            <th>Modelo</th>
                                            <th>Cor</th>
                                            <th>Vaga</th>
                                            <th>Entrada</th>
                                            <th>Tempo</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($movimentacoes_ativas as $mov): ?>
                                            <?php 
                                                $entrada = new DateTime($mov['data_entrada']);
                                                $agora = new DateTime();
                                                $diff = $agora->diff($entrada);
                                                $tempo = $diff->format('%H:%I');
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($mov['placa_veiculo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($mov['modelo_veiculo']); ?></td>
                                                <td><?php echo htmlspecialchars($mov['cor_veiculo']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($mov['vaga_numero']); ?></span></td>
                                                <td><?php echo $entrada->format('d/m/Y H:i'); ?></td>
                                                <td><?php echo $tempo; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" onclick="registrarSaida(<?php echo $mov['id']; ?>, '<?php echo htmlspecialchars($mov['placa_veiculo']); ?>')">
                                                        <i class="bi bi-box-arrow-right"></i> Saída
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de Saída -->
    <div class="modal fade" id="modalSaida" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Registrar Saída - <span id="placaVeiculo"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="saida">
                        <input type="hidden" name="movimentacao_id" id="movimentacao_id">
                        
                        <!-- Informações do Veículo e Permanência -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-car-front me-2"></i>Dados do Veículo</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Placa:</strong> <span id="infoPlaca"></span></p>
                                        <p class="mb-1"><strong>Modelo:</strong> <span id="infoModelo"></span></p>
                                        <p class="mb-1"><strong>Cor:</strong> <span id="infoCor"></span></p>
                                        <p class="mb-0"><strong>Vaga:</strong> <span id="infoVaga"></span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Permanência</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Entrada:</strong> <span id="infoEntrada"></span></p>
                                        <p class="mb-1"><strong>Saída:</strong> <span id="infoSaida"></span></p>
                                        <p class="mb-0"><strong>Tempo:</strong> <span id="infoTempo" class="text-primary fw-bold"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Valor a Pagar -->
                        <div class="alert alert-success text-center mb-4">
                            <h4 class="mb-0">
                                <i class="bi bi-currency-dollar me-2"></i>
                                Valor a Pagar: <span id="valorPagar" class="fw-bold">R$ 0,00</span>
                            </h4>
                            <div id="detalhesCalculo" class="mt-2 small"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                            <select class="form-select" name="forma_pagamento" required>
                                <option value="">Selecione...</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="pix">PIX</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-lg me-2"></i>
                            Confirmar Saída
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function registrarSaida(movimentacaoId, placa) {
            // Buscar informações da movimentação
            fetch('api/calcular_saida.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    movimentacao_id: movimentacaoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Preencher informações do modal
                    document.getElementById('movimentacao_id').value = movimentacaoId;
                    document.getElementById('placaVeiculo').textContent = data.placa_veiculo;
                    document.getElementById('infoPlaca').textContent = data.placa_veiculo;
                    document.getElementById('infoModelo').textContent = data.modelo_veiculo || 'Não informado';
                    document.getElementById('infoCor').textContent = data.cor_veiculo || 'Não informada';
                    document.getElementById('infoVaga').textContent = data.vaga_numero;
                    document.getElementById('infoEntrada').textContent = data.data_entrada_formatada;
                    document.getElementById('infoSaida').textContent = data.data_saida_formatada;
                    document.getElementById('infoTempo').textContent = data.tempo_formatado;
                    document.getElementById('valorPagar').textContent = 'R$ ' + data.valor_formatado;
                    
                    // Detalhes do cálculo
                    let detalhes = '';
                    if (data.detalhes_tarifa && data.detalhes_tarifa.detalhes) {
                        detalhes = data.detalhes_tarifa.detalhes;
                    } else {
                        detalhes = `Tempo: ${data.tempo_minutos} minutos`;
                    }
                    document.getElementById('detalhesCalculo').textContent = detalhes;
                    
                    // Exibir modal
                    var modal = new bootstrap.Modal(document.getElementById('modalSaida'));
                    modal.show();
                } else {
                    alert('Erro ao calcular valor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao buscar informações da movimentação');
            });
        }
        
        // Máscara simples para placa
        document.getElementById('placa_entrada').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (value.length > 3) {
                value = value.substring(0, 3) + '-' + value.substring(3, 7);
            }
            e.target.value = value;
        });
        
        // Garantir que o formulário funcione corretamente
        document.getElementById('formEntrada').addEventListener('submit', function(e) {
            const placa = document.getElementById('placa_entrada').value.trim();
            
            if (!placa) {
                e.preventDefault();
                alert('Por favor, informe a placa do veículo.');
                document.getElementById('placa_entrada').focus();
                return false;
            }
            
            if (placa.length < 7) {
                e.preventDefault();
                alert('Por favor, informe uma placa válida (ex: ABC-1234).');
                document.getElementById('placa_entrada').focus();
                return false;
            }
            
            // Desabilitar o botão para evitar duplo clique
            const submitButton = document.getElementById('btnEntrada');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processando...';
            
            return true;
        })
    </script>
    
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
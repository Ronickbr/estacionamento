<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Estacionamento.php';
require_once __DIR__ . '/../classes/MovimentacaoRotativa.php';
require_once __DIR__ . '/../classes/Mensalista.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$estacionamento = new Estacionamento();
$movimentacao = new MovimentacaoRotativa();
$mensalista = new Mensalista();

// Buscar primeiro estacionamento (para demo)
$estacionamentos = $estacionamento->listar();
$estacionamento_atual = $estacionamentos[0] ?? null;

$estatisticas = null;
$movimentacoes_ativas = [];
$cobrancas_pendentes = [];

if ($estacionamento_atual) {
    $estatisticas = $estacionamento->getEstatisticas($estacionamento_atual['id']);
    $movimentacoes_ativas = $movimentacao->getMovimentacoesAtivas($estacionamento_atual['id']);
    $cobrancas_pendentes = $mensalista->getCobrancasPendentes();
}

// Verificar se as estatísticas foram obtidas corretamente
if (!$estatisticas || !is_array($estatisticas)) {
    $estatisticas = [
        'nome' => 'N/A',
        'total_vagas' => 0,
        'vagas_ocupadas' => 0,
        'vagas_livres' => 0,
        'taxa_ocupacao' => 0,
        'veiculos_hoje' => 0,
        'faturamento_hoje' => 0,
        'mensalistas_ativos' => 0
    ];
}

// Definir título da página
$page_title = 'Dashboard';

// Incluir header comum
require_once __DIR__ . '/includes/header.php';
?>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo date('d/m/Y'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($estacionamento_atual && $estatisticas): ?>
                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Vagas Ocupadas</div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            <?php echo isset($estatisticas['vagas_ocupadas']) ? $estatisticas['vagas_ocupadas'] : 0; ?>/<?php echo isset($estatisticas['total_vagas']) ? $estatisticas['total_vagas'] : 0; ?>
                                        </div>
                                        <small><?php echo isset($estatisticas['taxa_ocupacao']) ? $estatisticas['taxa_ocupacao'] : 0; ?>% de ocupação</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-car-front stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Veículos Hoje</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo isset($estatisticas['veiculos_hoje']) ? $estatisticas['veiculos_hoje'] : 0; ?></div>
                                        <small>Atualmente no estacionamento</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-day stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Faturamento Hoje</div>
                                        <div class="h5 mb-0 font-weight-bold">R$ <?php echo number_format(isset($estatisticas['faturamento_hoje']) ? $estatisticas['faturamento_hoje'] : 0, 2, ',', '.'); ?></div>
                                        <small>Receita do dia</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-currency-dollar stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Mensalistas</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo isset($estatisticas['mensalistas_ativos']) ? $estatisticas['mensalistas_ativos'] : 0; ?></div>
                                        <small>Clientes ativos</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Movimentações Ativas -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-car-front-fill me-2 text-primary"></i>
                                    Veículos no Estacionamento
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
                                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($mov['vaga_numero']); ?></span></td>
                                                        <td><?php echo $entrada->format('H:i'); ?></td>
                                                        <td><?php echo $tempo; ?></td>
                                                        <td>
                                                            <a href="movimentacao.php?saida=<?php echo $mov['id']; ?>" class="btn btn-sm btn-success">
                                                                <i class="bi bi-box-arrow-right"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cobranças Pendentes -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                                    Cobranças Pendentes
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($cobrancas_pendentes)): ?>
                                    <div class="text-center py-3">
                                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2 mb-0">Todas as cobranças em dia!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($cobrancas_pendentes, 0, 5) as $cobranca): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <small class="fw-bold"><?php echo htmlspecialchars($cobranca['mensalista_nome']); ?></small><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($cobranca['placa_veiculo']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="fw-bold text-danger">R$ <?php echo number_format($cobranca['valor'], 2, ',', '.'); ?></small><br>
                                                <small class="text-muted"><?php echo date('d/m', strtotime($cobranca['data_vencimento'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($cobrancas_pendentes) > 5): ?>
                                        <div class="text-center mt-3">
                                            <a href="mensalistas.php" class="btn btn-sm btn-outline-primary">
                                                Ver todas (<?php echo count($cobrancas_pendentes); ?>)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Nenhum estacionamento configurado. Entre em contato com o administrador.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Atualizar página a cada 30 segundos para mostrar dados em tempo real
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
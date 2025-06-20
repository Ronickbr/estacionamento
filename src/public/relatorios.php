<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/MovimentacaoRotativa.php';
require_once __DIR__ . '/../classes/Mensalista.php';
require_once __DIR__ . '/../classes/Estacionamento.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$movimentacao = new MovimentacaoRotativa();
$mensalista = new Mensalista();
$estacionamento = new Estacionamento();

// Parâmetros de filtro
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$estacionamentoId = isset($_GET['estacionamento_id']) ? $_GET['estacionamento_id'] : null;
$tipoRelatorio = isset($_GET['tipo']) ? $_GET['tipo'] : 'movimentacao';

// Buscar estacionamentos para filtro
$estacionamentos = $estacionamento->listarEstacionamentos();

// Gerar relatórios baseado no tipo
$dadosRelatorio = [];
$totalReceita = 0;
$totalMovimentacoes = 0;

switch ($tipoRelatorio) {
    case 'movimentacao':
        $dadosRelatorio = $movimentacao->getRelatorioMovimentacao($dataInicio, $dataFim, $estacionamentoId);
        foreach ($dadosRelatorio as $item) {
            $totalReceita += $item['valor_pago'] ?? 0;
        }
        $totalMovimentacoes = count($dadosRelatorio);
        break;
        
    case 'mensalistas':
        $dadosRelatorio = $mensalista->gerarRelatorio($dataInicio, $dataFim, $estacionamentoId);
        foreach ($dadosRelatorio as $item) {
            $totalReceita += $item['valor_pago'];
        }
        $totalMovimentacoes = count($dadosRelatorio);
        break;
        
    case 'ocupacao':
        $dadosRelatorio = $estacionamento->gerarRelatorioOcupacao($dataInicio, $dataFim, $estacionamentoId);
        break;
        
    case 'financeiro':
        // Combinar dados de movimentação rotativa e mensalistas
        $movimentacaoRotativa = $movimentacao->gerarRelatorio($dataInicio, $dataFim, $estacionamentoId);
        $cobrancasMensalistas = $mensalista->gerarRelatorio($dataInicio, $dataFim, $estacionamentoId);
        
        $dadosRelatorio = [
            'rotativa' => $movimentacaoRotativa,
            'mensalistas' => $cobrancasMensalistas
        ];
        
        foreach ($movimentacaoRotativa as $item) {
            $totalReceita += $item['valor_pago'];
        }
        foreach ($cobrancasMensalistas as $item) {
            $totalReceita += $item['valor_pago'];
        }
        break;
}

// Estatísticas gerais
// Se não há estacionamento específico, usar o primeiro disponível
if (!$estacionamentoId && !empty($estacionamentos)) {
    $estacionamentoId = $estacionamentos[0]['id'];
}
$estatisticas = $estacionamento->getEstatisticas($estacionamentoId);

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
$page_title = 'Relatórios';

// Incluir header comum
require_once __DIR__ . '/includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
        .chart-container {
            position: relative;
            height: 400px;
        }
    </style>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Relatórios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo date('d/m/Y'); ?>
                            </button>
                        </div>
                        <button class="btn btn-outline-primary no-print" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4 no-print">
                    <div class="card-header">
                        <h5>Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo de Relatório</label>
                                <select class="form-control" id="tipo" name="tipo">
                                    <option value="movimentacao" <?php echo ($tipoRelatorio == 'movimentacao') ? 'selected' : ''; ?>>Movimentação Rotativa</option>
                                    <option value="mensalistas" <?php echo ($tipoRelatorio == 'mensalistas') ? 'selected' : ''; ?>>Mensalistas</option>
                                    <option value="ocupacao" <?php echo ($tipoRelatorio == 'ocupacao') ? 'selected' : ''; ?>>Ocupação</option>
                                    <option value="financeiro" <?php echo ($tipoRelatorio == 'financeiro') ? 'selected' : ''; ?>>Financeiro</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estacionamento_id" class="form-label">Estacionamento</label>
                                <select class="form-control" id="estacionamento_id" name="estacionamento_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($estacionamentos as $est): ?>
                                        <option value="<?php echo $est['id']; ?>" <?php echo ($estacionamentoId == $est['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($est['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $dataInicio; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $dataFim; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estatísticas Resumo -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                <h4>R$ <?php echo number_format($totalReceita, 2, ',', '.'); ?></h4>
                                <p class="mb-0">Receita Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-car fa-2x mb-2"></i>
                                <h4><?php echo $totalMovimentacoes; ?></h4>
                                <p class="mb-0">Total de Registros</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-parking fa-2x mb-2"></i>
                                <h4><?php echo isset($estatisticas['vagas_ocupadas']) ? $estatisticas['vagas_ocupadas'] : 0; ?>/<?php echo isset($estatisticas['total_vagas']) ? $estatisticas['total_vagas'] : 0; ?></h4>
                                <p class="mb-0">Vagas Ocupadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-percentage fa-2x mb-2"></i>
                                <h4><?php 
                                    $totalVagas = isset($estatisticas['total_vagas']) ? $estatisticas['total_vagas'] : 0;
                                    $vagasOcupadas = isset($estatisticas['vagas_ocupadas']) ? $estatisticas['vagas_ocupadas'] : 0;
                                    echo $totalVagas > 0 ? round(($vagasOcupadas / $totalVagas) * 100, 1) : 0; 
                                ?>%</h4>
                                <p class="mb-0">Taxa de Ocupação</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo do Relatório -->
                <?php if ($tipoRelatorio == 'movimentacao'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Relatório de Movimentação Rotativa</h5>
                            <small class="text-muted">Período: <?php echo date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora Entrada</th>
                                            <th>Data/Hora Saída</th>
                                            <th>Placa</th>
                                            <th>Vaga</th>
                                            <th>Tempo</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dadosRelatorio as $item): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($item['data_entrada'])); ?></td>
                                                <td><?php echo $item['data_saida'] ? date('d/m/Y H:i', strtotime($item['data_saida'])) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($item['placa_veiculo']); ?></td>
                                                <td><?php echo $item['numero_vaga']; ?></td>
                                                <td>
                                                    <?php 
                                                    if ($item['data_saida']) {
                                                        $entrada = new DateTime($item['data_entrada']);
                                                        $saida = new DateTime($item['data_saida']);
                                                        $diff = $entrada->diff($saida);
                                                        echo $diff->format('%H:%I');
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>R$ <?php echo number_format($item['valor_pago'] ?? 0, 2, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($tipoRelatorio == 'mensalistas'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Relatório de Mensalistas</h5>
                            <small class="text-muted">Período: <?php echo date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Placa</th>
                                            <th>Período</th>
                                            <th>Valor Mensal</th>
                                            <th>Status Pagamento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dadosRelatorio as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['nome_mensalista']); ?></td>
                                                <td><?php echo htmlspecialchars($item['placa_veiculo']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($item['mes_referencia'])); ?></td>
                                                <td>R$ <?php echo number_format($item['valor'] ?? 0, 2, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($item['data_pagamento']): ?>
                                                        <span class="badge bg-success">Pago em <?php echo date('d/m/Y', strtotime($item['data_pagamento'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Pendente</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($tipoRelatorio == 'ocupacao'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Relatório de Ocupação</h5>
                            <small class="text-muted">Período: <?php echo date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <canvas id="ocupacaoChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Estacionamento</th>
                                                    <th>Total Vagas</th>
                                                    <th>Ocupadas</th>
                                                    <th>Taxa</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dadosRelatorio as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['nome_estacionamento']); ?></td>
                                                        <td><?php echo $item['total_vagas']; ?></td>
                                                        <td><?php echo $item['vagas_ocupadas']; ?></td>
                                                        <td><?php echo round(($item['vagas_ocupadas'] / $item['total_vagas']) * 100, 1); ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($tipoRelatorio == 'financeiro'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Receita Rotativa</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Placa</th>
                                                    <th>Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $totalRotativa = 0;
                                                foreach ($dadosRelatorio['rotativa'] as $item): 
                                                    $totalRotativa += $item['valor_pago'] ?? 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($item['data_entrada'])); ?></td>
                                                        <td><?php echo htmlspecialchars($item['placa_veiculo']); ?></td>
                                                        <td>R$ <?php echo number_format($item['valor_pago'] ?? 0, 2, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-primary">
                                                    <th colspan="2">Total Rotativa:</th>
                                                    <th>R$ <?php echo number_format($totalRotativa, 2, ',', '.'); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Receita Mensalistas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Mensalista</th>
                                                    <th>Mês</th>
                                                    <th>Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $totalMensalistas = 0;
                                                foreach ($dadosRelatorio['mensalistas'] as $item): 
                                                    $totalMensalistas += $item['valor_pago'] ?? 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['nome_mensalista']); ?></td>
                                                        <td><?php echo date('m/Y', strtotime($item['mes_referencia'])); ?></td>
                                                        <td>R$ <?php echo number_format($item['valor_pago'] ?? 0, 2, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-success">
                                                    <th colspan="2">Total Mensalistas:</th>
                                                    <th>R$ <?php echo number_format($totalMensalistas, 2, ',', '.'); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>Resumo Financeiro</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <h4 class="text-primary">R$ <?php echo number_format($totalRotativa, 2, ',', '.'); ?></h4>
                                    <p>Receita Rotativa</p>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="text-success">R$ <?php echo number_format($totalMensalistas, 2, ',', '.'); ?></h4>
                                    <p>Receita Mensalistas</p>
                                </div>
                                <div class="col-md-4">
                                    <h4 class="text-info">R$ <?php echo number_format($totalRotativa + $totalMensalistas, 2, ',', '.'); ?></h4>
                                    <p>Receita Total</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de ocupação
        <?php if ($tipoRelatorio == 'ocupacao' && !empty($dadosRelatorio)): ?>
            const ctx = document.getElementById('ocupacaoChart').getContext('2d');
            const ocupacaoChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo "'" . implode("', '", array_column($dadosRelatorio, 'nome_estacionamento')) . "'"; ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_map(function($item) { return round(($item['vagas_ocupadas'] / $item['total_vagas']) * 100, 1); }, $dadosRelatorio)); ?>],
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Taxa de Ocupação por Estacionamento (%)'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
    
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
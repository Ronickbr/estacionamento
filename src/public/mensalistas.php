<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Mensalista.php';
require_once __DIR__ . '/../classes/Estacionamento.php';

$auth = new Auth();
$auth->requireLogin();
$user = $auth->getCurrentUser();

$mensalista = new Mensalista();
$estacionamento = new Estacionamento();

// Definir estacionamento_id antes do processamento
$estacionamentos = $estacionamento->listar();
$estacionamento_id = !empty($estacionamentos) ? $estacionamentos[0]['id'] : 1;

$message = '';
$messageType = '';

// Função para validar CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// Função para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para validar placa
function validarPlaca($placa) {
    $placa = preg_replace('/[^A-Z0-9]/', '', strtoupper($placa));
    // Formato antigo: AAA9999 ou novo: AAA9A99
    return preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa) || preg_match('/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/', $placa);
}

// Função para formatar placa
function formatarPlaca($placa) {
    if (empty($placa)) {
        return '';
    }
    
    $placa = preg_replace('/[^A-Z0-9]/', '', strtoupper($placa));
    
    if (strlen($placa) === 7) {
        // Formato: AAA-1234 ou AAA-1B23
        return substr($placa, 0, 3) . '-' . substr($placa, 3);
    }
    
    return $placa;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // Validações
                $errors = [];
                
                if (empty($_POST['nome'])) {
                    $errors[] = 'Nome é obrigatório.';
                }
                
                if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
                    $errors[] = 'CPF inválido.';
                }
                
                if (!empty($_POST['email']) && !validarEmail($_POST['email'])) {
                    $errors[] = 'Email inválido.';
                }
                
                if (empty($_POST['placa_veiculo']) || !validarPlaca($_POST['placa_veiculo'])) {
                    $errors[] = 'Placa do veículo inválida.';
                }
                
                if (empty($_POST['valor_mensal']) || $_POST['valor_mensal'] <= 0) {
                    $errors[] = 'Valor mensal deve ser maior que zero.';
                }
                
                if (empty($_POST['data_inicio']) || empty($_POST['data_fim'])) {
                    $errors[] = 'Datas de início e fim são obrigatórias.';
                } elseif (strtotime($_POST['data_inicio']) >= strtotime($_POST['data_fim'])) {
                    $errors[] = 'Data de início deve ser anterior à data de fim.';
                }
                
                if (empty($errors)) {
                    $data = [
                        'nome' => trim($_POST['nome']),
                        'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf']),
                        'telefone' => $_POST['telefone'],
                        'email' => $_POST['email'],
                        'endereco' => '', // Campo obrigatório na tabela
                        'placa_veiculo' => strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa_veiculo'])),
                        'modelo_veiculo' => $_POST['modelo_veiculo'],
                        'cor_veiculo' => $_POST['cor_veiculo'],
                        'estacionamento_id' => $estacionamento_id, // Campo obrigatório
                        'vaga_fixa_id' => isset($_POST['vaga_dedicada']) && $_POST['vaga_dedicada'] ? $_POST['vaga_dedicada'] : null,
                        'valor_mensal' => floatval($_POST['valor_mensal']),
                        'data_inicio' => $_POST['data_inicio'],
                        'data_fim' => $_POST['data_fim']
                    ];
                    
                    if ($mensalista->criar($data)) {
                        $message = 'Mensalista cadastrado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Erro ao cadastrar mensalista. Verifique se o CPF ou placa já estão cadastrados.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'danger';
                }
                break;
                
            case 'update':
                // Validações para atualização
                $errors = [];
                
                if (empty($_POST['nome'])) {
                    $errors[] = 'Nome é obrigatório.';
                }
                
                if (empty($_POST['cpf']) || !validarCPF($_POST['cpf'])) {
                    $errors[] = 'CPF inválido.';
                }
                
                if (!empty($_POST['email']) && !validarEmail($_POST['email'])) {
                    $errors[] = 'Email inválido.';
                }
                
                if (empty($_POST['placa_veiculo']) || !validarPlaca($_POST['placa_veiculo'])) {
                    $errors[] = 'Placa do veículo inválida.';
                }
                
                if (empty($_POST['valor_mensal']) || $_POST['valor_mensal'] <= 0) {
                    $errors[] = 'Valor mensal deve ser maior que zero.';
                }
                
                if (!empty($_POST['data_inicio']) && !empty($_POST['data_fim']) && 
                    strtotime($_POST['data_inicio']) >= strtotime($_POST['data_fim'])) {
                    $errors[] = 'Data de início deve ser anterior à data de fim.';
                }
                
                if (empty($errors)) {
                    $data = [
                        'nome' => trim($_POST['nome']),
                        'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf']),
                        'telefone' => $_POST['telefone'],
                        'email' => $_POST['email'],
                        'endereco' => '', // Campo obrigatório na tabela
                        'placa_veiculo' => strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['placa_veiculo'])),
                        'modelo_veiculo' => $_POST['modelo_veiculo'],
                        'cor_veiculo' => $_POST['cor_veiculo'],
                        'vaga_fixa_id' => isset($_POST['vaga_dedicada']) && $_POST['vaga_dedicada'] ? $_POST['vaga_dedicada'] : null,
                        'valor_mensal' => floatval($_POST['valor_mensal']),
                        'data_fim' => $_POST['data_fim'],
                        'status' => isset($_POST['ativo']) ? 'ativo' : 'inativo'
                    ];
                    
                    if ($mensalista->atualizar($_POST['id'], $data)) {
                        $message = 'Mensalista atualizado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Erro ao atualizar mensalista. Verifique se o CPF ou placa já estão cadastrados.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                if ($mensalista->atualizar($_POST['id'], ['status' => 'inativo'])) {
                    $message = 'Mensalista desativado com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Erro ao desativar mensalista.';
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Buscar vagas disponíveis
$vagasDisponiveis = $estacionamento->obterVagasDisponiveis($estacionamento_id);

// Parâmetros de filtro e paginação
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$vaga_filter = isset($_GET['vaga']) ? $_GET['vaga'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; // Registros por página
$offset = ($page - 1) * $limit;

// Buscar mensalistas com filtros e paginação
$mensalistas = $mensalista->listar($search, $estacionamento_id);

// Aplicar filtros
if ($status_filter) {
    $mensalistas = array_filter($mensalistas, function($m) use ($status_filter) {
        return $m['status'] === $status_filter;
    });
}

if ($vaga_filter === 'com_vaga') {
    $mensalistas = array_filter($mensalistas, function($m) {
        return !empty($m['vaga_numero']);
    });
} elseif ($vaga_filter === 'sem_vaga') {
    $mensalistas = array_filter($mensalistas, function($m) {
        return empty($m['vaga_numero']);
    });
}

// Calcular total e paginação
$total_records = count($mensalistas);
$total_pages = ceil($total_records / $limit);
$mensalistas = array_slice($mensalistas, $offset, $limit);

// Verificar mensalistas que faltam 5 dias para vencer ou já venceram
$mensalistas_vencendo = [];
foreach ($mensalistas as $m) {
    $dataFim = new DateTime($m['data_fim']);
    $hoje = new DateTime();
    $hoje->setTime(0, 0, 0); // Zerar horas para comparação precisa
    $dataFim->setTime(0, 0, 0);
    
    // Calcular diferença em dias
    $diff = $hoje->diff($dataFim);
    $diasRestantes = $dataFim >= $hoje ? $diff->days : -$diff->days;
    
    // Incluir se faltam 5 dias ou menos, ou já venceu
    if ($diasRestantes <= 5 && $m['status'] === 'ativo') {
        $mensalistas_vencendo[] = $m;
    }
}

// Exportar CSV se solicitado
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mensalistas_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Cabeçalho do CSV
    fputcsv($output, [
        'Nome',
        'CPF',
        'Email',
        'Telefone',
        'Placa do Veículo',
        'Vaga',
        'Valor Mensal',
        'Data Início',
        'Data Fim',
        'Status'
    ]);
    
    // Dados dos mensalistas filtrados
    foreach ($mensalistas as $mensalista) {
        $vaga = $mensalista['vaga_numero'] ? 'Vaga ' . $mensalista['vaga_numero'] : 'Sem vaga fixa';
        
        fputcsv($output, [
            $mensalista['nome'],
            $mensalista['cpf'],
            $mensalista['email'],
            $mensalista['telefone'],
            formatarPlaca($mensalista['placa_veiculo']),
            $vaga,
            'R$ ' . number_format($mensalista['valor_mensal'], 2, ',', '.'),
            date('d/m/Y', strtotime($mensalista['data_inicio'])),
            date('d/m/Y', strtotime($mensalista['data_fim'])),
            ucfirst($mensalista['status'])
        ]);
    }
    
    fclose($output);
    exit;
}

// Obter mensalista para edição
$editMensalista = null;
if (isset($_GET['edit'])) {
    $editMensalista = $mensalista->buscarPorId($_GET['edit']);
}
$page_title = 'Mensalistas';
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>
    <link href="assets/css/style.css" rel="stylesheet">
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mensalistas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo date('d/m/Y'); ?>
                            </button>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mensalistaModal"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Adicionar novo mensalista">
                            <i class="fas fa-user-plus me-1"></i> Novo Mensalista
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Alerta de vencimentos próximos -->
                <?php if (!empty($mensalistas_vencendo)): ?>
                    <?php 
                    $vencidos = 0;
                    $vencendo = 0;
                    foreach ($mensalistas_vencendo as $m) {
                        $dataFim = new DateTime($m['data_fim']);
                        $hoje = new DateTime();
                        if ($dataFim < $hoje) {
                            $vencidos++;
                        } else {
                            $vencendo++;
                        }
                    }
                    ?>
                    <div class="alert <?php echo $vencidos > 0 ? 'alert-danger' : 'alert-warning'; ?> alert-dismissible fade show" role="alert">
                        <i class="fas <?php echo $vencidos > 0 ? 'fa-times-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                        <strong><?php echo $vencidos > 0 ? 'Urgente!' : 'Atenção!'; ?></strong> 
                        <?php if ($vencidos > 0): ?>
                            <?php echo $vencidos; ?> mensalista(s) com mensalidade vencida
                            <?php if ($vencendo > 0): ?>
                                e <?php echo $vencendo; ?> vencendo em até 5 dias
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo $vencendo; ?> mensalista(s) com vencimento em até 5 dias
                        <?php endif; ?>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estatísticas rápidas -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo $total_records; ?></h5>
                                <p class="card-text">Total de Mensalistas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success"><?php echo count(array_filter($mensalistas, function($m) { return $m['status'] === 'ativo'; })); ?></h5>
                                <p class="card-text">Ativos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><?php echo count($mensalistas_vencendo); ?></h5>
                                <p class="card-text">Vencendo/Vencidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-info"><?php echo count(array_filter($mensalistas, function($m) { return !empty($m['vaga_numero']); })); ?></h5>
                                <p class="card-text">Com Vaga Fixa</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros Avançados -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtros
                            <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosAvancados">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </h6>
                    </div>
                    <div class="collapse" id="filtrosAvancados">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Nome, CPF ou placa" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Todos</option>
                                        <option value="ativo" <?php echo $status_filter === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inativo" <?php echo $status_filter === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                        <option value="suspenso" <?php echo $status_filter === 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="vaga" class="form-label">Vaga</label>
                                    <select class="form-select" id="vaga" name="vaga">
                                        <option value="">Todas</option>
                                        <option value="com_vaga" <?php echo $vaga_filter === 'com_vaga' ? 'selected' : ''; ?>>Com vaga fixa</option>
                                        <option value="sem_vaga" <?php echo $vaga_filter === 'sem_vaga' ? 'selected' : ''; ?>>Sem vaga fixa</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Aplicar filtros">
                                            <i class="fas fa-search me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div class="mt-2">
                                <a href="?" class="btn btn-sm btn-outline-secondary"
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="Limpar todos os filtros">
                                    <i class="fas fa-eraser me-1"></i>Limpar Filtros
                                </a>
                                <button class="btn btn-sm btn-outline-success" onclick="exportarCSV()"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Exportar dados para CSV">
                                    <i class="fas fa-file-csv me-1"></i>Exportar CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Mensalistas -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Telefone</th>
                                <th>Placa</th>
                                <th>Vaga</th>
                                <th>Valor Mensal</th>
                                <th>Período</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mensalistas as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['nome'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['cpf'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['telefone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(formatarPlaca($m['placa_veiculo'] ?? '')); ?></td>
                                    <td><?php echo $m['vaga_numero'] ? 'Vaga ' . $m['vaga_numero'] : 'Sem vaga fixa'; ?></td>
                                    <td>R$ <?php echo number_format($m['valor_mensal'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($m['data_inicio'])) . ' - ' . date('d/m/Y', strtotime($m['data_fim'])); ?></td>
                                    <td>
                                        <?php if ($m['status'] === 'ativo'): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php elseif ($m['status'] === 'suspenso'): ?>
                                            <span class="badge bg-warning">Suspenso</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary me-1" 
                                           data-bs-toggle="tooltip" data-bs-placement="top" title="Editar mensalista">
                                            <i class="fas fa-user-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja desativar este mensalista?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Desativar mensalista">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Paginação">
                        <ul class="pagination justify-content-center">
                            <!-- Primeira página -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Páginas numeradas -->
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Última página -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <div class="text-center text-muted">
                        Mostrando <?php echo ($offset + 1); ?> a <?php echo min($offset + $limit, $total_records); ?> de <?php echo $total_records; ?> registros
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal Mensalista -->
    <div class="modal fade" id="mensalistaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editMensalista ? 'Editar' : 'Novo'; ?> Mensalista</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editMensalista ? 'update' : 'create'; ?>">
                        <?php if ($editMensalista): ?>
                            <input type="hidden" name="id" value="<?php echo $editMensalista['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['nome']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cpf" class="form-label">CPF *</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" required value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['cpf']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['telefone']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['email']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="placa_veiculo" class="form-label">Placa do Veículo *</label>
                                    <input type="text" class="form-control" id="placa_veiculo" name="placa_veiculo" required value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['placa_veiculo']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="modelo_veiculo" class="form-label">Modelo</label>
                                    <input type="text" class="form-control" id="modelo_veiculo" name="modelo_veiculo" value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['modelo_veiculo']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cor_veiculo" class="form-label">Cor</label>
                                    <input type="text" class="form-control" id="cor_veiculo" name="cor_veiculo" value="<?php echo $editMensalista ? htmlspecialchars($editMensalista['cor_veiculo']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="valor_mensal" class="form-label">Valor Mensal *</label>
                                    <input type="number" step="0.01" class="form-control" id="valor_mensal" name="valor_mensal" required value="<?php echo $editMensalista ? $editMensalista['valor_mensal'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vaga_dedicada" class="form-label">Vaga Dedicada</label>
                                    <select class="form-control" id="vaga_dedicada" name="vaga_dedicada">
                                        <option value="">Sem vaga fixa</option>
                                        <?php foreach ($vagasDisponiveis as $vaga): ?>
                                            <option value="<?php echo $vaga['id']; ?>" <?php echo ($editMensalista && $editMensalista['vaga_fixa_id'] == $vaga['id']) ? 'selected' : ''; ?>>
                                                Vaga <?php echo $vaga['numero']; ?> - <?php echo ucfirst($vaga['tipo']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="data_inicio" class="form-label">Data Início *</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" required value="<?php echo $editMensalista ? $editMensalista['data_inicio'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="data_fim" class="form-label">Data Fim *</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" required value="<?php echo $editMensalista ? $editMensalista['data_fim'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($editMensalista): ?>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo ($editMensalista['status'] === 'ativo') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="ativo">
                                                        Ativo
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas <?php echo $editMensalista ? 'fa-save' : 'fa-user-plus'; ?> me-1"></i><?php echo $editMensalista ? 'Atualizar' : 'Cadastrar'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para validar CPF
        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]/g, '');
            if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
            
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = 11 - (soma % 11);
            let dv1 = resto < 2 ? 0 : resto;
            
            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            resto = 11 - (soma % 11);
            let dv2 = resto < 2 ? 0 : resto;
            
            return dv1 == parseInt(cpf.charAt(9)) && dv2 == parseInt(cpf.charAt(10));
        }
        
        // Função para validar email
        function validarEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Função para validar placa
        function validarPlaca(placa) {
            placa = placa.replace(/[^A-Z0-9]/g, '').toUpperCase();
            return /^[A-Z]{3}[0-9]{4}$/.test(placa) || /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/.test(placa);
        }
        
        // Máscara para CPF com validação
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
            
            // Validação em tempo real
            const feedback = e.target.parentNode.querySelector('.invalid-feedback') || 
                           e.target.parentNode.appendChild(document.createElement('div'));
            feedback.className = 'invalid-feedback';
            
            if (value.length === 14) {
                if (validarCPF(value)) {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    feedback.textContent = '';
                } else {
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                    feedback.textContent = 'CPF inválido';
                }
            } else {
                e.target.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
            }
        });
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4,5})(\d{4})$/, '$1-$2');
            e.target.value = value;
        });
        
        // Máscara para placa com validação
        document.getElementById('placa_veiculo').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (value.length > 3) {
                value = value.substring(0, 3) + '-' + value.substring(3, 7);
            }
            e.target.value = value;
            
            // Validação em tempo real
            const feedback = e.target.parentNode.querySelector('.invalid-feedback') || 
                           e.target.parentNode.appendChild(document.createElement('div'));
            feedback.className = 'invalid-feedback';
            
            if (value.length >= 7) {
                if (validarPlaca(value)) {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    feedback.textContent = '';
                } else {
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                    feedback.textContent = 'Placa inválida';
                }
            } else {
                e.target.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
            }
        });
        
        // Validação de email em tempo real
        document.getElementById('email').addEventListener('input', function(e) {
            const feedback = e.target.parentNode.querySelector('.invalid-feedback') || 
                           e.target.parentNode.appendChild(document.createElement('div'));
            feedback.className = 'invalid-feedback';
            
            if (e.target.value && !validarEmail(e.target.value)) {
                e.target.classList.remove('is-valid');
                e.target.classList.add('is-invalid');
                feedback.textContent = 'Email inválido';
            } else if (e.target.value) {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
                feedback.textContent = '';
            } else {
                e.target.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
            }
        });
        
        // Validação de datas
        document.getElementById('data_inicio').addEventListener('change', validarDatas);
        document.getElementById('data_fim').addEventListener('change', validarDatas);
        
        function validarDatas() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            
            if (dataInicio && dataFim) {
                if (new Date(dataInicio) >= new Date(dataFim)) {
                    document.getElementById('data_fim').classList.add('is-invalid');
                    let feedback = document.getElementById('data_fim').parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        document.getElementById('data_fim').parentNode.appendChild(feedback);
                    }
                    feedback.textContent = 'Data fim deve ser posterior à data início';
                } else {
                    document.getElementById('data_fim').classList.remove('is-invalid');
                    document.getElementById('data_fim').classList.add('is-valid');
                    const feedback = document.getElementById('data_fim').parentNode.querySelector('.invalid-feedback');
                    if (feedback) feedback.textContent = '';
                }
            }
        }
        
        // Função para exportar CSV
        function exportarCSV() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }
        
        // Auto-save no localStorage para recuperar dados em caso de erro
        const form = document.querySelector('#mensalistaModal form');
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    localStorage.setItem('mensalista_' + this.name, this.value);
                });
                
                // Recuperar dados salvos
                const savedValue = localStorage.getItem('mensalista_' + input.name);
                if (savedValue && !input.value) {
                    input.value = savedValue;
                }
            });
        }
        
        // Limpar modal ao fechar
        document.getElementById('mensalistaModal').addEventListener('hidden.bs.modal', function() {
            if (!window.location.search.includes('edit=')) {
                this.querySelector('form').reset();
                // Limpar localStorage
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('mensalista_')) {
                        localStorage.removeItem(key);
                    }
                });
                // Remover classes de validação
                this.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                    el.classList.remove('is-valid', 'is-invalid');
                });
            }
        });
        
        // Confirmação antes de sair com dados não salvos
        let formChanged = false;
        if (form) {
            form.addEventListener('input', () => formChanged = true);
            form.addEventListener('submit', () => formChanged = false);
        }
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Se houve processamento do formulário de atualização, limpar URL imediatamente
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update'): ?>
            // Remover parâmetro edit da URL imediatamente
            const url = new URL(window.location);
            url.searchParams.delete('edit');
            window.history.replaceState({}, document.title, url.pathname + url.search);
            
            // Ocultar mensagem temporariamente se existir
            const alertElement = document.querySelector('.alert');
            if (alertElement) {
                alertElement.style.display = 'none';
                // Mostrar mensagem após um delay
                setTimeout(function() {
                    alertElement.style.display = 'block';
                    alertElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        <?php endif; ?>
        
        // Abrir modal para edição se parâmetro edit estiver presente
        <?php if ($editMensalista && !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update')): ?>
            var modal = new bootstrap.Modal(document.getElementById('mensalistaModal'));
            modal.show();
        <?php endif; ?>
        
        // Tooltip para botões
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Database.php';

$auth = new Auth();
$auth->requireAdmin();
$user = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

$sucesso = '';
$erro = '';

// Buscar configurações de tarifas atuais
$query = "SELECT chave, valor FROM configuracoes WHERE chave LIKE 'tarifa_%' ORDER BY chave";
$stmt = $conn->prepare($query);
$stmt->execute();
$tarifas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Buscar faixas de tarifas personalizadas
$query_faixas = "SELECT chave, valor FROM configuracoes WHERE chave LIKE 'faixa_tarifa_%' ORDER BY chave";
$stmt_faixas = $conn->prepare($query_faixas);
$stmt_faixas->execute();
$faixas_raw = $stmt_faixas->fetchAll(PDO::FETCH_KEY_PAIR);

// Organizar faixas em array estruturado
$faixas_tarifas = [];
foreach ($faixas_raw as $chave => $valor) {
    if (preg_match('/faixa_tarifa_(\d+)_(tempo|valor)/', $chave, $matches)) {
        $indice = $matches[1];
        $tipo = $matches[2];
        if (!isset($faixas_tarifas[$indice])) {
            $faixas_tarifas[$indice] = ['tempo' => '', 'valor' => ''];
        }
        $faixas_tarifas[$indice][$tipo] = $valor;
    }
}

// Ordenar faixas por índice
ksort($faixas_tarifas);

// Se não há faixas, criar algumas padrão baseadas na imagem
if (empty($faixas_tarifas)) {
    $faixas_tarifas = [
        1 => ['tempo' => '30', 'valor' => '4.00'],
        2 => ['tempo' => '60', 'valor' => '8.00'],
        3 => ['tempo' => '90', 'valor' => '11.00'],
        4 => ['tempo' => '120', 'valor' => '14.00'],
        5 => ['tempo' => '150', 'valor' => '17.00'],
        6 => ['tempo' => '180', 'valor' => '20.00'],
        7 => ['tempo' => '210', 'valor' => '23.00'],
        8 => ['tempo' => '240', 'valor' => '26.00'],
        9 => ['tempo' => '270', 'valor' => '29.00'],
        10 => ['tempo' => '300', 'valor' => '32.00']
    ];
}

// Configurações padrão de tarifas
$tarifas_padrao = [
    'tarifa_tipo' => 'fracao', // fracao ou hora
    'tarifa_valor_fracao' => '5.00',
    'tarifa_tempo_fracao' => '15', // minutos
    'tarifa_valor_hora' => '8.00',
    'tarifa_valor_diaria' => '25.00',
    'tarifa_tolerancia' => '10', // minutos de tolerância
    'tarifa_minutos_cortesia' => '15', // minutos de cortesia
    'tarifa_minutos_excedentes' => '5', // minutos excedentes tolerados
    // Configurações de horário noturno removidas
    'tarifa_desconto_mensalista' => '0', // desconto percentual para mensalistas
];

// Mesclar com configurações padrão
$tarifas = array_merge($tarifas_padrao, $tarifas);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Validar dados
        $tarifa_tolerancia = intval($_POST['tarifa_tolerancia'] ?? 0);
        $tarifa_minutos_cortesia = intval($_POST['tarifa_minutos_cortesia'] ?? 0);
        $tarifa_minutos_excedentes = intval($_POST['tarifa_minutos_excedentes'] ?? 0);
        
        // Validações
        if ($tarifa_minutos_cortesia < 0) {
            throw new Exception('Minutos de cortesia não podem ser negativos');
        }
        
        if ($tarifa_minutos_excedentes < 0) {
            throw new Exception('Minutos excedentes não podem ser negativos');
        }
        
        // Processar faixas de tarifas
        $faixas_post = $_POST['faixas'] ?? [];
        
        // Limpar faixas antigas
        $query_delete = "DELETE FROM configuracoes WHERE chave LIKE 'faixa_tarifa_%'";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->execute();
        
        // Salvar novas faixas
        foreach ($faixas_post as $indice => $faixa) {
            if (!empty($faixa['tempo']) && !empty($faixa['valor'])) {
                $tempo = intval($faixa['tempo']);
                $valor = floatval($faixa['valor']);
                
                if ($tempo > 0 && $valor > 0) {
                    // Salvar tempo da faixa
                    $query_faixa = "INSERT INTO configuracoes (chave, valor) VALUES (?, ?)";
                    $stmt_faixa = $conn->prepare($query_faixa);
                    $stmt_faixa->execute(["faixa_tarifa_{$indice}_tempo", $tempo]);
                    
                    // Salvar valor da faixa
                    $stmt_faixa->execute(["faixa_tarifa_{$indice}_valor", number_format($valor, 2, '.', '')]);
                }
            }
        }

        // Array com todas as configurações
        $configuracoes = [
            'tarifa_tolerancia' => $tarifa_tolerancia,
            'tarifa_minutos_cortesia' => $tarifa_minutos_cortesia,
            'tarifa_minutos_excedentes' => $tarifa_minutos_excedentes,
        ];
        
        // Inserir ou atualizar configurações
        foreach ($configuracoes as $chave => $valor) {
            $query = "INSERT INTO configuracoes (chave, valor) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = CURRENT_TIMESTAMP";
            $stmt = $conn->prepare($query);
            $stmt->execute([$chave, $valor]);
        }
        
        $conn->commit();
        $sucesso = 'Configurações de tarifas salvas com sucesso!';
        
        // Atualizar array de tarifas
        $tarifas = array_merge($tarifas, $configuracoes);
        
        // Recarregar faixas de tarifas após salvar
        $query_faixas = "SELECT chave, valor FROM configuracoes WHERE chave LIKE 'faixa_tarifa_%' ORDER BY chave";
        $stmt_faixas = $conn->prepare($query_faixas);
        $stmt_faixas->execute();
        $faixas_raw = $stmt_faixas->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $faixas_tarifas = [];
        foreach ($faixas_raw as $chave => $valor) {
            if (preg_match('/faixa_tarifa_(\d+)_(tempo|valor)/', $chave, $matches)) {
                $indice = $matches[1];
                $tipo = $matches[2];
                if (!isset($faixas_tarifas[$indice])) {
                    $faixas_tarifas[$indice] = ['tempo' => '', 'valor' => ''];
                }
                $faixas_tarifas[$indice][$tipo] = $valor;
            }
        }
        ksort($faixas_tarifas);
        
    } catch (Exception $e) {
        $conn->rollBack();
        $erro = 'Erro ao salvar configurações: ' . $e->getMessage();
    }
}

// Definir título da página
$page_title = 'Gestão de Tarifas';

// Incluir header comum
require_once __DIR__ . '/includes/header.php';
?>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-calculator me-2"></i>Gestão de Tarifas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>Imprimir
                        </button>
                    </div>
                </div>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($sucesso); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- Configurações de Cortesia e Tolerância -->
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-clock-fill me-2"></i>Cortesia e Tolerância
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="tarifa_minutos_cortesia" class="form-label">Minutos de Cortesia</label>
                                        <input type="number" class="form-control" id="tarifa_minutos_cortesia" 
                                               name="tarifa_minutos_cortesia" min="0" max="60" 
                                               value="<?php echo htmlspecialchars($tarifas['tarifa_minutos_cortesia']); ?>">
                                        <div class="form-text">Tempo gratuito inicial (cliente não paga se ficar apenas este tempo)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tarifa_minutos_excedentes" class="form-label">Minutos Excedentes Tolerados</label>
                                        <input type="number" class="form-control" id="tarifa_minutos_excedentes" 
                                               name="tarifa_minutos_excedentes" min="0" max="30" 
                                               value="<?php echo htmlspecialchars($tarifas['tarifa_minutos_excedentes']); ?>">
                                        <div class="form-text">Minutos extras que não são cobrados (ex: 1h05min = 1h se tolerância for 5min)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tarifa_tolerancia" class="form-label">Tolerância Geral (min)</label>
                                        <input type="number" class="form-control" id="tarifa_tolerancia" 
                                               name="tarifa_tolerancia" min="0" max="30" 
                                               value="<?php echo htmlspecialchars($tarifas['tarifa_tolerancia']); ?>">
                                        <div class="form-text">Tolerância geral do sistema em minutos</div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Exemplo:</strong> Com 15min de cortesia e 5min de tolerância excedente:
                                        <ul class="mb-0 mt-2">
                                            <li>0-15min: Gratuito (cortesia)</li>
                                            <li>16-30min: Cobra 1 fração</li>
                                            <li>1h05min: Cobra apenas 1h (tolerância excedente)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

    <!-- Tabela de Faixas de Tarifas -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>Tabela de Faixas de Tarifas
                    </h5>
                    <div>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="adicionarFaixa()">
                            <i class="bi bi-plus-circle me-1"></i>Adicionar Faixa
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="preencherExemplo()">
                            <i class="bi bi-clipboard-data me-1"></i>Exemplo Ágape
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Como funciona:</strong> Configure faixas de tempo específicas com valores fixos. 
                        Por exemplo: "Até 30 minutos = R$ 4,00", "Até 1 hora = R$ 8,00", etc.
                        O sistema cobrará o valor da faixa correspondente ao tempo de permanência.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tabela-faixas">
                            <thead class="table-light">
                                <tr>
                                    <th width="200">Tempo Limite (minutos)</th>
                                    <th width="200">Valor (R$)</th>
                                    <th width="300">Descrição</th>
                                    <th width="100">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="faixas-tbody">
                                <?php foreach ($faixas_tarifas as $indice => $faixa): ?>
                                <tr data-indice="<?php echo $indice; ?>">
                                    <td>
                                        <input type="number" class="form-control" 
                                               name="faixas[<?php echo $indice; ?>][tempo]" 
                                               value="<?php echo htmlspecialchars($faixa['tempo']); ?>" 
                                               min="1" placeholder="Ex: 30" onchange="atualizarDescricao(this)">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" 
                                               name="faixas[<?php echo $indice; ?>][valor]" 
                                               value="<?php echo htmlspecialchars($faixa['valor']); ?>" 
                                               step="0.01" min="0" placeholder="Ex: 4.00">
                                    </td>
                                    <td>
                                        <span class="text-muted descricao-faixa">
                                            <?php 
                                            $tempo = intval($faixa['tempo']);
                                            if ($tempo >= 60) {
                                                $horas = floor($tempo / 60);
                                                $minutos = $tempo % 60;
                                                if ($minutos > 0) {
                                                    echo "Até {$horas}h{$minutos}min";
                                                } else {
                                                    echo "Até {$horas}h";
                                                }
                                            } else {
                                                echo "Até {$tempo} minutos";
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="removerFaixa(<?php echo $indice; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-lightbulb me-1"></i>
                            <strong>Dica:</strong> Ordene as faixas do menor para o maior tempo. 
                            O sistema aplicará o valor da primeira faixa que atender ao tempo de permanência.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calculator me-2"></i>Simulador de Cobrança
                    </h5>
                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="simular_tempo" class="form-label">Tempo de Permanência</label>
                                            <input type="number" class="form-control" id="simular_tempo" 
                                                   placeholder="Minutos" min="1">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-primary d-block" onclick="simularCobranca()">
                                                <i class="bi bi-calculator me-1"></i>Simular
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Valor Calculado</label>
                                            <div class="form-control bg-light" id="resultado_simulacao">R$ 0,00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="configuracoes.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Salvar Configurações
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação do formulário
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Simulador de cobrança usando faixas de tarifas
        function simularCobranca() {
            const tempo = parseInt(document.getElementById('simular_tempo').value);
            if (!tempo || tempo <= 0) {
                alert('Por favor, insira um tempo válido em minutos.');
                return;
            }

            const minutosCortesia = parseInt(document.getElementById('tarifa_minutos_cortesia').value) || 0;
            const minutosExcedentes = parseInt(document.getElementById('tarifa_minutos_excedentes').value) || 0;

            let valor = 0;

            // Verificar cortesia
            if (tempo <= minutosCortesia) {
                valor = 0;
            } else {
                const tempoCobravel = tempo - minutosCortesia;
                
                // Buscar valor nas faixas de tarifas
                const faixas = [];
                const inputs = document.querySelectorAll('#faixas-tbody tr');
                
                inputs.forEach(row => {
                    const tempoInput = row.querySelector('input[name*="[tempo]"]');
                    const valorInput = row.querySelector('input[name*="[valor]"]');
                    
                    if (tempoInput && valorInput && tempoInput.value && valorInput.value) {
                        faixas.push({
                            tempo: parseInt(tempoInput.value),
                            valor: parseFloat(valorInput.value)
                        });
                    }
                });
                
                // Ordenar faixas por tempo
                faixas.sort((a, b) => a.tempo - b.tempo);
                
                // Encontrar faixa aplicável
                for (const faixa of faixas) {
                    if (tempoCobravel <= faixa.tempo) {
                        valor = faixa.valor;
                        break;
                    }
                }
                
                // Se não encontrou faixa, usar a última
                if (valor === 0 && faixas.length > 0) {
                    valor = faixas[faixas.length - 1].valor;
                }
            }

            document.getElementById('resultado_simulacao').textContent = 
                'R$ ' + valor.toFixed(2).replace('.', ',');
        }

        // Funções para gerenciar faixas de tarifas
        let proximoIndice = <?php echo max(array_keys($faixas_tarifas)) + 1; ?>;

        function adicionarFaixa() {
            const tbody = document.getElementById('faixas-tbody');
            const novaLinha = document.createElement('tr');
            novaLinha.setAttribute('data-indice', proximoIndice);
            
            novaLinha.innerHTML = `
                <td>
                    <input type="number" class="form-control" 
                           name="faixas[${proximoIndice}][tempo]" 
                           min="1" placeholder="Ex: 30" onchange="atualizarDescricao(this)">
                </td>
                <td>
                    <input type="number" class="form-control" 
                           name="faixas[${proximoIndice}][valor]" 
                           step="0.01" min="0" placeholder="Ex: 4.00">
                </td>
                <td>
                    <span class="text-muted descricao-faixa">-</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="removerFaixa(${proximoIndice})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(novaLinha);
            proximoIndice++;
        }

        function removerFaixa(indice) {
            const linha = document.querySelector(`tr[data-indice="${indice}"]`);
            if (linha) {
                linha.remove();
            }
        }

        function atualizarDescricao(input) {
            const tempo = parseInt(input.value);
            const descricaoSpan = input.closest('tr').querySelector('.descricao-faixa');
            
            if (tempo > 0) {
                if (tempo >= 60) {
                    const horas = Math.floor(tempo / 60);
                    const minutos = tempo % 60;
                    if (minutos > 0) {
                        descricaoSpan.textContent = `Até ${horas}h${minutos}min`;
                    } else {
                        descricaoSpan.textContent = `Até ${horas}h`;
                    }
                } else {
                    descricaoSpan.textContent = `Até ${tempo} minutos`;
                }
            } else {
                descricaoSpan.textContent = '-';
            }
        }

        function preencherExemplo() {
            // Limpar tabela atual
            const tbody = document.getElementById('faixas-tbody');
            tbody.innerHTML = '';
            
            // Dados do exemplo Ágape da imagem
            const exemploAgape = [
                {tempo: 30, valor: 4.00},
                {tempo: 60, valor: 8.00},
                {tempo: 90, valor: 11.00},
                {tempo: 120, valor: 14.00},
                {tempo: 150, valor: 17.00},
                {tempo: 180, valor: 20.00},
                {tempo: 210, valor: 23.00},
                {tempo: 240, valor: 26.00},
                {tempo: 270, valor: 29.00},
                {tempo: 300, valor: 32.00}
            ];
            
            proximoIndice = 1;
            exemploAgape.forEach((faixa, index) => {
                const indice = index + 1;
                const novaLinha = document.createElement('tr');
                novaLinha.setAttribute('data-indice', indice);
                
                let descricao;
                if (faixa.tempo >= 60) {
                    const horas = Math.floor(faixa.tempo / 60);
                    const minutos = faixa.tempo % 60;
                    if (minutos > 0) {
                        descricao = `Até ${horas}h${minutos}min`;
                    } else {
                        descricao = `Até ${horas}h`;
                    }
                } else {
                    descricao = `Até ${faixa.tempo} minutos`;
                }
                
                novaLinha.innerHTML = `
                    <td>
                        <input type="number" class="form-control" 
                               name="faixas[${indice}][tempo]" 
                               value="${faixa.tempo}" 
                               min="1" placeholder="Ex: 30" onchange="atualizarDescricao(this)">
                    </td>
                    <td>
                        <input type="number" class="form-control" 
                               name="faixas[${indice}][valor]" 
                               value="${faixa.valor.toFixed(2)}" 
                               step="0.01" min="0" placeholder="Ex: 4.00">
                    </td>
                    <td>
                        <span class="text-muted descricao-faixa">${descricao}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="removerFaixa(${indice})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(novaLinha);
            });
            
            proximoIndice = exemploAgape.length + 1;
        }
    </script>
</body>
</html>
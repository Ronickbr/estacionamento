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

// Buscar configurações atuais
$query = "SELECT chave, valor FROM configuracoes ORDER BY chave";
$stmt = $conn->prepare($query);
$stmt->execute();
$configuracoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Configurações padrão
if (empty($configuracoes)) {
    $configs_padrao = [
        // Tarifas
        'tarifa_tipo' => 'fracao', // fracao ou hora
        'tarifa_valor_fracao' => '5.00',
        'tarifa_tempo_fracao' => '15', // minutos
        'tarifa_valor_hora' => '8.00',
        'tarifa_valor_diaria' => '25.00',
        'tarifa_tolerancia' => '10', // minutos de tolerância
        
        // Vagas
        'total_vagas' => '50',
        'vagas_comuns' => '40',
        'vagas_preferenciais' => '8',
        'vagas_pcd' => '2',
        
        // Dados do Sistema
        'sistema_nome' => 'ParkManager',
        'sistema_razao_social' => 'Estacionamento LTDA',
        'sistema_cnpj' => '00.000.000/0001-00',
        'sistema_endereco' => 'Rua das Flores, 123',
        'sistema_cidade' => 'São Paulo',
        'sistema_cep' => '01234-567',
        'sistema_telefone' => '(11) 99999-9999',
        'sistema_email' => 'contato@parkmanager.com',
        
        // Cupom/Ticket
        'cupom_cabecalho' => 'ESTACIONAMENTO PARKMANAGER',
        'cupom_rodape' => 'Obrigado pela preferência!',
        'cupom_observacoes' => 'Guarde este comprovante',
        'cupom_instrucao' => 'Instrumento a apresentacao deste\ncupom para a retirada do veiculo',
        'cupom_info_loja' => 'LOJA  04 4.52',
        'cupom_placa' => 'ABC-1234',
        'cupom_ticket' => '8238',
        'cupom_data' => '12/11/2015',
        'cupom_hora' => '17:54',
        'cupom_largura' => '40', // caracteres
        'cupom_logo_ativo' => '1'
    ];
    
    foreach ($configs_padrao as $chave => $valor) {
        $query_upsert = "INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor) 
                        ON DUPLICATE KEY UPDATE valor = COALESCE(valor, VALUES(valor))";
        $stmt_upsert = $conn->prepare($query_upsert);
        $stmt_upsert->bindParam(':chave', $chave);
        $stmt_upsert->bindParam(':valor', $valor);
        $stmt_upsert->execute();
    }
    
    $configuracoes = $configs_padrao;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $chave => $valor) {
            if ($chave !== 'salvar') {
                // Usar INSERT ... ON DUPLICATE KEY UPDATE para evitar erro de chave duplicada
                $query_upsert = "INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor) 
                                ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = CURRENT_TIMESTAMP";
                $stmt_upsert = $conn->prepare($query_upsert);
                $stmt_upsert->bindParam(':chave', $chave);
                $stmt_upsert->bindParam(':valor', $valor);
                $stmt_upsert->execute();
            }
        }
        
        $sucesso = 'Configurações salvas com sucesso!';
        
        // Recarregar configurações
        $stmt->execute();
        $configuracoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
    } catch (Exception $e) {
        $erro = 'Erro ao salvar configurações: ' . $e->getMessage();
    }
}

// Função para obter valor da configuração
function getConfig($key, $default = '') {
    global $configuracoes;
    return isset($configuracoes[$key]) ? $configuracoes[$key] : $default;
}
$page_title = 'Configurações';
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .config-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
        }
        .preview-cupom {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            text-align: left;
            white-space: pre;
            overflow: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-gear-fill me-2"></i>Configurações do Sistema</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo date('d/m/Y'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo $sucesso; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $erro; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">


                    <!-- Configurações de Vagas -->
                    <div class="config-section">
                        <h3 class="section-title">
                            <i class="bi bi-grid-3x3-gap me-2"></i>Configurações de Vagas
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="total_vagas" class="form-label">Total de Vagas</label>
                                    <input type="number" class="form-control" id="total_vagas" name="total_vagas" 
                                           value="<?php echo getConfig('total_vagas', '50'); ?>" min="1" onchange="updateVagasTotal()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="vagas_comuns" class="form-label">Vagas Comuns</label>
                                    <input type="number" class="form-control" id="vagas_comuns" name="vagas_comuns" 
                                           value="<?php echo getConfig('vagas_comuns', '40'); ?>" min="0" onchange="updateVagasTotal()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="vagas_preferenciais" class="form-label">Vagas Preferenciais</label>
                                    <input type="number" class="form-control" id="vagas_preferenciais" name="vagas_preferenciais" 
                                           value="<?php echo getConfig('vagas_preferenciais', '8'); ?>" min="0" onchange="updateVagasTotal()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="vagas_pcd" class="form-label">Vagas PCD</label>
                                    <input type="number" class="form-control" id="vagas_pcd" name="vagas_pcd" 
                                           value="<?php echo getConfig('vagas_pcd', '2'); ?>" min="0" onchange="updateVagasTotal()">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Distribuição:</strong> 
                            <span id="vagas-summary">
                                <?php 
                                $comuns = getConfig('vagas_comuns', '40');
                                $pref = getConfig('vagas_preferenciais', '8');
                                $pcd = getConfig('vagas_pcd', '2');
                                $total = $comuns + $pref + $pcd;
                                echo "$comuns comuns + $pref preferenciais + $pcd PCD = $total vagas";
                                ?>
                            </span>
                        </div>
                    </div>

                    <!-- Dados do Sistema -->
                    <div class="config-section">
                        <h3 class="section-title">
                            <i class="bi bi-building me-2"></i>Dados do Sistema
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sistema_nome" class="form-label">Nome do Sistema</label>
                                    <input type="text" class="form-control" id="sistema_nome" name="sistema_nome" 
                                           value="<?php echo getConfig('sistema_nome', 'ParkManager'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sistema_razao_social" class="form-label">Razão Social</label>
                                    <input type="text" class="form-control" id="sistema_razao_social" name="sistema_razao_social" 
                                           value="<?php echo getConfig('sistema_razao_social', 'Estacionamento LTDA'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sistema_cnpj" class="form-label">CNPJ</label>
                                    <input type="text" class="form-control" id="sistema_cnpj" name="sistema_cnpj" 
                                           value="<?php echo getConfig('sistema_cnpj', '00.000.000/0001-00'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sistema_telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="sistema_telefone" name="sistema_telefone" 
                                           value="<?php echo getConfig('sistema_telefone', '(11) 99999-9999'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sistema_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="sistema_email" name="sistema_email" 
                                           value="<?php echo getConfig('sistema_email', 'contato@parkmanager.com'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sistema_cep" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="sistema_cep" name="sistema_cep" 
                                           value="<?php echo getConfig('sistema_cep', '01234-567'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="sistema_endereco" class="form-label">Endereço</label>
                                    <input type="text" class="form-control" id="sistema_endereco" name="sistema_endereco" 
                                           value="<?php echo getConfig('sistema_endereco', 'Rua das Flores, 123'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sistema_cidade" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="sistema_cidade" name="sistema_cidade" 
                                           value="<?php echo getConfig('sistema_cidade', 'São Paulo'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configurações do Cupom -->
                    <div class="config-section">
                        <h3 class="section-title">
                            <i class="bi bi-receipt me-2"></i>Configurações do Cupom/Ticket
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="cupom_cabecalho" class="form-label">Cabeçalho do Cupom</label>
                                            <input type="text" class="form-control" id="cupom_cabecalho" name="cupom_cabecalho" 
                                                   value="<?php echo getConfig('cupom_cabecalho', 'ESTACIONAMENTO PARKMANAGER'); ?>" 
                                                   onchange="updatePreview()">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="cupom_rodape" class="form-label">Rodapé do Cupom</label>
                                            <input type="text" class="form-control" id="cupom_rodape" name="cupom_rodape" 
                                                   value="<?php echo getConfig('cupom_rodape', 'Obrigado pela preferência!'); ?>" 
                                                   onchange="updatePreview()">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="cupom_observacoes" class="form-label">Observações</label>
                                            <textarea class="form-control" id="cupom_observacoes" name="cupom_observacoes" 
                                                      rows="2" onchange="updatePreview()"><?php echo getConfig('cupom_observacoes', 'Guarde este comprovante'); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="cupom_instrucao" class="form-label">Instrução no Cupom</label>
                                            <textarea class="form-control" id="cupom_instrucao" name="cupom_instrucao" 
                                                      rows="2" onchange="updatePreview()"><?php echo getConfig('cupom_instrucao', 'Instrumento a apresentacao deste\ncupom para a retirada do veiculo'); ?></textarea>
                                            <small class="form-text text-muted">Esta instrução será exibida no cupom de entrada.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cupom_info_loja" class="form-label">Informação da Loja</label>
                                            <input type="text" class="form-control" id="cupom_info_loja" name="cupom_info_loja" 
                                                   value="<?php echo getConfig('cupom_info_loja', 'LOJA  04 4.52'); ?>" onchange="updatePreview()">
                                            <small class="form-text text-muted">Informação adicional que aparecerá no final do cupom.</small>
                                        </div>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Informação:</strong> O cupom utilizará automaticamente os dados do sistema (nome, CNPJ, endereço, telefone) e os dados da entrada do veículo (placa, ticket, data, hora).
                                    </div>
                                    
                                    <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cupom_largura" class="form-label">Largura (caracteres)</label>
                                            <select class="form-select" id="cupom_largura" name="cupom_largura" onchange="updatePreview()">
                                                <option value="32" <?php echo getConfig('cupom_largura') == '32' ? 'selected' : ''; ?>>32 caracteres</option>
                                                <option value="40" <?php echo getConfig('cupom_largura') == '40' ? 'selected' : ''; ?>>40 caracteres</option>
                                                <option value="48" <?php echo getConfig('cupom_largura') == '48' ? 'selected' : ''; ?>>48 caracteres</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="cupom_logo_ativo" name="cupom_logo_ativo" 
                                                       value="1" <?php echo getConfig('cupom_logo_ativo') == '1' ? 'checked' : ''; ?> onchange="updatePreview()">
                                                <label class="form-check-label" for="cupom_logo_ativo">
                                                    Incluir logo no cupom
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Preview do Cupom</label>
                                <div class="preview-cupom" id="cupom-preview">
                                    <!-- Preview será gerado via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <button type="submit" name="salvar" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-2"></i>Salvar Configurações
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        
        function updateVagasTotal() {
            const comuns = parseInt(document.getElementById('vagas_comuns').value) || 0;
            const pref = parseInt(document.getElementById('vagas_preferenciais').value) || 0;
            const pcd = parseInt(document.getElementById('vagas_pcd').value) || 0;
            const total = comuns + pref + pcd;
            
            document.getElementById('total_vagas').value = total;
            document.getElementById('vagas-summary').textContent = 
                `${comuns} comuns + ${pref} preferenciais + ${pcd} PCD = ${total} vagas`;
        }
        
        function updatePreview() {
            // Obter valores dos campos do formulário
            const largura = parseInt(document.getElementById('cupom_largura').value) || 40;
            const cabecalho = document.getElementById('cupom_cabecalho') ? document.getElementById('cupom_cabecalho').value : 'ESTACIONAMENTO PARKMANAGER';
            const rodape = document.getElementById('cupom_rodape') ? document.getElementById('cupom_rodape').value : 'Obrigado pela preferência!';
            const observacoes = document.getElementById('cupom_observacoes') ? document.getElementById('cupom_observacoes').value : 'Guarde este comprovante';
            const instrucao = document.getElementById('cupom_instrucao') ? document.getElementById('cupom_instrucao').value : 'Instrumento a apresentacao deste\ncupom para a retirada do veiculo';
            const infoLoja = document.getElementById('cupom_info_loja') ? document.getElementById('cupom_info_loja').value : 'LOJA  04 4.52';
            
            // Dados do sistema (valores atuais dos campos ou padrões)
            const razaoSocial = document.getElementById('sistema_razao_social') ? document.getElementById('sistema_razao_social').value : '<?php echo getConfig("sistema_razao_social", "ESTACIONAMENTO LTDA"); ?>';
            const cnpj = document.getElementById('sistema_cnpj') ? document.getElementById('sistema_cnpj').value : '<?php echo getConfig("sistema_cnpj", "00.000.000/0000-00"); ?>';
            const endereco = document.getElementById('sistema_endereco') ? document.getElementById('sistema_endereco').value : '<?php echo getConfig("sistema_endereco", "GONCALVES CHAVES 000"); ?>';
            const telefone = document.getElementById('sistema_telefone') ? document.getElementById('sistema_telefone').value : '<?php echo getConfig("sistema_telefone", "(00) 0000 0000"); ?>';
            
            // Dados simulados para o preview
            const placa = 'ABC-1234';
            const tipoVeiculo = 'CARRO';
            const ticket = '8238';
            const dataEntrada = new Date().toLocaleDateString('pt-BR');
            const horaEntrada = new Date().toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            
            let preview = '';
            
            // Cabeçalho personalizado
            if (cabecalho) {
                const cabecalhoCentralizado = cabecalho.length > largura ? cabecalho.substring(0, largura) : cabecalho.padStart(Math.floor((largura + cabecalho.length) / 2)).padEnd(largura);
                preview += cabecalhoCentralizado + '\n';
            }
            
            // Dados da empresa
            if (razaoSocial) {
                const razaoSocialFormatada = razaoSocial.length > largura ? razaoSocial.substring(0, largura) : razaoSocial;
                preview += razaoSocialFormatada + '\n';
            }
            if (cnpj) {
                preview += cnpj + '\n';
            }
            preview += '\n';
            if (endereco) {
                preview += endereco + '\n';
            }
            if (telefone) {
                preview += telefone + '\n';
            }
            preview += '\n';
            preview += '='.repeat(largura) + '\n';
            preview += '\n';
            
            // Placa centralizada
            const placaCentralizada = placa.padStart(Math.floor((largura + placa.length) / 2)).padEnd(largura);
            preview += placaCentralizada + '\n';
            preview += 'TESTE'.padStart(Math.floor((largura + 5) / 2)).padEnd(largura) + '\n';
            preview += '\n';
            
            // Informações do veículo
            preview += 'Tipo: ' + tipoVeiculo + '\n';
            preview += 'Ticket: ' + ticket + '\n';
            preview += 'Data: ' + dataEntrada + '\n';
            preview += 'Hora: ' + horaEntrada + '\n';
            preview += '\n';
            
            // Instruções
            if (instrucao) {
                preview += instrucao.replace(/\\n/g, '\n') + '\n';
                preview += '\n';
            }
            
            // Código de barras simulado
            preview += '|'.repeat(Math.floor(largura * 0.9)) + '\n';
            preview += '\n';
            
            // Informações da loja
            if (infoLoja) {
                preview += infoLoja + '\n';
            }
            
            // Rodapé
            if (rodape) {
                preview += '\n';
                const rodapeCentralizado = rodape.length > largura ? rodape.substring(0, largura) : rodape.padStart(Math.floor((largura + rodape.length) / 2)).padEnd(largura);
                preview += rodapeCentralizado + '\n';
            }
            
            // Observações
            if (observacoes) {
                preview += '\n';
                preview += observacoes + '\n';
            }
            
            // Atualizar o preview
            const previewElement = document.getElementById('cupom-preview');
            if (previewElement) {
                previewElement.textContent = preview;
            }
        }
        
        // Inicializar campos na carga da página
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
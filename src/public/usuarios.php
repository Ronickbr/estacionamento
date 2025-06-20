<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Database.php';

$auth = new Auth();
$auth->requireAdmin(); // Apenas admins podem gerenciar usuários
$user = $auth->getCurrentUser();

$database = new Database();
$conn = $database->getConnection();

$message = '';
$messageType = 'success';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $senha = $_POST['senha'];
                $tipo = $_POST['tipo'];
                
                // Validações
                if (empty($nome) || empty($email) || empty($senha)) {
                    $message = 'Todos os campos são obrigatórios.';
                    $messageType = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email inválido.';
                    $messageType = 'danger';
                } elseif (strlen($senha) < 6) {
                    $message = 'A senha deve ter pelo menos 6 caracteres.';
                    $messageType = 'danger';
                } else {
                    // Verificar se email já existe
                    $query = "SELECT id FROM usuarios WHERE email = :email";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'Este email já está cadastrado.';
                        $messageType = 'danger';
                    } else {
                        if ($auth->createUser($nome, $email, $senha, $tipo)) {
                            $message = 'Usuário cadastrado com sucesso!';
                            $messageType = 'success';
                        } else {
                            $message = 'Erro ao cadastrar usuário.';
                            $messageType = 'danger';
                        }
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $tipo = $_POST['tipo'];
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                
                if (empty($nome) || empty($email)) {
                    $message = 'Nome e email são obrigatórios.';
                    $messageType = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email inválido.';
                    $messageType = 'danger';
                } else {
                    // Verificar se email já existe para outro usuário
                    $query = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'Este email já está sendo usado por outro usuário.';
                        $messageType = 'danger';
                    } else {
                        $query = "UPDATE usuarios SET nome = :nome, email = :email, tipo = :tipo, ativo = :ativo WHERE id = :id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':nome', $nome);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':tipo', $tipo);
                        $stmt->bindParam(':ativo', $ativo);
                        $stmt->bindParam(':id', $id);
                        
                        if ($stmt->execute()) {
                            $message = 'Usuário atualizado com sucesso!';
                            $messageType = 'success';
                        } else {
                            $message = 'Erro ao atualizar usuário.';
                            $messageType = 'danger';
                        }
                    }
                }
                break;
                
            case 'change_password':
                $id = $_POST['id'];
                $nova_senha = $_POST['nova_senha'];
                
                if (strlen($nova_senha) < 6) {
                    $message = 'A nova senha deve ter pelo menos 6 caracteres.';
                    $messageType = 'danger';
                } else {
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $query = "UPDATE usuarios SET senha = :senha WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':senha', $senha_hash);
                    $stmt->bindParam(':id', $id);
                    
                    if ($stmt->execute()) {
                        $message = 'Senha alterada com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Erro ao alterar senha.';
                        $messageType = 'danger';
                    }
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['id'];
                $query = "UPDATE usuarios SET ativo = NOT ativo WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    $message = 'Status do usuário alterado com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Erro ao alterar status do usuário.';
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Buscar usuários
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tipo_filter = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT id, nome, email, tipo, ativo, created_at FROM usuarios WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (nome LIKE :search OR email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($tipo_filter)) {
    $query .= " AND tipo = :tipo";
    $params[':tipo'] = $tipo_filter;
}

if ($status_filter !== '') {
    $query .= " AND ativo = :status";
    $params[':status'] = $status_filter;
}

$query .= " ORDER BY nome ASC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$usuarios = $stmt->fetchAll();

// Obter usuário para edição
$editUser = null;
if (isset($_GET['edit'])) {
    $query = "SELECT id, nome, email, tipo, ativo FROM usuarios WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $editUser = $stmt->fetch();
}
$page_title = 'Usuarios';
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestão de Usuários</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Adicionar novo usuário">
                            <i class="fas fa-user-plus me-1"></i> Novo Usuário
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Nome ou email" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos</option>
                                    <option value="admin" <?php echo $tipo_filter === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="operador" <?php echo $tipo_filter === 'operador' ? 'selected' : ''; ?>>Operador</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="mt-2">
                            <a href="?" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eraser me-1"></i>Limpar Filtros
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Lista de usuários -->
                <div class="card">
                    <div class="card-header">
                        <h5>Usuários Cadastrados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Cadastrado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <?php if ($u['tipo'] === 'admin'): ?>
                                                    <span class="badge bg-danger">Administrador</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Operador</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($u['ativo']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                                            <td>
                                                <a href="?edit=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary me-1"
                                                   data-bs-toggle="tooltip" title="Editar usuário">
                                                    <i class="fas fa-user-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                                                        data-bs-toggle="modal" data-bs-target="#passwordModal"
                                                        data-user-id="<?php echo $u['id']; ?>"
                                                        data-user-name="<?php echo htmlspecialchars($u['nome']); ?>"
                                                        title="Alterar senha">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($u['id'] != $user['id']): // Não pode desativar a si mesmo ?>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Tem certeza que deseja <?php echo $u['ativo'] ? 'desativar' : 'ativar'; ?> este usuário?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $u['ativo'] ? 'danger' : 'success'; ?>"
                                                            title="<?php echo $u['ativo'] ? 'Desativar' : 'Ativar'; ?> usuário">
                                                        <i class="fas fa-<?php echo $u['ativo'] ? 'user-times' : 'user-check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Novo/Editar Usuário -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo $editUser ? 'Editar Usuário' : 'Novo Usuário'; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                        <?php if ($editUser): ?>
                            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required
                                   value="<?php echo $editUser ? htmlspecialchars($editUser['nome']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>">
                        </div>
                        
                        <?php if (!$editUser): ?>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                            <div class="form-text">Mínimo de 6 caracteres</div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Usuário *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="operador" <?php echo ($editUser && $editUser['tipo'] === 'operador') ? 'selected' : ''; ?>>Operador</option>
                                <option value="admin" <?php echo ($editUser && $editUser['tipo'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                        
                        <?php if ($editUser): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                       <?php echo $editUser['ativo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">
                                    Usuário ativo
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
                            <i class="fas <?php echo $editUser ? 'fa-save' : 'fa-user-plus'; ?> me-1"></i>
                            <?php echo $editUser ? 'Atualizar' : 'Cadastrar'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Alterar Senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="id" id="password_user_id">
                        
                        <p>Alterando senha do usuário: <strong id="password_user_name"></strong></p>
                        
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha *</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
                            <div class="form-text">Mínimo de 6 caracteres</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i>Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Modal de alterar senha
        var passwordModal = document.getElementById('passwordModal');
        passwordModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            var userName = button.getAttribute('data-user-name');
            
            document.getElementById('password_user_id').value = userId;
            document.getElementById('password_user_name').textContent = userName;
            document.getElementById('nova_senha').value = '';
        });

        // Auto-abrir modal de edição se há parâmetro edit
        <?php if ($editUser): ?>
        var userModal = new bootstrap.Modal(document.getElementById('userModal'));
        userModal.show();
        <?php endif; ?>
    </script>
</body>
</html>
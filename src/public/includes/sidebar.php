<?php
// Sidebar component
// Requires: $user array with user information
// Requires: current page name for active state

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <i class="bi bi-car-front text-white" style="font-size: 2rem;"></i>
            <h5 class="text-white mt-2">RNB ParkManager</h5>
            <small class="text-white-50">Olá, <?php echo htmlspecialchars($user['nome']); ?></small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'movimentacao.php') ? 'active' : ''; ?>" href="movimentacao.php">
                    <i class="bi bi-car-front-fill me-2"></i>
                    Movimentação
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'mensalistas.php') ? 'active' : ''; ?>" href="mensalistas.php">
                    <i class="bi bi-people-fill me-2"></i>
                    Mensalistas
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'relatorios.php') ? 'active' : ''; ?>" href="relatorios.php">
                    <i class="bi bi-graph-up me-2"></i>
                    Relatórios
                </a>
            </li>
            <?php if (isset($user['tipo']) && $user['tipo'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['configuracoes.php', 'tarifas.php', 'usuarios.php'])) ? 'active' : ''; ?>" 
                   href="#" data-bs-toggle="collapse" data-bs-target="#configSubmenu" 
                   aria-expanded="<?php echo (in_array($current_page, ['configuracoes.php', 'tarifas.php', 'usuarios.php'])) ? 'true' : 'false'; ?>">
                    <i class="bi bi-gear-fill me-2"></i>
                    Configurações
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse <?php echo (in_array($current_page, ['configuracoes.php', 'tarifas.php', 'usuarios.php'])) ? 'show' : ''; ?>" id="configSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'configuracoes.php') ? 'active' : ''; ?>" href="configuracoes.php">
                                <i class="fas fa-sliders-h me-2"></i>
                                Gerais
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'tarifas.php') ? 'active' : ''; ?>" href="tarifas.php">
                                <i class="fas fa-calculator me-2"></i>
                                Gestão de Tarifas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'usuarios.php') ? 'active' : ''; ?>" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>
                                Usuários
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-3">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Sair
                </a>
            </li>
        </ul>
    </div>
</nav>
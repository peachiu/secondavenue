<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Garantir que temos acesso à DB e Auth se ainda não estiverem carregados
if (!isset($pdo)) {
    $path_to_db = __DIR__ . '/../../backend/db.php';
    if (file_exists($path_to_db))
        require_once $path_to_db;
}
if (!function_exists('getCurrentUser')) {
    $path_to_auth = __DIR__ . '/../../backend/auth.php';
    if (file_exists($path_to_auth))
        require_once $path_to_auth;
}

$currentUser = getCurrentUser();
$searchQuery = $_GET['q'] ?? '';

// Contar mensagens não lidas
$unreadCount = 0;
// Contar itens no carrinho (Exemplo via Sessão)
$cartCount = 0;
$cartItems = [];

if (isset($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
    // Em um cenário real, iríamos buscar os detalhes dos produtos à DB
}

if ($currentUser) {
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt->execute([$currentUser['id']]);
            $unreadCount = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Silently fail if table doesn't exist or error
        }
    }
}
?>
<!-- Navbar Flutuante Global -->
<nav class="navbar navbar-expand-lg sticky-top custom-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="index.php">SECOND AVENUE</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Barra de Pesquisa Central -->
            <form action="index.php" method="GET"
                class="d-flex mx-auto my-2 my-lg-0 w-50 position-relative search-container">
                <input class="form-control rounded-pill ps-4 pe-5" type="search" name="q"
                    placeholder="O que procuras hoje?" aria-label="Pesquisar"
                    value="<?= htmlspecialchars($searchQuery) ?>">
                <button class="btn btn-link position-absolute end-0 top-0 text-muted" type="submit"
                    style="margin-right: 10px;">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-3">

                <!-- Carrinho -->
                <li class="nav-item dropdown cart-dropdown">
                    <a class="nav-link position-relative" href="cart.php" id="cartDropdown" role="button"
                        aria-expanded="false">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <!-- Dropdown do Carrinho (Hover) -->
                    <div class="dropdown-menu dropdown-menu-end apple-dropdown p-3" aria-labelledby="cartDropdown"
                        style="min-width: 300px;">
                        <h6 class="dropdown-header fw-bold">O Teu Carrinho</h6>
                        <?php if ($cartCount > 0): ?>
                            <!-- Exemplo de itens (placeholder) -->
                            <p class="text-muted small text-center my-3">Itens no carrinho...</p>
                            <a href="cart.php" class="btn btn-primary btn-sm w-100 rounded-pill">Ver Carrinho</a>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-shopping-basket fa-2x text-muted mb-2"></i>
                                <p class="small text-muted mb-0">O carrinho está vazio.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>

                <?php if ($currentUser): ?>
                    <!-- Notificações -->
                    <li class="nav-item dropdown notification-dropdown">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($currentUser['role'] === 'professional' && $currentUser['verified'] == 0): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle">
                                    <span class="visually-hidden">Awaiting Approval</span>
                                </span>
                            <?php elseif ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unreadCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end apple-dropdown" aria-labelledby="notifDropdown">
                            <h6 class="dropdown-header fw-bold">Notificações</h6>

                            <?php if ($currentUser['role'] === 'professional' && $currentUser['verified'] == 0): ?>
                                <div
                                    class="dropdown-item px-3 py-2 bg-warning bg-opacity-10 border-start border-warning border-4">
                                    <p class="mb-0 small fw-bold text-warning-emphasis">Aprovação Pendente</p>
                                    <p class="mb-0 small text-muted">A tua conta é "Comunidade" até ser aprovada pelo admin.</p>
                                </div>
                            <?php endif; ?>

                            <?php if ($unreadCount > 0): ?>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="chat.php">
                                    <i class="fas fa-envelope text-primary"></i>
                                    <div>
                                        <span class="d-block small fw-bold"><?= $unreadCount ?> novas mensagens</span>
                                        <span class="d-block x-small text-muted">Ver conversas</span>
                                    </div>
                                </a>
                            <?php elseif (!($currentUser['role'] === 'professional' && $currentUser['verified'] == 0)): ?>
                                <p class="text-center text-muted small my-3">Sem novas notificações.</p>
                            <?php endif; ?>
                        </div>
                    </li>

                    <!-- Perfil Dropdown (Apple Style) -->
                    <li class="nav-item dropdown profile-dropdown">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" id="profileDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-circle">
                                <?php
                                $initials = strtoupper(substr($currentUser['name'], 0, 1));
                                echo $initials;
                                ?>
                            </div>
                            <!-- <span class="d-none d-lg-block fw-medium"><?= htmlspecialchars($currentUser['name']) ?></span> -->
                            <i class="fas fa-chevron-down fa-xs text-muted"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end apple-dropdown" aria-labelledby="profileDropdown">
                            <li class="px-3 py-2 border-bottom mb-2">
                                <span class="d-block fw-bold text-dark"><?= htmlspecialchars($currentUser['name']) ?></span>
                                <span
                                    class="d-block x-small text-muted"><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
                            </li>
                            <li><a class="dropdown-item" href="dashboard.php"><i
                                        class="fas fa-columns me-2 text-secondary"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="dashboard.php?view=listings"><i
                                        class="fas fa-box-open me-2 text-secondary"></i> Meus Artigos</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <div class="dropdown-item d-flex justify-content-between align-items-center gap-3"
                                    id="themeToggleBtn" style="cursor: pointer;">
                                    <span><i class="fas fa-moon me-2 text-secondary"></i> Tema Escuro</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch">
                                    </div>
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger fw-bold" href="../backend/logout.php"><i
                                        class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                        </ul>
                    </li>

                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-dark rounded-pill px-4" href="login.php">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary rounded-pill px-4 fw-bold" href="register.php">Registar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>





<script>
    // Theme Toggle Logic
    document.addEventListener('DOMContentLoaded', () => {
        const themeSwitch = document.getElementById('themeSwitch');
        const html = document.documentElement;

        // Check local storage or system preference
        const savedTheme = localStorage.getItem('theme');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
            html.setAttribute('data-theme', 'dark');
            if (themeSwitch) themeSwitch.checked = true;
        }

        if (themeSwitch) {
            themeSwitch.addEventListener('change', () => {
                if (themeSwitch.checked) {
                    html.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    html.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                }
            });

            // Allow clicking the row too
            document.getElementById('themeToggleBtn').addEventListener('click', (e) => {
                if (e.target !== themeSwitch) {
                    themeSwitch.checked = !themeSwitch.checked;
                    themeSwitch.dispatchEvent(new Event('change'));
                }
            });
        }
    });
</script>
<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/auth.php'; // Para verificações de interação mais tarde

// Procurar anúncios
$currentUser = getCurrentUser();

// Obter valores de filtragem
$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Consulta Base
$sql = "SELECT listings.*, users.name as seller_name, users.is_verified, users.role as seller_role 
        FROM listings 
        JOIN users ON listings.user_id = users.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (listings.title LIKE ? OR listings.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category && $category !== 'Todas') {
    $sql .= " AND listings.category = ?";
    $params[] = $category;
}

// Lógica de Ordenação
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$sql .= " LIMIT 20";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-pt" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Second Avenue - Avenida trust-worthy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Barra de Navegação Flutuante -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">SECOND AVENUE</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <form action="index.php" method="GET" class="d-flex mx-auto my-2 my-lg-0 w-50 position-relative">
                    <input class="form-control rounded-pill ps-4 pe-5" type="search" name="q" placeholder="O que procuras hoje?"
                        aria-label="Pesquisar" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-link position-absolute end-0 top-0 text-muted" type="submit"
                        style="margin-right: 10px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-3">
                    <?php if ($currentUser): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-user-circle fa-lg me-1"></i> <?= htmlspecialchars($currentUser['name']) ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-danger btn-sm rounded-pill" href="../backend/logout.php">Sair</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm rounded-pill fw-bold" href="login.php">Entrar</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php" title="Carrinho">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Secção Principal (Hero) -->
    <header class="hero">
        <div class="container">
            <h1 class="display-3 mb-3">Tecnologia com <span class="text-accent">Confiança</span></h1>
            <p class="lead text-secondary mb-5 w-75 mx-auto">A plataforma verificada para comprar e vender material
                informático. Junte-se à nossa comunidade de vendedores profissionais.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#produtos" class="btn btn-primary btn-lg px-5">Explorar Ofertas</a>
                <?php if ($currentUser): ?>
                    <a href="create_listing.php" class="btn btn-accent btn-lg px-5">Vender Artigo</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-outline-dark btn-lg px-5">Registar Conta</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Filtros e Barra de Pesquisa Secundária -->
    <div class="container mb-5">
        <div class="card p-3 border-0 shadow-sm rounded-4 bg-white filter-card" style="margin-top: -40px; position: relative; z-index: 10;">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-lg-4 col-md-12">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 ps-3">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-0 bg-transparent ps-0" 
                               placeholder="Pesquisar produtos..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-4">
                    <select name="category" class="form-select border-0 bg-light rounded-pill">
                        <option value="Todas">Todas as Categorias</option>
                        <option value="Computadores" <?= $category == 'Computadores' ? 'selected' : '' ?>>Computadores</option>
                        <option value="Smartphones" <?= $category == 'Smartphones' ? 'selected' : '' ?>>Smartphones</option>
                        <option value="Áudio" <?= $category == 'Áudio' ? 'selected' : '' ?>>Áudio</option>
                        <option value="Componentes" <?= $category == 'Componentes' ? 'selected' : '' ?>>Componentes</option>
                        <option value="Outros" <?= $category == 'Outros' ? 'selected' : '' ?>>Outros</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-4">
                    <select name="sort" class="form-select border-0 bg-light rounded-pill">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mais recentes</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Preço: Baixo a Alto</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Preço: Alto a Baixo</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Aplicar</button>
                </div>
            </form>
        </div>
    </div>

    <section class="container my-5" id="produtos">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold fs-3">
                <?php if ($search || ($category && $category !== 'Todas')): ?>
                    Resultados da Pesquisa
                <?php else: ?>
                    Recém Chegados
                <?php endif; ?>
            </h2>
            <?php if ($search || ($category && $category !== 'Todas')): ?>
                <a href="index.php" class="text-decoration-none text-muted small">Limpar Filtros</a>
            <?php else: ?>
                <a href="search.php" class="text-decoration-none text-primary fw-bold">Ver tudo <i class="fas fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php if (count($listings) > 0): ?>
                <?php foreach ($listings as $item): ?>
                    <div class="col">
                        <div class="card h-100 product-card">
                            <a href="product.php?id=<?= $item['id'] ?>" class="stretched-link"></a>
                            <div class="card-img-top d-flex align-items-center justify-content-center text-secondary bg-light">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                        alt="<?= htmlspecialchars($item['title']) ?>" class="w-100 h-100 object-fit-cover">
                                <?php else: ?>
                                    <i class="fas fa-box-open fa-3x opacity-50"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span
                                        class="badge bg-light text-dark border"><?= htmlspecialchars($item['category'] ?? 'Geral') ?></span>
                                    <?php if ($item['is_verified']): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary" title="Vendedor Profissional"><i
                                                class="fas fa-check-circle"></i> Profissional</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"
                                            title="Vendedor Comunitário">Comunidade</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title fw-bold text-truncate"><?= htmlspecialchars($item['title']) ?></h5>
                                <p class="card-text text-muted small user-select-none">
                                    <i class="fas fa-user mb-1"></i> <?= htmlspecialchars($item['seller_name']) ?>
                                    <?php if ($item['is_verified']): ?><i class="fas fa-check-circle verified-tick"
                                            title="Verificado"></i><?php endif; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="h5 mb-0 fw-bold"><?= number_format($item['price'], 2) ?> €</span>
                                    <button class="btn btn-outline-primary btn-sm rounded-circle">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Ainda não há produtos listados. Seja o primeiro!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="text-white py-5">
        <div class="container text-center">
            <h4 class="fw-bold mb-3">SECOND AVENUE</h4>
            <div class="d-flex justify-content-center gap-4 mb-4">
                <a href="#" class="text-white-50 hover-white"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" class="text-white-50 hover-white"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" class="text-white-50 hover-white"><i class="fab fa-facebook fa-lg"></i></a>
            </div>
            <p class="mb-1 text-white-50">&copy; 2026 Second Avenue. Desenvolvido por <a
                    href="https://github.com/peachiu" class="text-white-50 text-decoration-none fw-bold">peachiu ✿</a>
            </p>
            <small class="text-white-50">PAP - Curso Profissional Técnico de Gestão e Programação de Sistemas
                Informáticos</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
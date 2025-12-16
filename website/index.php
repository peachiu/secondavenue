<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/auth.php'; // For interaction checks later

// Fetch recent listings
$stmt = $pdo->query("SELECT listings.*, users.name as seller_name, users.is_verified, users.role as seller_role 
                     FROM listings 
                     JOIN users ON listings.user_id = users.id 
                     ORDER BY created_at DESC LIMIT 8");
$listings = $stmt->fetchAll();

$currentUser = getCurrentUser();
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

    <!-- Floating Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="index.php">SECOND AVENUE</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <form class="d-flex mx-auto my-2 my-lg-0 w-50 position-relative">
                    <input class="form-control rounded-pill ps-4 pe-5" type="search" placeholder="O que procuras hoje?"
                        aria-label="Pesquisar">
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

    <!-- Hero Section -->
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

    <section class="container my-5" id="produtos">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold fs-3">Recém Chegados</h2>
            <a href="search.php" class="text-decoration-none text-primary fw-bold">Ver tudo <i
                    class="fas fa-arrow-right"></i></a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php if (count($listings) > 0): ?>
                <?php foreach ($listings as $item): ?>
                    <div class="col">
                        <div class="card h-100">
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
            <p class="mb-1 text-white-50">&copy; 2026 Second Avenue.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
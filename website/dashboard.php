<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../backend/db.php';
require_once '../backend/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];

// Procurar anúncios do utilizador
$stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$myListings = $stmt->fetchAll();

// Contar estatísticas
$listingCount = count($myListings);
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Second Avenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg border-bottom px-4"
        style="margin-top: 0; border-radius: 0; width: 100%; max-width: 100%; background: white;">
        <a class="navbar-brand" href="index.php">SECOND AVENUE</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="fw-bold"><?= htmlspecialchars($user['name']) ?></span>
            <a href="../backend/logout.php" class="btn btn-sm btn-outline-danger rounded-pill">Sair</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row g-4">
            <!-- Barra Lateral / Perfil -->
            <div class="col-md-3">
                <div class="card p-4 text-center">
                    <div class="mb-3">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-primary"
                            style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">
                        <?= htmlspecialchars($user['name']) ?>
                        <?php if ($user['verified']): ?>
                            <i class="fas fa-check-circle verified-tick" title="Verificado"></i>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-secondary mb-3 align-self-center"><?= ucfirst($user['role']) ?></span>

                    <hr>
                    <div class="text-start">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Anúncios</span>
                            <span class="fw-bold"><?= $listingCount ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Vendas</span>
                            <span class="fw-bold">0</span><!-- Espaço Reservado -->
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a href="create_listing.php" class="btn btn-primary fw-bold">Criar Anúncio</a>
                    <a href="chat.php" class="btn btn-outline-dark fw-bold">Mensagens</a>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-md-9">
                <h3 class="fw-bold mb-4">Meus Anúncios</h3>

                <?php if (count($myListings) > 0): ?>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Produto</th>
                                        <th>Preço</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myListings as $listing): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-3"
                                                        style="width: 40px; height: 40px;">
                                                        <i class="fas fa-box text-secondary"></i>
                                                    </div>
                                                    <span
                                                        class="fw-bold text-dark"><?= htmlspecialchars($listing['title']) ?></span>
                                                </div>
                                            </td>
                                            <td class="fw-bold"><?= number_format($listing['price'], 2) ?> €</td>
                                            <td>
                                                <span class="badge <?= $listing['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $listing['stock'] ?> un
                                                </span>
                                            </td>
                                            <td>Ativo</td>
                                            <td>
                                                <button class="btn btn-sm btn-light text-primary"><i
                                                        class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm btn-light text-danger"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-cart-2130356-1800917.png"
                            alt="Empty" style="width: 150px; opacity: 0.5;">
                        <p class="text-muted mt-3">Ainda não tens anúncios ativos.</p>
                        <a href="create_listing.php" class="btn btn-sm btn-primary">Começar a Vender</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <footer class="text-white py-5">
            <div class="container text-center">
                <h4 class="fw-bold mb-3">SECOND AVENUE</h4>
                <div class="d-flex justify-content-center gap-4 mb-4">
                    <a href="#" class="text-white-50 hover-white"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white-50 hover-white"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white-50 hover-white"><i class="fab fa-facebook fa-lg"></i></a>
                </div>
                <p class="mb-1 text-white-50">&copy; 2026 Second Avenue. Desenvolvido por <a
                        href="https://github.com/peachiu" class="text-white-50 text-decoration-none fw-bold">peachiu
                        ✿</a></p>
                <small class="text-white-50">PAP - Curso Profissional Técnico de Gestão e Programação de Sistemas
                    Informáticos</small>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
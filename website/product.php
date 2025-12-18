<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../backend/db.php';
require_once '../backend/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Procurar detalhes do anúncio + informação do Vendedor
$stmt = $pdo->prepare("
    SELECT l.*, u.name as seller_name, u.location as seller_location, u.is_verified, u.id as seller_id
    FROM listings l
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "Produto não encontrado.";
    exit;
}

// Tratar lógica de Avaliações mais tarde (espaço reservado)
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['title']) ?> - Second Avenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row g-5">
            <!-- Imagem do Produto -->
            <div class="col-md-6">
                <div class="card p-5 text-center align-items-center justify-content-center shadow-sm"
                    style="height: 400px; background-color: var(--bg-card);">
                    <?php if ($product['image_url']): ?>
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Produto" class="img-fluid"
                            style="max-height: 100%;">
                    <?php else: ?>
                        <i class="fas fa-box-open fa-5x text-secondary opacity-50"></i>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalhes -->
            <div class="col-md-6">
                <div class="mb-3">
                    <span
                        class="badge bg-primary bg-opacity-10 text-primary"><?= htmlspecialchars($product['category']) ?></span>
                    <?php if ($product['tags']): ?>
                        <?php foreach (explode(',', $product['tags']) as $tag): ?>
                            <span class="badge bg-light text-secondary border"><?= trim(htmlspecialchars($tag)) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h1 class="fw-bold mb-2"><?= htmlspecialchars($product['title']) ?></h1>
                <div class="h3 text-primary fw-bold mb-4"><?= number_format($product['price'], 2) ?> €</div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-user-circle fa-2x text-muted me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">
                                    <?= htmlspecialchars($product['seller_name']) ?>
                                    <?php if ($product['is_verified']): ?>
                                        <i class="fas fa-check-circle verified-tick" title="Vendedor Verificado"></i>
                                    <?php endif; ?>
                                </h6>
                                <small
                                    class="text-muted"><?= htmlspecialchars($product['seller_location'] ?? 'Localização desconhecida') ?></small>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="chat.php?receiver_id=<?= $product['seller_id'] ?>&listing_id=<?= $product['id'] ?>"
                                class="btn btn-primary py-2 rounded-pill fw-bold">
                                <i class="fas fa-comments me-2"></i> Contactar Vendedor
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold">Descrição</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>

                <!-- Informação de Stock -->
                <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="fw-bold">Disponibilidade:</span>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success">Em Stock (<?= $product['stock'] ?>)</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Esgotado</span>
                    <?php endif; ?>
                </div>

                <hr>

                <!-- Secção de Avaliações -->
                <div class="mt-4">
                    <h5 class="fw-bold mb-3">Avaliações</h5>

                    <?php
                    // Tratar Submissão de Avaliação
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
                        if (isLoggedIn()) {
                            $rating = (int) $_POST['rating'];
                            $comment = $_POST['comment'] ?? '';
                            $stmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, listing_id, seller_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$_SESSION['user_id'], $product['id'], $product['seller_id'], $rating, $comment]);
                            echo "<div class='alert alert-success py-2'>Obrigado pela sua avaliação!</div>";
                        } else {
                            echo "<div class='alert alert-warning py-2'>Faça login para avaliar.</div>";
                        }
                    }

                    // Procurar Avaliações
                    $stmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE listing_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$product['id']]);
                    $reviews = $stmt->fetchAll();
                    ?>

                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="card mb-2 border-0 shadow-sm">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold small"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                                        <div class="text-warning small">
                                            <?php for ($i = 0; $i < $review['rating']; $i++)
                                                echo '<i class="fas fa-star"></i>'; ?>
                                        </div>
                                    </div>
                                    <p class="mb-0 small text-muted"><?= htmlspecialchars($review['comment']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small">Sem avaliações ainda.</p>
                    <?php endif; ?>

                    <?php if (isLoggedIn() && $_SESSION['user_id'] != $product['seller_id']): ?>
                        <form method="POST" class="mt-3">
                            <label class="form-label small fw-bold">Deixar Avaliação</label>
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <select name="rating" class="form-select form-select-sm w-auto rounded-pill" required>
                                    <option value="5">5 Estrelas</option>
                                    <option value="4">4 Estrelas</option>
                                    <option value="3">3 Estrelas</option>
                                    <option value="2">2 Estrelas</option>
                                    <option value="1">1 Estrela</option>
                                </select>
                                <input type="text" name="comment" class="form-control form-control-sm rounded-pill"
                                    placeholder="O seu comentário...">
                                <button type="submit" class="btn btn-sm btn-dark rounded-circle"><i
                                        class="fas fa-arrow-right"></i></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
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
                    href="https://github.com/peachiu" class="text-white-50 text-decoration-none fw-bold">peachiu ✿</a>
            </p>
            <small class="text-white-50">PAP - Curso Profissional Técnico de Gestão e Programação de Sistemas
                Informáticos</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
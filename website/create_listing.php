<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? 'Geral';
    $stock = $_POST['stock'] ?? 1;
    $tags = $_POST['tags'] ?? '';
    $image_url = $_POST['image_url'] ?? ''; // Using URL for simplicity

    $userId = $_SESSION['user_id'];

    if (!$title || !$price) {
        $error = "Preencha os campos obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, title, description, price, category, stock, tags, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $description, $price, $category, $stock, $tags, $image_url]);
            $success = "Anúncio criado com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao criar anúncio: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Anúncio - Second Avenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="fw-bold">Novo Anúncio</h2>
                        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill">Voltar</a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold small">Título do Produto</label>
                                <input type="text" name="title" class="form-control rounded-pill" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Preço (€)</label>
                                <input type="number" step="0.01" name="price" class="form-control rounded-pill" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Stock (Unidades)</label>
                                <input type="number" name="stock" class="form-control rounded-pill" value="1" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Categoria</label>
                                <select name="category" class="form-select rounded-pill">
                                    <option value="Computadores">Computadores</option>
                                    <option value="Smartphones">Smartphones</option>
                                    <option value="Audio">Áudio</option>
                                    <option value="Componentes">Componentes</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Tags (separadas por vírgula)</label>
                                <input type="text" name="tags" class="form-control rounded-pill" placeholder="Ex: Gaming, Novo, Apple">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Link da Imagem</label>
                                <input type="url" name="image_url" class="form-control rounded-pill" placeholder="https://exemplo.com/imagem.jpg">
                                <div class="form-text">Para demonstração, use um URL de imagem pública.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Descrição</label>
                                <textarea name="description" class="form-control" rows="5" style="border-radius: 20px;"></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-pill">Publicar Anúncio</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

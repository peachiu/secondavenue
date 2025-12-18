<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../backend/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'community';
    $location = $_POST['location'] ?? '';

    // Validação básica
    if (strlen($password) < 6) {
        $error = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } else {
        $result = registerUser($name, $email, $password, $role, $location);
        if ($result['success']) {
            $success = 'Conta criada com sucesso! ';
            if ($role === 'professional') {
                $success .= 'A sua conta profissional ficará pendente de verificação.';
            } else {
                // Login automático para conta comum
                loginUser($email, $password);
                header('Location: index.php');
                exit;
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar - Second Avenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light py-5">

    <div class="card shadow-lg p-4" style="width: 100%; max-width: 500px;">
        <div class="card-body">
            <div class="text-center mb-4">
                <a href="index.php" class="text-decoration-none h3 fw-bold text-dark">SECOND AVENUE</a>
                <p class="text-muted small mt-2">Crie a sua conta e comece a negociar.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success py-2 small">
                    <?= htmlspecialchars($success) ?>
                    <div class="mt-2"><a href="login.php" class="fw-bold">Ir para Login</a></div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nome Completo</label>
                    <input type="text" name="name" class="form-control rounded-pill" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="email" name="email" class="form-control rounded-pill" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Localização (Cidade/Distrito)</label>
                    <input type="text" name="location" class="form-control rounded-pill" placeholder="Ex: Lisboa"
                        required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-2">Tipo de Conta</label>
                    <div class="d-flex gap-3">
                        <div class="form-check card p-3 flex-fill text-center" style="cursor: pointer;">
                            <input class="form-check-input float-none mx-auto mb-2" type="radio" name="role"
                                value="community" id="roleComm" checked>
                            <label class="form-check-label d-block small fw-bold stretched-link"
                                for="roleComm">Comunidade</label>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Para vendas ocasionais</span>
                        </div>
                        <div class="form-check card p-3 flex-fill text-center" style="cursor: pointer;">
                            <input class="form-check-input float-none mx-auto mb-2" type="radio" name="role"
                                value="professional" id="rolePro">
                            <label class="form-check-label d-block small fw-bold stretched-link"
                                for="rolePro">Profissional</label>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Requer Verificação</span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Palavra-passe</label>
                    <input type="password" name="password" class="form-control rounded-pill" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg fs-6 fw-bold">Criar Conta</button>
                </div>
            </form>

            <div class="text-center mt-3 small">
                <span class="text-muted">Já tem conta?</span>
                <a href="login.php" class="text-primary fw-bold text-decoration-none">Entrar</a>
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
            <p class="mb-1 text-white-50">&copy; 2026 Second Avenue. Desenvolvido por <a href="https://github.com/peachiu" class="text-white-50 text-decoration-none fw-bold">peachiu ✿</a></p>
            <small class="text-white-50">PAP - Curso Profissional Técnico de Gestão e Programação de Sistemas Informáticos</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
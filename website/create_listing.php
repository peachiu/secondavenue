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

$currentUser = getCurrentUser(); // Buscar dados atualizados (para verificar role)

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? 'Geral';
    $condition = $_POST['condition'] ?? 'Usado - Bom';
    
    // Lógica do Stock
    // Se for user da comunidade e checkbox não marcada, stock = 1
    // Se for checkada ou user profissional, usa o valor do input
    $stock = 1;
    $stockInput = $_POST['stock'] ?? 1;
    $manageStock = isset($_POST['manage_stock']) ? true : false;
    
    if ($currentUser['role'] === 'professional' || $manageStock) {
        $stock = $stockInput;
    }

    $tags = $_POST['tags'] ?? '';
    
    // Upload de Imagem
    $image_url = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileDetails = $_FILES['image'];
        $uploadDir = __DIR__ . '/productimages/';
        
        // Garantir diretório (já criado via comando, mas por segurança)
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = strtolower(pathinfo($fileDetails['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($ext, $allowed)) {
            // Gerar nome base seguro
            $safeTitle = preg_replace('/[^a-zA-Z0-9]/', '', $title);
            if (empty($safeTitle)) $safeTitle = 'product';
            
            $filename = $safeTitle . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            // Renomear se existir (iPhone-1.png, iPhone-2.png)
            $counter = 1;
            while (file_exists($targetPath)) {
                $filename = $safeTitle . '-' . $counter . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                $counter++;
            }
            
            if (move_uploaded_file($fileDetails['tmp_name'], $targetPath)) {
                $image_url = 'productimages/' . $filename;
            } else {
                $error = "Falha ao gravar a imagem no servidor.";
            }
        } else {
            $error = "Formato de imagem inválido. Use JPG, PNG ou WEBP.";
        }
    } elseif (!empty($_POST['image_url_text'])) {
        // Fallback para URL externa se fornecida
        $image_url = $_POST['image_url_text'];
    }

    if (!$title || !$price) {
        $error = "Preencha os campos obrigatórios (Título e Preço).";
    } elseif (empty($image_url) && !isset($_POST['image_url_text'])) {
       // Opcional: forçar imagem? Vamos deixar passar sem imagem se quiserem,
       // mas o código layout assume imagem.
    }

    if (!$error) {
        try {
            $userId = $_SESSION['user_id'];
            // SQL Atualizado com item_condition
            $sql = "INSERT INTO listings (user_id, title, description, price, category, item_condition, stock, tags, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $title, $description, $price, $category, $condition, $stock, $tags, $image_url]);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card shadow-lg p-5 border-0 rounded-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="fw-bold">Novo Anúncio</h2>
                        <span class="badge bg-secondary rounded-pill px-3 py-2"><?php echo $currentUser['role'] === 'professional' ? 'Vendedor Profissional' : 'Vendedor Comunidade'; ?></span>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-4"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success rounded-4 d-flex justify-content-between align-items-center">
                            <span><?= htmlspecialchars($success) ?></span>
                            <a href="dashboard.php" class="btn btn-sm btn-success fw-bold rounded-pill">Ver Meus Anúncios</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <!-- Secção Esquerda -->
                            <div class="col-md-12">
                                <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">Detalhes do Produto</h5>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Título do Produto <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-lg rounded-pill border-0 px-4" style="background-color: var(--bg-body); color: var(--text-primary);" placeholder="Ex: iPhone 13 Pro Max" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Categoria</label>
                                <select name="category" class="form-select rounded-pill border-0 px-4" style="background-color: var(--bg-body); color: var(--text-primary);">
                                    <option value="Computadores">Computadores</option>
                                    <option value="Smartphones">Smartphones</option>
                                    <option value="Audio">Áudio</option>
                                    <option value="Componentes">Componentes</option>
                                    <option value="Consolas">Consolas</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Estado do Item</label>
                                <select name="condition" class="form-select rounded-pill border-0 px-4" style="background-color: var(--bg-body); color: var(--text-primary);">
                                    <option value="Novo/Nunca Aberto">Novo / Nunca Aberto</option>
                                    <option value="Usado - Como Novo">Usado - Como Novo</option>
                                    <option value="Usado - Bom">Usado - Bom</option>
                                    <option value="Usado - Razoável">Usado - Razoável</option>
                                    <option value="Usado - Mau Estado">Usado - Mau Estado</option>
                                    <option value="Não Funciona">Não Funciona / Para Peças</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Preço (€) <span class="text-danger">*</span></label>
                                <div class="input-group" style="background-color: var(--bg-body); border-radius: 50px; overflow: hidden;">
                                    <input type="number" step="0.01" name="price" class="form-control border-0 px-4" style="background-color: transparent; color: var(--text-primary);" placeholder="0.00" required>
                                    <span class="input-group-text border-0 bg-transparent text-secondary">€</span>
                                </div>
                            </div>

                            <!-- Lógica de Stock -->
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Stock</label>
                                <div class="p-3 rounded-4" style="background-color: var(--bg-body);">
                                    <?php if ($currentUser['role'] === 'community'): ?>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="manage_stock" id="manageStockToggle">
                                            <label class="form-check-label small" for="manageStockToggle">Tenho mais do que uma unidade (Alterar Stock)</label>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div id="stockInputArgs" class="<?php echo $currentUser['role'] === 'community' ? 'd-none' : ''; ?> mt-2">
                                        <input type="number" name="stock" class="form-control rounded-pill border-0 bg-white" value="1" min="1">
                                        <div class="form-text x-small ps-3">Quantidade disponível para venda imediata.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold small">Tags</label>
                                <input type="text" name="tags" class="form-control rounded-pill border-0 px-4" style="background-color: var(--bg-body); color: var(--text-primary);" placeholder="Ex: Gaming, Apple, 5G (separadas por vírgula)">
                            </div>

                            <!-- Upload de Imagem -->
                            <div class="col-12">
                                <label class="form-label fw-bold small">Fotografias</label>
                                <div class="card p-3 border-dashed text-center" style="border: 2px dashed var(--secondary-color); border-radius: 20px; background-color: var(--bg-body);">
                                    <input type="file" name="image" id="imageInput" class="form-control d-none" accept="image/*" onchange="previewImage(this)">
                                    <label for="imageInput" class="cursor-pointer" style="cursor: pointer;">
                                        <div id="imagePreviewContainer">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                            <p class="text-muted small mb-0">Carregar imagem do dispositivo</p>
                                        </div>
                                        <img id="preview" src="#" alt="Preview" class="d-none img-fluid rounded-3 mt-2" style="max-height: 200px;">
                                    </label>
                                </div>
                                <div class="mt-2 text-end">
                                    <button type="button" class="btn btn-link btn-sm text-decoration-none small text-muted" onclick="document.getElementById('urlInputDiv').classList.toggle('d-none')">
                                        Ou usar URL externa
                                    </button>
                                </div>
                                <div id="urlInputDiv" class="d-none mt-2">
                                    <input type="url" name="image_url_text" class="form-control rounded-pill border-0 px-4" style="background-color: var(--bg-body); color: var(--text-primary);" placeholder="https://...">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Descrição Detalhada</label>
                                <textarea name="description" class="form-control border-0 p-3" rows="5" style="border-radius: 20px; background-color: var(--bg-body); color: var(--text-primary);"></textarea>
                            </div>

                            <div class="col-12 mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold shadow-sm hover-scale">
                                    <i class="fas fa-paper-plane me-2"></i> Publicar Anúncio
                                </button>
                                <br>
                                <a href="dashboard.php" class="text-muted small text-decoration-none mt-3 d-inline-block">Cancelar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lógica para Stock Toggle
        const stockToggle = document.getElementById('manageStockToggle');
        const stockInput = document.getElementById('stockInputArgs');
        
        if (stockToggle) {
            stockToggle.addEventListener('change', function() {
                if (this.checked) {
                    stockInput.classList.remove('d-none');
                } else {
                    stockInput.classList.add('d-none');
                }
            });
        }

        // Preview da Imagem
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const container = document.getElementById('imagePreviewContainer');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    container.classList.add('d-none');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>

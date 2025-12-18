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
$currentUserId = $user['id'] ?? null;
$receiverId = $_GET['receiver_id'] ?? null;
$listingId = $_GET['listing_id'] ?? null;

// Tratamento de Ações (Bloquear, Apagar, Enviar)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = $_POST['receiver_id'] ?? null;
    $listingId = $_POST['listing_id'] ?? null;

    if ($action === 'send_message' && $targetId) {
        $content = trim($_POST['content'] ?? '');
        if ($content) {
            // Verificar Bloqueio
            $stmt = $pdo->prepare("SELECT id FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
            $stmt->execute([$targetId, $currentUserId]); // Se o recetor me bloqueou
            if ($stmt->fetch()) {
                $error = "Não pode enviar mensagens a este utilizador.";
            } else {
                // Verificar se EU bloqueie
                $stmt->execute([$currentUserId, $targetId]);
                if ($stmt->fetch()) {
                    $error = "Você bloqueou este utilizador. Desbloqueie para enviar mensagens.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, content) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$currentUserId, $targetId, $listingId ?: null, $content]);
                    header("Location: chat.php?receiver_id=$targetId");
                    exit;
                }
            }
        }
    } elseif ($action === 'block_user' && $targetId) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
            $stmt->execute([$currentUserId, $targetId]);
            $success = "Utilizador bloqueado.";
        } catch (Exception $e) {
            $error = "Erro ao bloquear.";
        }
    } elseif ($action === 'unblock_user' && $targetId) { // Adicionando Unblock por conveniência
        $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$currentUserId, $targetId]);
        $success = "Utilizador desbloqueado.";
    } elseif ($action === 'delete_conversation' && $targetId) {
        // Soft delete das mensagens para o utilizador atual
        // Se eu sou o remetente, marco deleted_by_sender
        $stmt = $pdo->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$currentUserId, $targetId]);
        
        // Se eu sou o recetor, marco deleted_by_receiver
        $stmt = $pdo->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE receiver_id = ? AND sender_id = ?");
        $stmt->execute([$currentUserId, $targetId]);
        
        $success = "Conversa apagada.";
        header("Location: chat.php"); // Voltar à raiz
        exit;
    }
}

// Procurar Conversas (Utilizadores com quem comunicou e NÃO APAGOU TUDO)
// Nota: Para simplificar, mostramos utilizadores com quem houve troca de mensagens visível
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name 
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE ((m.sender_id = ? AND m.deleted_by_sender = 0) 
       OR (m.receiver_id = ? AND m.deleted_by_receiver = 0)) 
       AND u.id != ?
");
$stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
$conversations = $stmt->fetchAll();

// Verificar estado de bloqueio para a interface
$isBlockedByMe = false;
$receiverName = '';
if ($receiverId) {
    // Buscar nome
    $stmtUser = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmtUser->execute([$receiverId]);
    $uData = $stmtUser->fetch();
    $receiverName = $uData['name'] ?? 'Utilizador';

    // Verificar se eu bloqueei
    $stmtBlock = $pdo->prepare("SELECT id FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmtBlock->execute([$currentUserId, $receiverId]);
    if ($stmtBlock->fetch()) {
        $isBlockedByMe = true;
    }
}

// Procurar Mensagens para a conversa atual (filtrando deletadas)
$messages = [];
if ($receiverId) {
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE ((sender_id = ? AND receiver_id = ? AND deleted_by_sender = 0) 
           OR (sender_id = ? AND receiver_id = ? AND deleted_by_receiver = 0))
        ORDER BY created_at ASC
    ");
    $stmt->execute([$currentUserId, $receiverId, $receiverId, $currentUserId]);
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Chat - Second Avenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-container { height: calc(100vh - 80px); }
        .message-box { overflow-y: auto; flex: 1; }
        .message-bubble { max-width: 75%; padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; }
        .sent { background-color: var(--primary-color); color: var(--bg-card); align-self: flex-end; border-bottom-right-radius: 5px; }
        .received { background-color: var(--bg-message-received, #f1f5f9); color: var(--text-primary); align-self: flex-start; border-bottom-left-radius: 5px; }
        
        /* Dark mode overrides for chat specific elements */
        [data-theme="dark"] .received {
            background-color: #334155; /* Slate 700 */
            color: #f1f5f9;
        }
        [data-theme="dark"] .sent {
            color: #0f172a; /* Dark text on white/light primary color in dark mode */
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid chat-container d-flex p-0">
        <!-- Barra Lateral -->
        <div class="border-end p-3 d-none d-md-block" style="width: 320px; overflow-y: auto; background-color: var(--bg-card);">
            <h5 class="fw-bold mb-4 px-2">Conversas</h5>
            <div class="list-group list-group-flush gap-2">
                <?php foreach ($conversations as $conv): ?>
                    <a href="?receiver_id=<?= $conv['id'] ?>"
                        class="list-group-item list-group-item-action rounded-3 border-0 <?= $receiverId == $conv['id'] ? 'active-chat' : '' ?>"
                        style="<?= $receiverId == $conv['id'] ? 'background-color: var(--secondary-color); color: white;' : 'background-color: transparent; color: var(--text-primary);' ?>">
                        <div class="d-flex align-items-center py-2">
                            <div class="avatar-circle me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                <?= strtoupper(substr($conv['name'], 0, 1)) ?>
                            </div>
                            <span class="text-truncate"><?= htmlspecialchars($conv['name']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($conversations)): ?>
                    <p class="text-muted small text-center mt-5">Sem conversas ativas.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Área de Chat -->
        <div class="flex-grow-1 d-flex flex-column" style="background-color: var(--bg-body);">
            <?php if ($receiverId): ?>
                <!-- Cabeçalho do Chat -->
                <div class="border-bottom p-3 d-flex justify-content-between align-items-center shadow-sm" style="z-index: 10; background-color: var(--bg-card);">
                    <div class="d-flex align-items-center">
                        <a href="chat.php" class="d-md-none me-3 text-reset"><i class="fas fa-arrow-left"></i></a>
                        <div class="avatar-circle me-2" style="width: 35px; height: 35px;">
                            <?= strtoupper(substr($receiverName, 0, 1)) ?>
                        </div>
                        <span class="fw-bold"><?= htmlspecialchars($receiverName) ?></span>
                        <?php if($isBlockedByMe): ?>
                            <span class="badge bg-danger ms-2">Bloqueado</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sm rounded-circle" data-bs-toggle="dropdown" style="background-color: var(--bg-body); color: var(--text-primary);">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 15px;">
                            <li>
                                <form method="POST">
                                    <input type="hidden" name="action" value="delete_conversation">
                                    <input type="hidden" name="receiver_id" value="<?= $receiverId ?>">
                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Tem a certeza que quer apagar esta conversa?');">
                                        <i class="fas fa-trash-alt me-2"></i> Apagar Conversa
                                    </button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST">
                                    <input type="hidden" name="receiver_id" value="<?= $receiverId ?>">
                                    <?php if ($isBlockedByMe): ?>
                                        <input type="hidden" name="action" value="unblock_user">
                                        <button class="dropdown-item" type="submit">
                                            <i class="fas fa-unlock me-2"></i> Desbloquear
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="block_user">
                                        <button class="dropdown-item text-warning" type="submit" onclick="return confirm('Este utilizador deixará ser capaz de lhe enviar mensagens.');">
                                            <i class="fas fa-ban me-2"></i> Bloquear
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Mensagens -->
                <div class="message-box p-4 d-flex flex-column">
                    <?php if ($error): ?>
                        <div class="alert alert-danger mx-auto w-75 rounded-pill text-center py-2 small mb-4 shadow-sm"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-bubble shadow-sm <?= $msg['sender_id'] == $currentUserId ? 'sent' : 'received' ?>">
                            <?= nl2br(htmlspecialchars($msg['content'])) ?>
                            <div class="text-end mt-1" style="font-size: 0.65rem; opacity: 0.7;">
                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted mt-5">
                            <i class="fas fa-comment-dots fa-3x mb-3 text-secondary opacity-25"></i>
                            <p class="small">Inicie a conversa com <?= htmlspecialchars($receiverName) ?>.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Input -->
                <div class="p-3 border-top" style="background-color: var(--bg-card);">
                    <?php if (!$isBlockedByMe): ?>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="receiver_id" value="<?= $receiverId ?>">
                            <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                            <input type="text" name="content" class="form-control rounded-pill border-0 px-4"
                                style="background-color: var(--bg-body); color: var(--text-primary);"
                                placeholder="Escreva uma mensagem..." autofocus autocomplete="off" required>
                            <button type="submit" class="btn btn-primary rounded-circle shadow-sm hover-scale" style="width: 48px; height: 48px;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0 border-0 rounded-pill text-center small">
                            <i class="fas fa-lock me-2"></i> Você bloqueou este utilizador.
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="d-flex h-100 align-items-center justify-content-center flex-column text-muted" style="background-color: var(--bg-card);">
                    <div class="rounded-circle p-4 mb-3" style="background-color: var(--bg-body);">
                        <i class="fas fa-comments fa-3x text-secondary"></i>
                    </div>
                    <h5 class="fw-bold" style="color: var(--text-primary);">As suas mensagens</h5>
                    <p class="text-secondary small">Selecione uma conversa para começar.</p>
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
                    href="https://github.com/peachiu" class="text-white-50 text-decoration-none fw-bold">peachiu ✿</a>
            </p>
            <small class="text-white-50">PAP - Curso Profissional Técnico de Gestão e Programação de Sistemas
                Informáticos</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$receiverId = $_GET['receiver_id'] ?? null;
$listingId = $_GET['listing_id'] ?? null;

// Send Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'], $_POST['receiver_id'])) {
    $content = trim($_POST['content']);
    $recv = $_POST['receiver_id'];
    $lst = $_POST['listing_id'] ?: null;

    if ($content) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$currentUserId, $recv, $lst, $content]);
        // Redirect to self to prevent resubmission
        header("Location: chat.php?receiver_id=$recv&listing_id=$lst");
        exit;
    }
}

// Fetch Conversations (Users communicated with)
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name 
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
");
$stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
$conversations = $stmt->fetchAll();

// Fetch Messages for current conversation
$messages = [];
if ($receiverId) {
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
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
        .chat-container {
            height: calc(100vh - 120px);
        }

        .message-box {
            overflow-y: auto;
            flex: 1;
        }

        .message-bubble {
            max-width: 75%;
            padding: 10px 15px;
            border-radius: 20px;
            margin-bottom: 10px;
        }

        .sent {
            background-color: var(--primary-color);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .received {
            background-color: #f1f5f9;
            color: var(--text-primary);
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
    </style>
</head>

<body class="bg-white">

    <nav class="navbar navbar-light border-bottom px-4 m-0 w-100" style="background: white; border-radius:0;">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-arrow-left"></i> Voltar</a>
        <span class="mx-auto fw-bold">Mensagens</span>
    </nav>

    <div class="container-fluid chat-container d-flex">
        <!-- Sidebar -->
        <div class="border-end p-3 d-none d-md-block" style="width: 300px;">
            <h5 class="fw-bold mb-3">Conversas</h5>
            <div class="list-group list-group-flush">
                <?php foreach ($conversations as $conv): ?>
                    <a href="?receiver_id=<?= $conv['id'] ?>"
                        class="list-group-item list-group-item-action <?= $receiverId == $conv['id'] ? 'active' : '' ?>">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary bg-opacity-10 rounded-circle d-flex justify-content-center align-items-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="fw-bold"><?= htmlspecialchars($conv['name']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($conversations)): ?>
                    <p class="text-muted small">Sem conversas ainda.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-grow-1 d-flex flex-column p-0">
            <?php if ($receiverId): ?>
                <div class="message-box p-4 d-flex flex-column">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-bubble <?= $msg['sender_id'] == $currentUserId ? 'sent' : 'received' ?>">
                            <?= nl2br(htmlspecialchars($msg['content'])) ?>
                            <div class="small opacity-50 text-end" style="font-size: 0.6rem; margin-top: 5px;">
                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-3 border-top">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="receiver_id" value="<?= $receiverId ?>">
                        <input type="hidden" name="listing_id" value="<?= $listingId ?>">
                        <input type="text" name="content" class="form-control rounded-pill"
                            placeholder="Escreva uma mensagem..." autofocus autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle" style="width: 45px; height: 45px;"><i
                                class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-flex h-100 align-items-center justify-content-center flex-column text-muted">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Selecione uma conversa para começar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>
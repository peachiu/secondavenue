<?php
require_once __DIR__ . '/backend/db.php';

try {
    // 1. Tabela de Utilizadores Bloqueados
    $sqlBlocked = "CREATE TABLE IF NOT EXISTS blocked_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        blocker_id INT NOT NULL,
        blocked_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_block (blocker_id, blocked_id)
    )";
    $pdo->exec($sqlBlocked);
    echo "Tabela 'blocked_users' criada ou já existente.\n";

    // 2. Adicionar colunas de apagado na tabela de mensagens (Soft Delete)
    // Verificar se já existe deleted_by_sender
    $stmt = $pdo->query("SHOW COLUMNS FROM messages LIKE 'deleted_by_sender'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN deleted_by_sender BOOLEAN DEFAULT FALSE");
        echo "Coluna 'deleted_by_sender' adicionada.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM messages LIKE 'deleted_by_receiver'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN deleted_by_receiver BOOLEAN DEFAULT FALSE");
        echo "Coluna 'deleted_by_receiver' adicionada.\n";
    }

} catch (PDOException $e) {
    echo "Erro SQL: " . $e->getMessage() . "\n";
}
?>
<?php
require_once __DIR__ . '/backend/db.php';
try {
    // Verificar se a coluna já existe para evitar erro
    $stmt = $pdo->query("SHOW COLUMNS FROM listings LIKE 'item_condition'");
    if ($stmt->fetch()) {
        echo "A coluna 'item_condition' já existe.\n";
    } else {
        $pdo->exec("ALTER TABLE listings ADD COLUMN item_condition VARCHAR(50) DEFAULT 'Usado'");
        echo "Coluna 'item_condition' adicionada com sucesso.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>

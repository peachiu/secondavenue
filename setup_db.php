<?php
require_once 'backend/db.php';

echo "Setting up database...<br>";

try {
    $sql = file_get_contents(__DIR__ . '/database.sql');

    // Dividir o SQL por ponto e vírgula para executar múltiplas instruções? 	
    // O PDO::exec costuma suportar múltiplas instruções se o driver o permitir, mas é mais seguro percorrer um ciclo para alguns drivers.
    // No entanto, normalmente a importação de um dump completo funciona bem de uma só vez com drivers genéricos ou requer divisão.
    // Vamos tentar executar o conteúdo completo.
    $pdo->exec($sql);

    echo "Database tables created successfully!<br>";
    echo "You can now delete this file or keep it for reset purposes.";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
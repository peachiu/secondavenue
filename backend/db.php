<?php
// backend/db.php

$host = 'localhost';
$dbname = 'secondavenue';
$username = 'root'; // Utilizador predefinido do XAMPP/WAMP
$password = '';     // Palavra-passe predefinida do XAMPP/WAMP (vazia)

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    
    // Criar a base de dados se não existir (por conveniência do desenvolvimento local)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    // Definir o modo de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Importar o esquema se as tabelas não existirem (verificação básica)
    // Nota: Em produção seriam usadas migrações, mas para esta configuração vamos manter as coisas simples
    // ou correr o ficheiro SQL manualmente. 	
    // Descomente a linha abaixo se quiser auto-executar o SQL na primeira ligação (use com precaução)
    // $sql = file_get_contents(__DIR__ . '/../database.sql');
    // $pdo->exec($sql);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

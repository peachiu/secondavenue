<?php
// backend/db.php

$host = 'localhost';
$dbname = 'secondavenue';
$username = 'root'; // Default XAMPP/WAMP user
$password = '';     // Default XAMPP/WAMP password (empty)

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    
    // Create database if it doesn't exist (for local dev convenience)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Import schema if tables don't exist (basic check)
    // Note: In production you'd use migrations, but for this setup we'll keep it simple
    // or run the SQL file manually. 
    // Uncomment the line below if you want to auto-run the SQL on first connect (use with caution)
    // $sql = file_get_contents(__DIR__ . '/../database.sql');
    // $pdo->exec($sql);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

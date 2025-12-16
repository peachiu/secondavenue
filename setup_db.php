<?php
require_once 'backend/db.php';

echo "Setting up database...<br>";

try {
    $sql = file_get_contents(__DIR__ . '/database.sql');

    // Split SQL by semicolons to execute multiple statements? 
    // PDO::exec usually handles multiple if driver supports it, but safer to loop for some drivers.
    // However, usually importing a full dump works fine in one go with generic drivers or requires splitting.
    // Let's try executing full content.
    $pdo->exec($sql);

    echo "Database tables created successfully!<br>";
    echo "You can now delete this file or keep it for reset purposes.";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>

<?php

// Configuration de la base de données
$host = getenv("DB_HOST") ?: "localhost";
$dbname = getenv("DB_NAME") ?: "tajwid_academy";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données.");
}

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

$sql = file_get_contents(BASE_PATH . '/database/migrations/001_auth_security.sql');

if ($sql === false) {
    throw new RuntimeException('Migration auth introuvable.');
}

$dsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec($sql);

echo "Module Authentification & Sécurité installé.\n";
echo "Admin: admin@wake-services.local / Admin@12345\n";

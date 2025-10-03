<?php
require_once __DIR__ . '/config.php';

function pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    // conecta ao servidor sem selecionar database para permitir CREATE DATABASE
    $dsnServer = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($dsnServer, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // cria o banco se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // conecta no banco
    $dsnDb = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsnDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

// roda a migração se as tabelas ainda não existirem
function ensure_migrated(): void {
    $db = pdo();
    $exists = $db->query("SHOW TABLES LIKE 'produtos'")->fetchColumn();
    if ($exists) return;

    $sql = file_get_contents(__DIR__ . '/migrations/001_init.sql');
    $db->exec($sql);
}
ensure_migrated();
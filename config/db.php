<?php
/**
 * Database Connection Config
 * Using PDO (PHP Data Objects) for security against SQL Injection
 * Auto-detects Local (XAMPP) vs Production (InfinityFree) environment
 */

$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

if ($serverName === 'localhost' || $serverName === '127.0.0.1') {
    // ── Local XAMPP ──
    $host = 'localhost';
    $db   = 'product_catalog';
    $user = 'root';
    $pass = '';
} else {
    // ── InfinityFree Production ──
    $host = 'sql107.infinityfree.com';
    $db   = 'if0_42056094_deshishop';
    $user = 'if0_42056094';
    $pass = 'Saikat.pal';
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed. Please make sure MySQL is running and the database matches schema.sql. Error: " . htmlspecialchars($e->getMessage()));
}

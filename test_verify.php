<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT password FROM users WHERE id = 1");
$hash = $stmt->fetchColumn();
echo "Hash from DB: " . $hash . "\n";
var_dump(password_verify('adminpassword123', $hash));

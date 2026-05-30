<?php
require_once __DIR__ . '/config/db.php';
$hash = password_hash('adminpassword123', PASSWORD_BCRYPT);

// Test verification
if (password_verify('adminpassword123', $hash)) {
    echo "Hash verification passed!\n";
} else {
    echo "Hash verification failed!\n";
}

$stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = 1");
$stmt->execute([':password' => $hash]);
echo "Database updated successfully!\n";

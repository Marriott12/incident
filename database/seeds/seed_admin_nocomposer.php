<?php
declare(strict_types=1);

// Minimal seed that does not require Composer autoload.
// Run: php database/seeds/seed_admin_nocomposer.php

require __DIR__ . '/../../config/db.php';

use App\Config\DB;

try {
    $pdo = DB::getPDO();
    $email = 'admin@example.local';
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Admin user already exists (id={$row['id']}).\n";
        exit(0);
    }

    $password = getenv('INITIAL_ADMIN_PASSWORD') ?: 'ChangeMe123!';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare('INSERT INTO users (`name`, `rank`, `email`, `password_hash`, `role`, `unit`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $ins->execute(['System Administrator', 'Colonel', $email, $hash, 'admin', 'HQ']);
    echo "Created admin user {$email} with password: {$password}\n";
    echo "Please change the password after first login.\n";
    exit(0);
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

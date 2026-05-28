<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Config\DB;


// Creates initial admin user if not present. Run: php database/seeds/seed_admin.php
try {
    $pdo = DB::getPDO();
    $email = 'admin@example.local';
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($row) {
        echo "Admin user already exists (id={$row['id']}).\n";
        exit;
    }

    $password = getenv('INITIAL_ADMIN_PASSWORD') ?: 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare('INSERT INTO users (name, rank, email, password_hash, role, unit, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $ins->execute(['System Administrator', 'Colonel', $email, $hash, 'admin', 'HQ']);
    echo "Created admin user {$email} with password: {$password}\n";
    echo "Please change the password after first login.\n";
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

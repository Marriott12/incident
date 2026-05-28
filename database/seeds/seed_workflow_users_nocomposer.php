<?php
declare(strict_types=1);

// Standalone seeder for workflow user roles.
// Run: php database/seeds/seed_workflow_users_nocomposer.php

require __DIR__ . '/../../config/db.php';

use App\Config\DB;

$users = [
    [
        'name' => 'Lusaka CPO',
        'rank' => 'Captain',
        'email' => 'cpo.lusaka@example.local',
        'password' => getenv('INITIAL_USER_PASSWORD') ?: 'ChangeMe123!',
        'role' => 'cpo',
        'unit' => 'Command Post Lusaka',
        'formation' => '1 Brigade',
        'province' => 'Lusaka',
    ],
    [
        'name' => 'Lusaka G Staff',
        'rank' => 'Lieutenant Colonel',
        'email' => 'gstaf.lusaka@example.local',
        'password' => getenv('INITIAL_USER_PASSWORD') ?: 'ChangeMe123!',
        'role' => 'g_staff',
        'unit' => 'G Staff Lusaka',
        'formation' => '1 Brigade',
        'province' => 'Lusaka',
    ],
    [
        'name' => '1 Brigade Commander',
        'rank' => 'Brigadier',
        'email' => 'formation.commander@example.local',
        'password' => getenv('INITIAL_USER_PASSWORD') ?: 'ChangeMe123!',
        'role' => 'formation_commander',
        'unit' => '1 Brigade HQ',
        'formation' => '1 Brigade',
        'province' => 'Lusaka',
    ],
    [
        'name' => 'Army HQ Reader',
        'rank' => 'Major General',
        'email' => 'army.hq@example.local',
        'password' => getenv('INITIAL_USER_PASSWORD') ?: 'ChangeMe123!',
        'role' => 'army_hq',
        'unit' => 'Army Headquarters',
        'formation' => 'Army HQ',
        'province' => 'National',
    ],
];

try {
    $pdo = DB::getPDO();
    foreach ($users as $user) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$user['email']]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($exists) {
            echo "User already exists: {$user['email']} (id={$exists['id']})\n";
            continue;
        }

        $hash = password_hash($user['password'], PASSWORD_BCRYPT);
        $ins = $pdo->prepare(
            'INSERT INTO users (`service_number`, `name`, `rank`, `email`, `password_hash`, `role`, `unit`, `formation`, `province`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $ins->execute([
            $user['service_number'] ?? null,
            $user['name'],
            $user['rank'],
            $user['email'],
            $hash,
            $user['role'],
            $user['unit'],
            $user['formation'],
            $user['province'],
        ]);

        echo "Created user: {$user['email']} with role {$user['role']}\n";
    }
    echo "Workflow roles seeding complete.\n";
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DDG\Bootstrap\Database;

$pdo = Database::connect();

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        continue;
    }
    $pdo->exec($sql);
    echo sprintf("[ok] %s\n", basename($file));
}

echo "Banco inicializado com sucesso.\n";

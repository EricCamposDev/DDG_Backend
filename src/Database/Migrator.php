<?php

declare(strict_types=1);

namespace DDG\Database;

use PDO;

final class Migrator
{
    public static function run(PDO $pdo, ?string $migrationsDir = null): void
    {
        $migrationsDir ??= dirname(__DIR__, 2) . '/database/migrations';
        $files = glob($migrationsDir . '/*.sql') ?: [];
        sort($files);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }
            $pdo->exec($sql);
        }
    }
}

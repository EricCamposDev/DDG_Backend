<?php

declare(strict_types=1);

namespace DDG\Bootstrap;

use PDO;

final class Database
{
    public static function connect(?string $path = null): PDO
    {
        $dsn = self::resolveDsn($path);
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    }

    private static function resolveDsn(?string $path): string
    {
        if ($path === ':memory:') {
            return 'sqlite::memory:';
        }
        $path ??= getenv('DB_PATH') ?: dirname(__DIR__, 2) . '/database/database.sqlite';
        return 'sqlite:' . $path;
    }
}

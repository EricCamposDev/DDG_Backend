<?php

declare(strict_types=1);

namespace DDG\Tests\Support;

use DDG\Bootstrap\Database;
use DDG\Database\Migrator;
use PDO;

final class TestDatabase
{
    public static function fresh(): PDO
    {
        $pdo = Database::connect(':memory:');
        Migrator::run($pdo);
        return $pdo;
    }
}

<?php

declare(strict_types=1);

/**
 * Migration runner.
 * Usage:
 *   php database/migrate.php          Run all pending migrations
 *   php database/migrate.php fresh    Drop all tables, then re-run
 */

$basePath = dirname(__DIR__);
require $basePath . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\App;
use App\Core\Database;

// Bootstrap config/env without HTTP side-effects.
App\Core\Env::load($basePath . '/.env');
App\Core\Config::load($basePath . '/config');
date_default_timezone_set((string) config('app.timezone', 'Asia/Tehran'));

$command = $argv[1] ?? 'migrate';
$pdo = Database::connection();

fwrite(STDOUT, "Nullik Academy — Database Migrator\n");
fwrite(STDOUT, str_repeat('=', 42) . "\n");

if ($command === 'fresh') {
    fwrite(STDOUT, "Dropping all tables...\n");
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = Database::select('SHOW TABLES');
    foreach ($tables as $row) {
        $table = array_values($row)[0];
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        fwrite(STDOUT, "  dropped {$table}\n");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

// Track which migrations have run.
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_migration (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$ran = array_column(Database::select('SELECT migration FROM migrations'), 'migration');
$batch = (int) Database::scalar('SELECT COALESCE(MAX(batch),0) FROM migrations') + 1;

$files = glob($basePath . '/database/migrations/*.sql');
sort($files);

$applied = 0;
foreach ($files as $file) {
    $name = basename($file, '.sql');
    if (in_array($name, $ran, true)) {
        continue;
    }

    $sql = file_get_contents($file);
    fwrite(STDOUT, "Migrating: {$name}\n");

    try {
        $pdo->exec($sql);
        Database::insert('INSERT INTO migrations (migration, batch) VALUES (?, ?)', [$name, $batch]);
        $applied++;
    } catch (Throwable $e) {
        fwrite(STDERR, "  FAILED: {$e->getMessage()}\n");
        exit(1);
    }
}

fwrite(STDOUT, str_repeat('=', 42) . "\n");
fwrite(STDOUT, $applied === 0 ? "Nothing to migrate. Database is up to date.\n" : "Applied {$applied} migration(s).\n");

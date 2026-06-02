<?php

declare(strict_types=1);

use CentralDeAulas\Config\DatabaseConfig;
use CentralDeAulas\Database\MigrationRunner;
use CentralDeAulas\Database\PdoDatabase;

require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$config = DatabaseConfig::fromEnvironment();
$database = new PdoDatabase($config);
$migrationRunner = new MigrationRunner($config, $database, dirname(__DIR__) . '/database/migrations');

try {
    $migrationRunner->run();
    $database->disconnect();

    echo "Database migrations executed successfully." . PHP_EOL;
} catch (Throwable $exception) {
    $database->disconnect();

    fwrite(STDERR, "Database setup failed: {$exception->getMessage()}" . PHP_EOL);
    exit(1);
}

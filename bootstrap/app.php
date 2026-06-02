<?php

declare(strict_types=1);

use CentralDeAulas\Core\Env;
use CentralDeAulas\Config\DatabaseConfig;
use CentralDeAulas\Database\MigrationRunner;
use CentralDeAulas\Database\PdoDatabase;

require_once __DIR__ . '/runtime.php';

if (in_array(strtolower(Env::get('DB_AUTO_MIGRATE', 'true')), ['1', 'true', 'yes', 'on'], true)) {
    $databaseConfig = DatabaseConfig::fromEnvironment();
    $database = new PdoDatabase($databaseConfig);
    $migrationRunner = new MigrationRunner(
        $databaseConfig,
        $database,
        dirname(__DIR__) . '/database/migrations'
    );

    $migrationRunner->run();
    $database->disconnect();
}

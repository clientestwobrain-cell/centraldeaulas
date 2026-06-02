<?php

declare(strict_types=1);

use CentralDeAulas\Config\DatabaseConfig;
use CentralDeAulas\Database\PdoDatabase;
use CentralDeAulas\Http\AdminSession;
use CentralDeAulas\Security\Crypto;
use CentralDeAulas\Service\AccessPasswordService;
use CentralDeAulas\Service\AdminDashboardService;
use CentralDeAulas\Service\AdminUserService;
use CentralDeAulas\Support\PhoneNormalizer;

require_once __DIR__ . '/app.php';

$database = new PdoDatabase(DatabaseConfig::fromEnvironment());
$adminSession = new AdminSession();
$adminSession->start();

$adminUserService = new AdminUserService($database);
$adminUserService->ensureBootstrapAdmin();

$adminDashboardService = new AdminDashboardService($database);
$accessPasswordService = new AccessPasswordService($database, new Crypto(), new PhoneNormalizer());

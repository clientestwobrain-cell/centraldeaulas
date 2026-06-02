<?php

declare(strict_types=1);

use CentralDeAulas\Config\DatabaseConfig;
use CentralDeAulas\Database\PdoDatabase;
use CentralDeAulas\Http\ApiAuth;
use CentralDeAulas\Http\Cors;
use CentralDeAulas\Security\Crypto;
use CentralDeAulas\Service\AccessPasswordApiService;
use CentralDeAulas\Service\AccessPasswordService;
use CentralDeAulas\Service\UserApiService;
use CentralDeAulas\Support\PhoneNormalizer;

require_once __DIR__ . '/app.php';

$cors = new Cors();
$cors->apply();
$cors->handlePreflight();

$database = new PdoDatabase(DatabaseConfig::fromEnvironment());
$apiAuth = new ApiAuth();
$phoneNormalizer = new PhoneNormalizer();
$accessPasswordService = new AccessPasswordService($database, new Crypto(), $phoneNormalizer);
$userApiService = new UserApiService($database, $phoneNormalizer);
$accessPasswordApiService = new AccessPasswordApiService($database, $accessPasswordService);

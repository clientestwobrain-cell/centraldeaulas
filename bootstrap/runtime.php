<?php

declare(strict_types=1);

use CentralDeAulas\Core\Autoloader;
use CentralDeAulas\Core\Env;

require_once dirname(__DIR__) . '/src/Core/Autoloader.php';

Autoloader::register(dirname(__DIR__) . '/src', 'CentralDeAulas\\');

Env::load(dirname(__DIR__) . '/.env');

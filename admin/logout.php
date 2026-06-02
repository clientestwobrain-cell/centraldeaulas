<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/admin.php';

$adminSession->logout();

header('Location: login.php');
exit;

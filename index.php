<?php

declare(strict_types=1);

use CentralDeAulas\Config\DatabaseConfig;
use CentralDeAulas\Database\PdoDatabase;

try {
    require_once __DIR__ . '/bootstrap/app.php';

    header('Content-Type: application/json; charset=utf-8');

    $database = new PdoDatabase(DatabaseConfig::fromEnvironment());
    $database->connect(false);
    $database->disconnect();

    http_response_code(200);

    echo json_encode([
        'status' => 'success',
        'message' => 'Conexao com o servidor MySQL estabelecida com sucesso.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Nao foi possivel conectar ao servidor MySQL.',
        'detail' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

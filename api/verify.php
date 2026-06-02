<?php

declare(strict_types=1);

use CentralDeAulas\Http\JsonResponse;
use CentralDeAulas\Http\Request;
use CentralDeAulas\Http\Validator;

try {
    require_once dirname(__DIR__) . '/bootstrap/api.php';

    $request = new Request();

    if ($request->method() !== 'POST') {
        JsonResponse::send([
            'status' => 'error',
            'message' => 'Metodo nao permitido.',
        ], 405);
        return;
    }

    $input = $request->input();
    $validator = new Validator();
    $isValid = $accessPasswordService->verifyPassword(
        $validator->requireString($input, 'phone', 30),
        $validator->requireString($input, 'password', 8)
    );

    JsonResponse::send([
        'status' => 'success',
        'data' => [
            'valid' => $isValid,
        ],
    ], $isValid ? 200 : 401);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send([
        'status' => 'error',
        'message' => $exception->getMessage(),
    ], 422);
} catch (Throwable $exception) {
    JsonResponse::send([
        'status' => 'error',
        'message' => 'Nao foi possivel validar a senha de acesso.',
        'detail' => $exception->getMessage(),
    ], 500);
}

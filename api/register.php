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

    $result = $accessPasswordService->registerOrReuseAccessPassword(
        $validator->requireString($input, 'name', 150),
        $validator->requireEmail($input, 'email'),
        $validator->requireString($input, 'phone', 30),
        $request->clientContext()
    );

    JsonResponse::send([
        'status' => 'success',
        'data' => $result,
    ]);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send([
        'status' => 'error',
        'message' => $exception->getMessage(),
    ], 422);
} catch (Throwable $exception) {
    JsonResponse::send([
        'status' => 'error',
        'message' => 'Nao foi possivel gerar a senha de acesso.',
        'detail' => $exception->getMessage(),
    ], 500);
}

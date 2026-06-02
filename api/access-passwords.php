<?php

declare(strict_types=1);

use CentralDeAulas\Http\JsonResponse;
use CentralDeAulas\Http\Request;

try {
    require_once dirname(__DIR__) . '/bootstrap/api.php';

    if (!$apiAuth->requireAdminToken()) {
        JsonResponse::send(['status' => 'error', 'message' => 'Token de API invalido ou ausente.'], 401);
        return;
    }

    $request = new Request();
    $method = $request->method();
    $id = $request->query('id');

    if ($method === 'GET') {
        if ($id !== null) {
            $accessPassword = $accessPasswordApiService->find((int) $id);

            JsonResponse::send([
                'status' => $accessPassword === false ? 'error' : 'success',
                'data' => $accessPassword === false ? null : $accessPassword,
                'message' => $accessPassword === false ? 'Chave nao encontrada.' : null,
            ], $accessPassword === false ? 404 : 200);
            return;
        }

        JsonResponse::send([
            'status' => 'success',
            'data' => $accessPasswordApiService->list((int) $request->query('page', 1), (int) $request->query('per_page', 15)),
        ]);
        return;
    }

    if ($method === 'POST') {
        JsonResponse::send([
            'status' => 'success',
            'data' => $accessPasswordApiService->create($request->input(), $request->clientContext()),
        ], 201);
        return;
    }

    if (in_array($method, ['PUT', 'PATCH'], true)) {
        if ($id === null) {
            JsonResponse::send(['status' => 'error', 'message' => 'Informe o id da chave.'], 422);
            return;
        }

        $accessPassword = $accessPasswordApiService->update((int) $id, $request->input());

        JsonResponse::send([
            'status' => $accessPassword === false ? 'error' : 'success',
            'data' => $accessPassword === false ? null : $accessPassword,
            'message' => $accessPassword === false ? 'Chave nao encontrada.' : null,
        ], $accessPassword === false ? 404 : 200);
        return;
    }

    if ($method === 'DELETE') {
        if ($id === null) {
            JsonResponse::send(['status' => 'error', 'message' => 'Informe o id da chave.'], 422);
            return;
        }

        $accessPassword = $accessPasswordApiService->deactivate((int) $id);

        JsonResponse::send([
            'status' => $accessPassword === false ? 'error' : 'success',
            'data' => $accessPassword === false ? null : $accessPassword,
            'message' => $accessPassword === false ? 'Chave nao encontrada.' : 'Chave desativada sem exclusao fisica.',
        ], $accessPassword === false ? 404 : 200);
        return;
    }

    JsonResponse::send(['status' => 'error', 'message' => 'Metodo nao permitido.'], 405);
} catch (InvalidArgumentException $exception) {
    JsonResponse::send(['status' => 'error', 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    JsonResponse::send([
        'status' => 'error',
        'message' => 'Nao foi possivel processar chaves.',
        'detail' => $exception->getMessage(),
    ], 500);
}

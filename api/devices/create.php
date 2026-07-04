<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::requireAdmin();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    JsonResponse::error('Невалиден JSON', 400);
}

$device = trim((string) ($input['device'] ?? ''));

if ($device === '') {
    JsonResponse::error('Името на устройството е задължително', 400);
}

if (mb_strlen($device) > 100) {
    JsonResponse::error('Името на устройството е твърде дълго (макс. 100 знака)', 400);
}

try {
    $dao = new DevicesDao();

    $existing = $dao->findByName($device);
    if ($existing !== null) {
        // Вече съществува — връщаме съществуващото, за да е идемпотентно.
        JsonResponse::success($existing, 200);
    }

    $id = $dao->create($device);
    JsonResponse::success(['id' => $id, 'device' => $device], 201);
} catch (Throwable $e) {
    JsonResponse::exception($e);
}

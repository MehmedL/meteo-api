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

$phenom = trim((string) ($input['phenom'] ?? ''));

if ($phenom === '') {
    JsonResponse::error('Името на явлението е задължително', 400);
}

if (mb_strlen($phenom) > 100) {
    JsonResponse::error('Името на явлението е твърде дълго (макс. 100 знака)', 400);
}

try {
    $dao = new PhenomenonDao();

    $existing = $dao->findByName($phenom);
    if ($existing !== null) {
        // Вече съществува — връщаме съществуващото, за да е идемпотентно.
        JsonResponse::success($existing, 200);
    }

    $id = $dao->create($phenom);
    JsonResponse::success(['id' => $id, 'phenom' => $phenom], 201);
} catch (Throwable $e) {
    JsonResponse::exception($e);
}
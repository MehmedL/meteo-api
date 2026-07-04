<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::requireAdmin();

try {
    $dao = new CameramodeDao();
    JsonResponse::success($dao->findAll());
} catch (Throwable $e) {
    JsonResponse::exception($e);
}

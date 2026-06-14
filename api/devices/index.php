<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

try {
    $dao = new DevicesDao();
    JsonResponse::success($dao->findAll());
} catch (Throwable $e) {
    JsonResponse::error($e->getMessage(), 500);
}

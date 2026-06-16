<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

$user = Auth::user();
if ($user === null) {
    JsonResponse::error('Не сте влезли', 401);
}

JsonResponse::success($user);

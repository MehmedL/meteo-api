<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::require();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    JsonResponse::error('Невалидна заявка', 400);
}

try {
    $videofileDao = new VideofileDao();
    $row = $videofileDao->findDetailById($id);

    if ($row === null) {
        JsonResponse::error('Записът не е намерен', 404);
    }

    $patchDao = new VideofilepatchDao();
    $phenomDao = new PatchphenomDao();
    $txtDao = new TxtfileDao();

    $patches = array_map(
        fn (array $patch) => [
            'id'           => $patch['id'],
            'patch'        => $patch['patch'],
            'phenomena'    => $phenomDao->findPhenomenaByVfp($patch['id']),
            'measurements' => $txtDao->findByVfp($patch['id']),
        ],
        $patchDao->findByVfile($id)
    );

    JsonResponse::success(VideofileDetailDto::fromRow($row, $patches));
} catch (Throwable $e) {
    JsonResponse::exception($e);
}
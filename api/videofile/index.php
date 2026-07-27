<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::require();

try {
    $filters = VideofileSearchFilters::parse();
    $pagination = VideofileSearchFilters::parsePagination();

    $dao = new VideofileDao();
    $rows = $dao->search($filters);

    $total = count($rows);
    $page = array_slice($rows, $pagination['offset'], $pagination['limit']);

    JsonResponse::success([
        'items' => array_map([VideofileSearchDto::class, 'fromRow'], $page),
        'total' => $total,
    ]);
} catch (Throwable $e) {
    JsonResponse::exception($e);
}
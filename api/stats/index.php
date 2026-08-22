<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::require();

try {
    $dao = new StatsDao();
    JsonResponse::success([
        'totals'       => $dao->totals(),
        'byPhenomenon' => $dao->countByPhenomenon(),
        'byDevice'     => $dao->countByDevice(),
    ]);
} catch (Throwable $e) {
    JsonResponse::exception($e);
}

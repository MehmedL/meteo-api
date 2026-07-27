<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::require();

// Ключове по VideofileDto::fromRow (findById връща вече оформено DTO).
const ALLOWED_TYPES = ['video' => 'filepath', 'zip' => 'zipfile'];

$id = (int) ($_GET['id'] ?? 0);
$type = (string) ($_GET['type'] ?? '');
$mode = ($_GET['mode'] ?? 'attachment') === 'inline' ? 'inline' : 'attachment';

if ($id <= 0 || !isset(ALLOWED_TYPES[$type])) {
    JsonResponse::error('Невалидна заявка', 400);
}

try {
    $record = (new VideofileDao())->findById($id);

    if ($record === null) {
        JsonResponse::error('Файлът не е намерен', 404);
    }

    $storedPath = $record[ALLOWED_TYPES[$type]] ?? null;

    if ($storedPath === null || $storedPath === '') {
        JsonResponse::error('Файлът не е намерен', 404);
    }

    $uploadsConfig = require dirname(__DIR__, 2) . '/config/uploads.php';
    $base = realpath($uploadsConfig['baseDir']);

    // $storedPath вече е пълният път на диска.
    $real = realpath($storedPath);

    if ($base === false || $real === false || !str_starts_with($real, $base) || !is_file($real)) {
        JsonResponse::error('Файлът не е намерен', 404);
    }

    $mime = 'application/octet-stream';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($real);
        if ($detected !== false) {
            $mime = $detected;
        }
    }

    $filename = basename($real);

    header('Content-Type: ' . $mime);
    header("Content-Disposition: {$mode}; filename=\"{$filename}\"");
    header('Content-Length: ' . filesize($real));
    header('X-Content-Type-Options: nosniff');

    readfile($real);
    exit;
} catch (Throwable $e) {
    JsonResponse::exception($e);
}
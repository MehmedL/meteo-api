<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

// Качването е позволено само за логнат потребител.
Auth::require();

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    JsonResponse::error('Не е качен файл или има грешка при качването', 400);
}

try {
    $content = file_get_contents($_FILES['file']['tmp_name']);
    if ($content === false) {
        JsonResponse::error('Файлът не може да бъде прочетен', 400);
    }

    $rows = TextfileImporter::parse($content);
    if ($rows === []) {
        JsonResponse::error('Във файла няма валидни редове с данни', 400);
    }

    $dao = new TxtfileDao();
    $inserted = $dao->insertMany($rows);

    JsonResponse::success(['inserted' => $inserted]);
} catch (Throwable $e) {
    JsonResponse::error($e->getMessage(), 500);
}

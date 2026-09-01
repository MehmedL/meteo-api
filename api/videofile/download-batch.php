<?php

require_once __DIR__ . '/../../config/bootstrap.php';

use ZipStream\CompressionMethod;
use ZipStream\ZipStream;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::require();

const DOWNLOAD_BATCH_MAX_ITEMS = 30;
const DOWNLOAD_BATCH_MAX_CONCURRENT = 3;

const DOWNLOAD_BATCH_ALLOWED_TYPES = [
    'image' => 'imgfile',
    'video' => 'filepath',
    'zip'   => 'zipfile',
];

$rawItems = $_GET['items'] ?? [];
if (!is_array($rawItems) || $rawItems === []) {
    JsonResponse::error('Не са избрани файлове', 400);
}

if (count($rawItems) > DOWNLOAD_BATCH_MAX_ITEMS) {
    JsonResponse::error('Може да изтеглите най-много ' . DOWNLOAD_BATCH_MAX_ITEMS . ' файла наведнъж', 400);
}

$requested = [];
foreach ($rawItems as $raw) {
    $parts = explode(':', (string) $raw, 2);
    if (count($parts) !== 2) {
        continue;
    }

    [$idPart, $type] = $parts;
    $id = (int) $idPart;

    if ($id <= 0 || !isset(DOWNLOAD_BATCH_ALLOWED_TYPES[$type])) {
        continue;
    }

    $requested[] = ['id' => $id, 'type' => $type];
}

if ($requested === []) {
    JsonResponse::error('Невалидна заявка', 400);
}

$locksDir = dirname(__DIR__, 2) . '/storage/locks';
if (!is_dir($locksDir)) {
    mkdir($locksDir, 0775, true);
}

$lockHandle = null;
for ($slot = 0; $slot < DOWNLOAD_BATCH_MAX_CONCURRENT; $slot++) {
    $handle = fopen($locksDir . "/zip-batch-{$slot}.lock", 'c');
    if ($handle !== false && flock($handle, LOCK_EX | LOCK_NB)) {
        $lockHandle = $handle;
        break;
    }
    if ($handle !== false) {
        fclose($handle);
    }
}

if ($lockHandle === null) {
    JsonResponse::error('Сървърът обработва твърде много изтегляния в момента. Опитайте отново след малко.', 429);
}

register_shutdown_function(static function () use ($lockHandle) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

set_time_limit(0);

function download_batch_resolvePath(string $storedPath, string $base, string $projectRoot): ?string
{
    $real = realpath($storedPath);
    if ($real === false) {
        $real = realpath($projectRoot . DIRECTORY_SEPARATOR . $storedPath);
    }

    if ($real === false || !str_starts_with($real, $base) || !is_file($real)) {
        return null;
    }

    return $real;
}

try {
    $videoDao = new VideofileDao();
    $projectRoot = dirname(__DIR__, 2);

    $uploadsConfig = require $projectRoot . '/config/uploads.php';
    $base = realpath($uploadsConfig['baseDir']);

    $files = [];
    $usedNames = [];
    $skipped = 0;

    foreach ($requested as $item) {
        $record = $videoDao->findById($item['id']);
        $storedPath = $record[DOWNLOAD_BATCH_ALLOWED_TYPES[$item['type']]] ?? null;

        $real = ($record !== null && $base !== false && $storedPath)
            ? download_batch_resolvePath($storedPath, $base, $projectRoot)
            : null;

        if ($real === null) {
            $skipped++;
            continue;
        }

        $cleanName = preg_replace('/^[0-9a-f]{16}__/', '', basename($real));
        $entryName = "{$item['id']}_{$item['type']}_" . $cleanName;
        if (isset($usedNames[$entryName])) {
            $entryName = "{$item['id']}_{$item['type']}_" . uniqid() . '_' . basename($real);
        }
        $usedNames[$entryName] = true;

        $files[] = ['name' => $entryName, 'path' => $real];
    }

    if ($files === []) {
        JsonResponse::error('Нито един от избраните файлове не беше намерен', 404);
    }

    header('Access-Control-Expose-Headers: X-Download-Batch-Skipped, X-Download-Batch-Total');
    header('X-Download-Batch-Skipped: ' . $skipped);
    header('X-Download-Batch-Total: ' . count($requested));

    $zip = new ZipStream(
        outputName: 'videofiles_' . date('Y-m-d_His') . '.zip',
        defaultCompressionMethod: CompressionMethod::STORE,
        sendHttpHeaders: true,
        contentType: 'application/zip',
    );

    foreach ($files as $file) {
        $zip->addFileFromPath($file['name'], $file['path']);
    }

    $zip->finish();
    exit;
} catch (Throwable $e) {
    JsonResponse::exception($e);
}
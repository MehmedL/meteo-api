<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::requireAdmin();

const ALLOWED_DIRECTIONS = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
const MAX_PATCH_INDEX = 8;
const TXT_MAX_SIZE = 20 * 1024 * 1024; // 20 MB

/** @return bool */
function vf_isValidDateTime(string $value): bool
{
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return $d !== false && $d->format('Y-m-d H:i:s') === $value;
}

/** Проверява качен txt файл (не се пази на диска, само се парсва). */
function vf_assertTxtUpload(array $file): void
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Грешка при качване на текстов файл.');
    }
    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Невалиден текстов файл.');
    }
    if ((int) ($file['size'] ?? 0) > TXT_MAX_SIZE) {
        throw new RuntimeException('Текстовият файл е твърде голям (макс. 20 MB).');
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext !== 'txt') {
        throw new RuntimeException('Позволени са само .txt файлове за пачовете.');
    }
}

function vf_hasUpload(string $field): bool
{
    return isset($_FILES[$field])
        && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

// --- Разчитане и валидация на метаданните (payload) ---
$payload = json_decode((string) ($_POST['payload'] ?? ''), true);
if (!is_array($payload)) {
    JsonResponse::error('Липсва или невалиден payload', 400);
}

$device = isset($payload['device']) && $payload['device'] !== null && $payload['device'] !== ''
    ? (int) $payload['device']
    : null;

$cammod = isset($payload['cammod']) && $payload['cammod'] !== null && $payload['cammod'] !== ''
    ? (int) $payload['cammod']
    : null;

$xGPS = isset($payload['xGPS']) && $payload['xGPS'] !== null && $payload['xGPS'] !== ''
    ? (float) $payload['xGPS']
    : null;

$yGPS = isset($payload['yGPS']) && $payload['yGPS'] !== null && $payload['yGPS'] !== ''
    ? (float) $payload['yGPS']
    : null;

$dir = isset($payload['dir']) ? trim((string) $payload['dir']) : '';
if ($dir !== '' && !in_array($dir, ALLOWED_DIRECTIONS, true)) {
    JsonResponse::error('Невалидна посока', 400);
}
$dir = $dir === '' ? null : $dir;

$sdata = isset($payload['sdata']) ? trim((string) $payload['sdata']) : '';
if ($sdata !== '' && !vf_isValidDateTime($sdata)) {
    JsonResponse::error('Невалидна дата и час на записа', 400);
}
$sdata = $sdata === '' ? null : $sdata;

// --- Задължителни полета ---
if (!vf_hasUpload('video')) {
    JsonResponse::error('Видео файлът е задължителен', 400);
}
if (!vf_hasUpload('image')) {
    JsonResponse::error('Изображението е задължително', 400);
}
if (!vf_hasUpload('zip')) {
    JsonResponse::error('Zip файлът е задължителен', 400);
}
if ($device === null) {
    JsonResponse::error('Устройството е задължително', 400);
}
if ($xGPS === null || $yGPS === null) {
    JsonResponse::error('GPS координатите (X и Y) са задължителни', 400);
}
if ($dir === null) {
    JsonResponse::error('Посоката е задължителна', 400);
}
if ($sdata === null) {
    JsonResponse::error('Датата и часът на записа са задължителни', 400);
}

$patchesInput = isset($payload['patches']) && is_array($payload['patches']) ? $payload['patches'] : [];

try {
    // Проверка на референции (device, cammod, phenomena) преди качване на файлове.
    if ($device !== null && (new DevicesDao())->findById($device) === null) {
        JsonResponse::error('Избраното устройство не съществува', 400);
    }

    if ($cammod !== null && (new CameramodeDao())->findById($cammod) === null) {
        JsonResponse::error('Избраният режим не съществува', 400);
    }

    $validPhenom = [];
    foreach ((new PhenomenonDao())->findAll() as $ph) {
        $validPhenom[(int) $ph['id']] = true;
    }

    // Нормализиране на пачовете: само валиден индекс 0..8; празни (без явления и без txt) се прескачат.
    $patches = [];
    foreach ($patchesInput as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $patchIndex = (int) ($entry['patch'] ?? -1);
        if ($patchIndex < 0 || $patchIndex > MAX_PATCH_INDEX) {
            continue;
        }

        $phenomena = [];
        foreach ((array) ($entry['phenomena'] ?? []) as $phId) {
            $phId = (int) $phId;
            if (!isset($validPhenom[$phId])) {
                JsonResponse::error('Избрано е несъществуващо явление', 400);
            }
            $phenomena[$phId] = $phId; // уникални
        }

        $patches[] = [
            'patch'     => $patchIndex,
            'phenomena' => array_values($phenomena),
            'rows'      => [],
            'hasTxt'    => vf_hasUpload("patch_txt_{$patchIndex}"),
        ];
    }

    $hasPhenomenonSelected = false;
    foreach ($patches as $p) {
        if ($p['phenomena'] !== []) {
            $hasPhenomenonSelected = true;
            break;
        }
    }
    if (!$hasPhenomenonSelected) {
        JsonResponse::error('Трябва да изберете поне едно явление в поне един пач', 400);
    }
} catch (Throwable $e) {
    JsonResponse::exception($e);
}

// --- Качване на файловете + парсване на txt ---
$uploader = new FileUploader();
$uploaded = ['video' => null, 'image' => null, 'zip' => null];

try {
    if (vf_hasUpload('video')) {
        $uploaded['video'] = $uploader->store($_FILES['video'], 'video');
    }
    if (vf_hasUpload('image')) {
        $uploaded['image'] = $uploader->store($_FILES['image'], 'image');
    }
    if (vf_hasUpload('zip')) {
        $uploaded['zip'] = $uploader->store($_FILES['zip'], 'zip');
    }

    $finalPatches = [];
    foreach ($patches as $patch) {
        $field = "patch_txt_{$patch['patch']}";
        if ($patch['hasTxt']) {
            vf_assertTxtUpload($_FILES[$field]);
            $content = file_get_contents($_FILES[$field]['tmp_name']);
            if ($content === false) {
                throw new RuntimeException('Текстовият файл не може да бъде прочетен.');
            }
            $patch['rows'] = TextfileImporter::parse($content);
        }

        // Прескачаме напълно празен пач (без явления и без txt редове).
        if ($patch['phenomena'] === [] && $patch['rows'] === []) {
            continue;
        }

        $finalPatches[] = [
            'patch'     => $patch['patch'],
            'phenomena' => $patch['phenomena'],
            'rows'      => $patch['rows'],
        ];
    }

    $meta = [
        'Filepath' => $uploaded['video'],
        'Device'   => $device === null ? null : (string) $device,
        'xGPS'     => $xGPS,
        'yGPS'     => $yGPS,
        'Dir'      => $dir,
        'Sdata'    => $sdata,
        'Imgfile'  => $uploaded['image'],
        'Zipfile'  => $uploaded['zip'],
        'cammod'   => $cammod,
    ];

    $service = new VideoImportService();
    $result = $service->save($meta, $finalPatches);

    JsonResponse::success($result, 201);
} catch (RuntimeException $e) {
    // Умишлени, четими за потребителя съобщения за валидация (файлове/txt).
    foreach ($uploaded as $path) {
        $uploader->delete($path);
    }
    JsonResponse::error($e->getMessage(), 400);
} catch (Throwable $e) {
    // Неочаквана грешка (напр. БД) — не показваме вътрешни детайли на клиента.
    foreach ($uploaded as $path) {
        $uploader->delete($path);
    }
    JsonResponse::exception($e);
}

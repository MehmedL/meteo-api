<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    JsonResponse::error('Невалиден JSON', 400);
}

$user = trim((string) ($input['user'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($user === '' || $password === '') {
    JsonResponse::error('Потребител и парола са задължителни', 400);
}

try {
    $dao = new UserDao();
    $record = $dao->findByUser($user);

    if ($record === null || !PasswordHasher::verify($password, $record['password'])) {
        JsonResponse::error('Грешно потребителско име или парола', 401);
    }

    // Прозрачно мигриране: ако паролата е в чист текст или стар алгоритъм,
    // я презаписваме като нов хеш при успешен вход.
    if (PasswordHasher::needsRehash($record['password'])) {
        $dao->updatePassword($record['id'], PasswordHasher::hash($password));
    }

    Auth::login($record);

    JsonResponse::success(UserDto::toPublic($record));
} catch (Throwable $e) {
    JsonResponse::error($e->getMessage(), 500);
}

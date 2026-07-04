<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    JsonResponse::error('Невалиден JSON', 400);
}

$email = Email::fromInput($input);
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    JsonResponse::error('Имейл и парола са задължителни', 400);
}

if (!Email::isValid($email)) {
    JsonResponse::error('Невалиден имейл адрес', 400);
}

try {
    $dao = new UserDao();
    $record = $dao->findByUser($email);

    if ($record === null || !PasswordHasher::verify($password, $record['password'])) {
        JsonResponse::error('Грешен имейл или парола', 401);
    }

    // Прозрачно мигриране: ако паролата е в чист текст или стар алгоритъм,
    // я презаписваме като нов хеш при успешен вход.
    if (PasswordHasher::needsRehash($record['password'])) {
        $dao->updatePassword($record['id'], PasswordHasher::hash($password));
    }

    // Проверка на правата за достъп (usercredentials).
    // Акаунти без ред в таблицата (напр. служебни) се третират без ограничения.
    $credDao = new UserCredentialsDao();
    $creds = $credDao->findByUserId($record['id']);

    if ($creds !== null) {
        $today = date('Y-m-d');

        if ($creds['from'] !== null && $today < $creds['from']) {
            JsonResponse::error('Достъпът все още не е активен (от ' . $creds['from'] . ')', 403);
        }

        if ($creds['to'] !== null && $today > $creds['to']) {
            JsonResponse::error('Достъпът е изтекъл на ' . $creds['to'], 403);
        }

        if ($creds['nmax'] !== null && $creds['nused'] >= $creds['nmax']) {
            JsonResponse::error('Достигнат е максималният брой влизания (' . $creds['nmax'] . ')', 403);
        }

        // Брояч на влизанията — само при режим "брой влизания".
        // При времеви прозорец Nused не се следи.
        if ($creds['nmax'] !== null) {
            $credDao->incrementUsed($record['id']);
        }
    }

    Auth::login($record);

    JsonResponse::success(Auth::publicUser($record));
} catch (Throwable $e) {
    JsonResponse::exception($e);
}

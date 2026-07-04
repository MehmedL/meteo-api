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
$mode = (string) ($input['mode'] ?? '');

if ($email === '' || $password === '') {
    JsonResponse::error('Имейл и парола са задължителни', 400);
}

if (!Email::isValid($email)) {
    JsonResponse::error('Невалиден имейл адрес', 400);
}

if (mb_strlen($email) > 100) {
    JsonResponse::error('Имейлът е твърде дълъг (макс. 100 знака)', 400);
}

if (mb_strlen($password) < 4) {
    JsonResponse::error('Паролата трябва да е поне 4 знака', 400);
}

if (!in_array($mode, ['window', 'count'], true)) {
    JsonResponse::error('Невалиден режим на достъп', 400);
}

$from = null;
$to = null;
$nmax = null;

if ($mode === 'window') {
    $from = (string) ($input['from'] ?? '');
    $to = (string) ($input['to'] ?? '');

    if (!isValidDate($from) || !isValidDate($to)) {
        JsonResponse::error('Невалидни дати за прозореца на достъп', 400);
    }

    if ($from > $to) {
        JsonResponse::error('Началната дата трябва да е преди крайната', 400);
    }

    $maxTo = date('Y-m-d', strtotime($from . ' +1 year'));
    if ($to > $maxTo) {
        JsonResponse::error('Прозорецът на достъп не може да е по-дълъг от 1 година', 400);
    }
} else {
    $nmax = (int) ($input['nmax'] ?? 0);
    if ($nmax < 1 || $nmax > 100000) {
        JsonResponse::error('Броят влизания трябва да е между 1 и 100000', 400);
    }
    // При режим "брой влизания" акаунтът е активен от днес, без краен срок.
    $from = date('Y-m-d');
}

try {
    $userDao = new UserDao();
    $credDao = new UserCredentialsDao();
    $existing = $userDao->findByUser($email);

    $pdo = Database::getConnection();

    if ($existing !== null) {
        $creds = $credDao->findByUserId($existing['id']);

        if ($creds === null || !$credDao->canReRegister($creds)) {
            JsonResponse::error('Имейл с този адрес вече е регистриран', 409);
        }

        $pdo->beginTransaction();
        try {
            $userDao->updatePassword($existing['id'], PasswordHasher::hash($password));
            $credDao->replaceForUser($existing['id'], $from, $to, $nmax);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        JsonResponse::success(['id' => $existing['id'], 'user' => $email, 'email' => $email], 201);
    }

    $pdo->beginTransaction();
    try {
        $userId = $userDao->create($email, PasswordHasher::hash($password));
        $credDao->createForUser($userId, $from, $to, $nmax);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    JsonResponse::success(['id' => $userId, 'user' => $email, 'email' => $email], 201);
} catch (Throwable $e) {
    JsonResponse::exception($e);
}

function isValidDate(string $value): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

<?php

/**
 * Създава нов потребител с хеширана парола.
 *
 * Употреба:
 *   php scripts/create-user.php <потребител> <парола>
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Този скрипт се изпълнява само от командния ред.');
}

require_once __DIR__ . '/../config/bootstrap.php';

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

if ($username === '' || $password === '') {
    fwrite(STDERR, "Употреба: php scripts/create-user.php <потребител> <парола>" . PHP_EOL);
    exit(1);
}

$dao = new UserDao();

if ($dao->findByUser($username) !== null) {
    fwrite(STDERR, "Потребител '{$username}' вече съществува." . PHP_EOL);
    exit(1);
}

$id = $dao->create($username, PasswordHasher::hash($password));
echo "Създаден потребител '{$username}' (id {$id})." . PHP_EOL;

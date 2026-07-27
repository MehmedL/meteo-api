<?php



if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Този скрипт се изпълнява само от командния ред.');
}

require_once __DIR__ . '/../config/bootstrap.php';

$dao = new UserDao();
$users = $dao->findAll();

$updated = 0;
foreach ($users as $user) {
    if (PasswordHasher::isHashed($user['password'])) {
        continue;
    }

    $dao->updatePassword($user['id'], PasswordHasher::hash($user['password']));
    echo "Хеширана парола за: {$user['user']} (id {$user['id']})" . PHP_EOL;
    $updated++;
}

echo "Готово. Обновени потребители: {$updated}." . PHP_EOL;

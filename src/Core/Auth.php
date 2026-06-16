<?php

class Auth
{
    /** Записва логнатия потребител в сесията (truth-source на сървъра). */
    public static function login(array $user): void
    {
        // Нов session id след вход — защита срещу session fixation.
        session_regenerate_id(true);
        $_SESSION['userId'] = (int) $user['id'];
        $_SESSION['user'] = (string) $user['user'];
    }

    public static function check(): bool
    {
        return isset($_SESSION['userId']);
    }

    /** @return array{id:int,user:string}|null */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['userId'],
            'user' => (string) ($_SESSION['user'] ?? ''),
        ];
    }

    /** Прекъсва изпълнението с 401, ако няма логнат потребител. */
    public static function require(): void
    {
        if (!self::check()) {
            JsonResponse::error('Не сте влезли', 401);
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }
}

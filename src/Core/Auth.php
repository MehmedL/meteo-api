<?php

class Auth
{
    private const ROLE_ADMIN = 'admin';
    private const ROLE_USER = 'user';

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['userId'] = (int) $user['id'];
        $_SESSION['user'] = (string) $user['user'];
        $_SESSION['role'] = self::resolveRole($user);
    }

    public static function check(): bool
    {
        return isset($_SESSION['userId']);
    }

    /** @return array{id:int,user:string,role:string}|null */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $user = [
            'id' => (int) $_SESSION['userId'],
            'user' => (string) ($_SESSION['user'] ?? ''),
        ];

        $role = self::resolveRole($user);
        $_SESSION['role'] = $role;
        $user['role'] = $role;

        return $user;
    }

    /** @return array{id:int,user:string,email:string,role:string,accessActive:bool} */
    public static function publicUser(array $record): array
    {
        $role = self::resolveRole($record);
        $credDao = new UserCredentialsDao();
        $creds = $credDao->findByUserId($record['id']);
        $accessActive = $role === self::ROLE_ADMIN || $credDao->isAccessUsable($creds);

        return UserDto::toPublic($record, $role, $accessActive);
    }

    public static function require(): void
    {
        if (!self::check()) {
            JsonResponse::error('Не сте влезли', 401);
        }
    }

    public static function requireAdmin(): void
    {
        $user = self::user();

        if ($user === null) {
            JsonResponse::error('Не сте влезли', 401);
        }

        if ($user['role'] !== self::ROLE_ADMIN) {
            JsonResponse::error('Нямате права за тази операция', 403);
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

    /** @param array{id:int,user:string} $user */
    private static function resolveRole(array $user): string
    {
        $admins = require dirname(__DIR__, 2) . '/config/admins.php';
        $login = mb_strtolower($user['user']);
        $admins = array_map('mb_strtolower', $admins);

        if (in_array($login, $admins, true)) {
            return self::ROLE_ADMIN;
        }

        return self::ROLE_USER;
    }
}

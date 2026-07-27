<?php

class PasswordHasher
{
    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verify(string $plain, string $stored): bool
    {
        if ($stored === '') {
            return false;
        }

        if (self::isHashed($stored)) {
            return password_verify($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }

    public static function isHashed(string $value): bool
    {
        return password_get_info($value)['algo'] !== null;
    }

    public static function needsRehash(string $stored): bool
    {
        if (!self::isHashed($stored)) {
            return true;
        }

        return password_needs_rehash($stored, PASSWORD_DEFAULT);
    }
}

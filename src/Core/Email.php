<?php

class Email
{
    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public static function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** @param array<string, mixed> $input */
    public static function fromInput(array $input): string
    {
        return self::normalize((string) ($input['email'] ?? $input['user'] ?? ''));
    }
}

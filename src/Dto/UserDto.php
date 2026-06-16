<?php

class UserDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id' => (int) ($row->ID ?? $row->id ?? 0),
            'user' => (string) ($row->User ?? $row->user ?? ''),
            'password' => (string) ($row->Password ?? $row->password ?? ''),
        ];
    }

    /** @param array{id:int,user:string,password?:string} $user */
    public static function toPublic(array $user): array
    {
        return [
            'id' => $user['id'],
            'user' => $user['user'],
        ];
    }
}

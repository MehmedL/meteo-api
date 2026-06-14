<?php

class UserDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'user' => $row->user,
            'password' => $row->password, 
        ];
    }
}

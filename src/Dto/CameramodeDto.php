<?php

class CameramodeDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'mode' => $row->mode, 
        ];
    }
}

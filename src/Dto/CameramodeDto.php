<?php

class CameramodeDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id'   => (int) $row->ID,
            'mode' => $row->Mode,
        ];
    }
}

<?php

class DevicesDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id'     => (int) $row->ID,
            'device' => $row->Device,
        ];
    }
}

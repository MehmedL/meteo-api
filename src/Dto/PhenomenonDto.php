<?php

class PhenomenonDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id'     => (int) $row->ID,
            'phenom' => $row->Phenom,
        ];
    }
}

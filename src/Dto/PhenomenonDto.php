<?php

class PhenomenonDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'phenom' => $row->phenom, 
        ];
    }
}

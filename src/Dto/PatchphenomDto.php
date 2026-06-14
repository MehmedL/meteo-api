<?php

class PatchphenomDto
{
    public static function fromRow(object $row): array
    {
        return [
            'vfp' => (int) $row->vfp,
            'phenom' => (int) $row->phenom, 
        ];
    }
}

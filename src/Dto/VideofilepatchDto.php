<?php

class VideofilepatchDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'vFile' => $row->vFile, 
            'patch' => $row->patch,
        ];
    }
}

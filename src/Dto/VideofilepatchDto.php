<?php

class VideofilepatchDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id'    => (int) $row->ID,
            'patch' => $row->patch === null ? null : (int) $row->patch,
            'vfile' => $row->Vfile === null ? null : (int) $row->Vfile,
        ];
    }
}

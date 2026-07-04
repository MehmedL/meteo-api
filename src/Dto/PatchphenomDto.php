<?php

class PatchphenomDto
{
    public static function fromRow(object $row): array
    {
        return [
            'vfp'    => $row->VFP === null ? null : (int) $row->VFP,
            'phenom' => $row->Phenom === null ? null : (int) $row->Phenom,
        ];
    }
}

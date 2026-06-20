<?php

class UserCredentialsDto
{
    public static function fromRow(object $row): array
    {
        $nmax = $row->Nmax ?? $row->nmax ?? null;

        return [
            'userid' => (int) ($row->Userid ?? $row->userid ?? 0),
            'from'   => $row->From ?? $row->from ?? null,
            'to'     => $row->To ?? $row->to ?? null,
            'nused'  => (int) ($row->Nused ?? $row->nused ?? 0),
            'nmax'   => $nmax === null ? null : (int) $nmax,
        ];
    }
}

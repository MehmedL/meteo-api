<?php

class UserCredentialsDto
{
    public static function fromRow(object $row): array
    {
        return [
            'userid' => (int) $row->userid,
            'from'   => $row->from,
            'to'     => $row->to,
            'nused'  => (int) $row->nused,
            'nmax'   => (int) $row->nmax,
        ];
    }
}

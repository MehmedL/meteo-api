<?php

class VideofileDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id'       => (int) $row->ID,
            'filepath' => $row->filepath,
            'device'   => $row->device,
            'xGPS'     => (float) $row->xGPS,
            'yGPS'     => (float) $row->yGPS,
            'dir'      => $row->dir,
            'sdate'    => $row->sdate,
            'imgfile'  => $row->imgfile,
            'zipfile'  => $row->zipfile,
            'cammod'   => (int) $row->cammod,
        ];
    }
}

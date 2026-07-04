<?php

class VideofileDto
{
    public static function fromRow(object $row): array
    {
        return [
            'id'       => (int) $row->ID,
            'filepath' => $row->Filepath,
            'device'   => $row->Device,
            'xGPS'     => $row->xGPS === null ? null : (float) $row->xGPS,
            'yGPS'     => $row->yGPS === null ? null : (float) $row->yGPS,
            'dir'      => $row->Dir,
            'sdata'    => $row->Sdata,
            'imgfile'  => $row->Imgfile,
            'zipfile'  => $row->Zipfile,
            'cammod'   => $row->cammod === null ? null : (int) $row->cammod,
        ];
    }
}

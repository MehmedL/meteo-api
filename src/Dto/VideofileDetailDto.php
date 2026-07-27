<?php

class VideofileDetailDto
{
    
    public static function fromRow(array $row, array $patches): array
    {
        return [
            'id'         => (int) $row['ID'],
            'sdata'      => $row['Sdata'],
            'dir'        => $row['Dir'],
            'xGPS'       => $row['xGPS'] === null ? null : (float) $row['xGPS'],
            'yGPS'       => $row['yGPS'] === null ? null : (float) $row['yGPS'],
            'deviceName' => $row['DeviceName'],
            'cammodName' => $row['CammodName'],
            'hasVideo'   => !empty($row['Filepath']),
            'hasZip'     => !empty($row['Zipfile']),
            'patches'    => $patches,
        ];
    }
}
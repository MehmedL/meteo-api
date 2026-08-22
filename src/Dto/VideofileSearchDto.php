<?php

class VideofileSearchDto
{
    public static function fromRow(array $row): array
    {
        $phenomIds = array_filter(explode(',', (string) ($row['PhenomIds'] ?? '')), fn ($v) => $v !== '');
        $phenomNames = array_filter(explode(', ', (string) ($row['PhenomNames'] ?? '')), fn ($v) => $v !== '');

        $phenomena = [];
        foreach (array_values($phenomIds) as $i => $id) {
            $phenomena[] = [
                'id'     => (int) $id,
                'phenom' => $phenomNames[$i] ?? '',
            ];
        }

        return [
            'id'         => (int) $row['ID'],
            'sdata'      => $row['Sdata'],
            'dir'        => $row['Dir'],
            'xGPS'       => $row['xGPS'] === null ? null : (float) $row['xGPS'],
            'yGPS'       => $row['yGPS'] === null ? null : (float) $row['yGPS'],
            'deviceName' => $row['DeviceName'],
            'cammodName' => $row['CammodName'],
            'phenomena'  => $phenomena,
            'hasImage'   => !empty($row['Imgfile']),
            'hasVideo'   => !empty($row['Filepath']),
            'hasZip'     => !empty($row['Zipfile']),
        ];
    }
}
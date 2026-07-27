<?php

class VideofileSearchFilters
{
    private const DEFAULT_LIMIT = 25;
    private const MAX_LIMIT = 100;

    /** @return array{phenomIds:int[],ranges:array,deviceId:?int,dir:?string,sdataFrom:?string,sdataTo:?string} */
    public static function parse(): array
    {
        $phenomIds = array_map('intval', (array) ($_GET['phenom'] ?? []));

        $deviceParam = $_GET['device'] ?? null;
        $deviceId = ($deviceParam === null || $deviceParam === '') ? null : (int) $deviceParam;

        $dirParam = $_GET['dir'] ?? null;
        $dir = ($dirParam === null || $dirParam === '') ? null : $dirParam;

        $sdataFrom = $_GET['sdata_from'] ?? null;
        $sdataFrom = ($sdataFrom === null || $sdataFrom === '') ? null : $sdataFrom;
        if ($sdataFrom !== null && !self::isValidSdataFilter($sdataFrom)) {
            JsonResponse::error('Невалиден начален филтър за дата/час', 400);
        }

        $sdataTo = $_GET['sdata_to'] ?? null;
        $sdataTo = ($sdataTo === null || $sdataTo === '') ? null : $sdataTo;
        if ($sdataTo !== null && !self::isValidSdataFilter($sdataTo)) {
            JsonResponse::error('Невалиден краен филтър за дата/час', 400);
        }

        $ranges = [];
        foreach (VideofileDao::FILTERABLE_TXT_COLUMNS as $column) {
            $min = $_GET["{$column}_min"] ?? null;
            $max = $_GET["{$column}_max"] ?? null;

            if (($min === null || $min === '') && ($max === null || $max === '')) {
                continue;
            }

            $ranges[$column] = [
                'min' => ($min === null || $min === '') ? null : (float) $min,
                'max' => ($max === null || $max === '') ? null : (float) $max,
            ];
        }

        return [
            'phenomIds' => $phenomIds,
            'ranges'    => $ranges,
            'deviceId'  => $deviceId,
            'dir'       => $dir,
            'sdataFrom' => $sdataFrom,
            'sdataTo'   => $sdataTo,
        ];
    }

    /** @return array{limit:int,offset:int} */
    public static function parsePagination(): array
    {
        $limit = (int) ($_GET['limit'] ?? self::DEFAULT_LIMIT);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        return ['limit' => $limit, 'offset' => $offset];
    }

    private static function isValidSdataFilter(string $value): bool
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $d !== false && $d->format('Y-m-d H:i:s') === $value;
    }
}
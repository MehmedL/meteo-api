<?php

require_once __DIR__ . '/../../config/bootstrap.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponse::error('Method not allowed', 405);
}

Auth::require();

/**
 * Мапва имената на диапазонните филтри (VideofileDao::FILTERABLE_TXT_COLUMNS)
 * към ключовете, с които се пазят в TxtfileDto — огледало на
 * RANGE_KEY_TO_MEASUREMENT_KEY от app/controllers/search.js.
 */
const VFEXPORT_RANGE_TO_MEASUREMENT_KEY = [
    'Frame'  => 'frame',
    'Intens' => 'intens',
    'Satur'  => 'satur',
    'Dav'    => 'dav',
    'Ddev'   => 'ddev',
    'Dcct'   => 'dcct',
];

/** Реда на измервателните колони — огледало на MEASUREMENT_COLUMNS от app/templates/search.gjs. */
const VFEXPORT_MEASUREMENT_COLUMNS = [
    'frame', 'avR', 'avG', 'avB', 'avDCP', 'dvR', 'dvG', 'dvB', 'dvDCP',
    'intens', 'satur', 'stHUE', 'stxCCT', 'styCCT', 'stCCT', 'stCCTal',
    'bktHUE', 'bkxCCT', 'bkyCCT', 'bkCCT', 'bkCCTal', 'dav', 'ddev', 'dcct',
];

/** Дали дадено измерване удовлетворява всички приложени диапазонни филтри — огледало на measurementMatchesRanges от app/controllers/search.js. */
function vfexport_measurementMatchesRanges(array $measurement, array $ranges): bool
{
    foreach ($ranges as $column => $range) {
        $key = VFEXPORT_RANGE_TO_MEASUREMENT_KEY[$column] ?? $column;
        $value = $measurement[$key] ?? null;

        if ($value === null) {
            return false;
        }
        if ($range['min'] !== null && $value < $range['min']) {
            return false;
        }
        if ($range['max'] !== null && $value > $range['max']) {
            return false;
        }
    }

    return true;
}

/**
 * Excel не позволява \ / ? * [ ] : в имена на шийтове, ограничава ги до 31
 * символа и изисква уникалност — тук се погрижваме и за трите.
 */
function vfexport_sheetNameFor(?string $rawName, array &$usedNames): string
{
    $name = trim((string) $rawName);
    if ($name === '') {
        $name = 'Без устройство';
    }

    $name = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name);
    $name = trim(mb_substr($name, 0, 31));
    if ($name === '') {
        $name = 'Без устройство';
    }

    $base = mb_substr($name, 0, 28);
    $candidate = mb_substr($name, 0, 31);
    $suffix = 2;
    while (isset($usedNames[$candidate])) {
        $candidate = $base . ' (' . $suffix . ')';
        $suffix++;
    }
    $usedNames[$candidate] = true;

    return $candidate;
}

try {
    $filters = VideofileSearchFilters::parse();

    $videoDao = new VideofileDao();
    $items = array_map([VideofileSearchDto::class, 'fromRow'], $videoDao->search($filters));

    $videoIds = array_column($items, 'id');

    $patchDao = new VideofilepatchDao();
    $patchesByVfile = $patchDao->findByVfileIds($videoIds);

    $patchIds = [];
    foreach ($patchesByVfile as $patches) {
        foreach ($patches as $patch) {
            $patchIds[] = $patch['id'];
        }
    }

    $measurementsByVfp = (new TxtfileDao())->findByVfpIds($patchIds);

    $spreadsheet = new Spreadsheet();
    $summarySheet = $spreadsheet->getActiveSheet();
    $summarySheet->setTitle('Резултати');

    $summaryHeaders = [
        'Устройство', 'Дата и час', 'Посока', 'X', 'Y', 'Явления', 'Пач',
    ];
    $summaryLastColumn = Coordinate::stringFromColumnIndex(count($summaryHeaders));
    $summarySheet->fromArray($summaryHeaders, null, 'A1', true);
    $summarySheet->getStyle("A1:{$summaryLastColumn}1")->getFont()->setBold(true);
    $summarySheet->freezePane('A2');

    // Измервателните колони (Frame..Dcct) не се дублират в основния шийт —
    // отиват в отделен шийт по устройство, идентифицирани само по Пач.
    $measurementHeaders = array_merge(
        ['Пач'],
        [
            'Frame', 'avR', 'avG', 'avB', 'avDCP', 'dvR', 'dvG', 'dvB', 'dvDCP',
            'Intens', 'Satur', 'stHUE', 'stxCCT', 'styCCT', 'stCCT', 'stCCTal',
            'bktHUE', 'bkxCCT', 'bkyCCT', 'bkCCT', 'bkCCTal', 'Dav', 'Ddev', 'Dcct',
        ]
    );
    $measurementLastColumn = Coordinate::stringFromColumnIndex(count($measurementHeaders));

    $deviceSheets = [];
    $deviceRowIndex = [];
    $usedSheetNames = [];

    $summaryRowIndex = 2;
    foreach ($items as $item) {
        $videoColumns = [
            $item['deviceName'],
            $item['sdata'],
            $item['dir'],
            $item['xGPS'],
            $item['yGPS'],
            implode(', ', array_column($item['phenomena'], 'phenom')),
        ];

        $patches = $patchesByVfile[$item['id']] ?? [];

        if ($patches === []) {
            $summarySheet->fromArray(
                array_merge($videoColumns, [null]),
                null,
                "A{$summaryRowIndex}",
                true
            );
            $summaryRowIndex++;
            continue;
        }

        $deviceName = $item['deviceName'] ?? '';

        foreach ($patches as $patch) {
            $summarySheet->fromArray(
                array_merge($videoColumns, [$patch['patch']]),
                null,
                "A{$summaryRowIndex}",
                true
            );
            $summaryRowIndex++;

            $measurements = $measurementsByVfp[$patch['id']] ?? [];
            $matching = $filters['ranges'] === []
                ? $measurements
                : array_filter(
                    $measurements,
                    fn (array $m) => vfexport_measurementMatchesRanges($m, $filters['ranges'])
                );

            if ($matching === []) {
                continue;
            }

            if (!isset($deviceSheets[$deviceName])) {
                $sheetName = vfexport_sheetNameFor($deviceName, $usedSheetNames);
                $deviceSheet = $spreadsheet->createSheet();
                $deviceSheet->setTitle($sheetName);
                $deviceSheet->fromArray($measurementHeaders, null, 'A1', true);
                $deviceSheet->getStyle("A1:{$measurementLastColumn}1")->getFont()->setBold(true);
                $deviceSheet->freezePane('A2');

                $deviceSheets[$deviceName] = $deviceSheet;
                $deviceRowIndex[$deviceName] = 2;
            }

            $deviceSheet = $deviceSheets[$deviceName];

            foreach ($matching as $measurement) {
                $measurementRow = array_merge(
                    [$patch['patch']],
                    array_map(fn (string $key) => $measurement[$key], VFEXPORT_MEASUREMENT_COLUMNS)
                );
                $deviceSheet->fromArray(
                    $measurementRow,
                    null,
                    "A{$deviceRowIndex[$deviceName]}",
                    true
                );
                $deviceRowIndex[$deviceName]++;
            }
        }
    }

    foreach (range(1, count($summaryHeaders)) as $i) {
        $summarySheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
    }
    foreach ($deviceSheets as $deviceSheet) {
        foreach (range(1, count($measurementHeaders)) as $i) {
            $deviceSheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    $spreadsheet->setActiveSheetIndex(0);

    $filename = 'videofile_export_' . date('Y-m-d_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    (new Xlsx($spreadsheet))->save('php://output');
    exit;
} catch (Throwable $e) {
    JsonResponse::exception($e);
}
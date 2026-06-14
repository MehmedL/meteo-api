<?php

/**
 * Парсва текстов файл с кадри (frame data) и го превръща в редове за таблица textfile.
 *
 * Формат на файла:
 *   ред 1: video file name: ...        (пропуска се)
 *   ред 2: header (frame N | avrage..)  (пропуска се)
 *   ред 3: под-header (R G B DCP...)    (пропуска се)
 *   ред 4+: данни — 25 стойности, разделени с интервали/табове
 *
 * Използваме колони 1-24 от файла; последната (patchN) се изхвърля.
 * VFP не се попълва — той е auto_increment в базата.
 */
class TextfileImporter
{
    private const SKIP_LINES = 3;

    /**
     * Колоните в таблицата textfile в реда, в който идват стойностите от файла.
     * Файл колона 1 -> Frame, колона 2 -> avR, ... колона 24 -> Dcct.
     */
    private const COLUMNS = [
        'Frame',
        'avR', 'avG', 'avB', 'avDCP',
        'dvR', 'dvG', 'dvB', 'dvDCP',
        'Intens', 'Satur',
        'stHUE', 'stxCCT', 'styCCT', 'stCCT', 'stCCTal',
        'bktHUE', 'bkxCCT', 'bkyCCT', 'bkCCT', 'bkCCTal',
        'Dav', 'Ddev', 'Dcct',
    ];

    /**
     * @return array<int, array<string, string>> Масив от редове (колона => стойност)
     */
    public static function parse(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $needed = count(self::COLUMNS);
        $rows = [];

        foreach ($lines as $index => $line) {
            if ($index < self::SKIP_LINES) {
                continue;
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $tokens = preg_split('/\s+/', $line);
            if (count($tokens) < $needed) {
                continue;
            }

            $row = [];
            foreach (self::COLUMNS as $position => $column) {
                $row[$column] = $tokens[$position];
            }

            $rows[] = $row;
        }

        return $rows;
    }
}

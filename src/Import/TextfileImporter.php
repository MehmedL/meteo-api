<?php
class TextfileImporter
{
    private const SKIP_LINES = 3;
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

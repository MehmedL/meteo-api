<?php

class TxtfileDao extends BaseDao
{
    protected string $table = 'textfile';
    protected string $dtoClass = TxtfileDto::class;

    /**
     * Вмъква парснатите редове на txt файл, вързани към даден пач (VFP).
     * Не отваря собствена транзакция — предназначено е за викане в рамките
     * на външна транзакция (виж VideoImportService). Връща броя редове.
     *
     * @param array<int, array<string, string>> $rows Редовете от TextfileImporter::parse
     */
    public function insertForPatch(int $vfp, array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $this->insert(array_merge(['VFP' => $vfp], $row));
            $count++;
        }

        return $count;
    }
}

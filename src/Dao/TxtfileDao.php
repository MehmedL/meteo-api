<?php

class TxtfileDao extends BaseDao
{
    protected string $table = 'textfile';
    protected string $dtoClass = TxtfileDto::class;

    
    public function insertForPatch(int $vfp, array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $this->insert(array_merge(['VFP' => $vfp], $row));
            $count++;
        }

        return $count;
    }

    public function findByVfp(int $vfp): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `textfile` WHERE `VFP` = :vfp ORDER BY `Frame`'
        );
        $stmt->execute(['vfp' => $vfp]);

        return array_map(
            fn (object $row) => TxtfileDto::fromRow($row),
            $stmt->fetchAll()
        );
    }

    
    public function findByVfpIds(array $vfpIds): array
    {
        $vfpIds = array_values(array_unique(array_map('intval', $vfpIds)));
        if ($vfpIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($vfpIds as $i => $id) {
            $key = "vfp{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = $id;
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM `textfile` WHERE `VFP` IN (' . implode(', ', $placeholders) . ') ORDER BY `VFP`, `Frame`'
        );
        $stmt->execute($params);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $measurement = TxtfileDto::fromRow($row);
            $grouped[$measurement['vfp']][] = $measurement;
        }

        return $grouped;
    }
}

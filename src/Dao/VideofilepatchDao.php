<?php

class VideofilepatchDao extends BaseDao
{
    protected string $table = 'videofilepatch';
    protected string $dtoClass = VideofilepatchDto::class;

    public function create(int $patch, int $vfile): int
    {
        return $this->insert(['patch' => $patch, 'Vfile' => $vfile]);
    }

    public function findByVfile(int $vfileId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM `videofilepatch` WHERE `Vfile` = :vfile ORDER BY `patch`'
        );
        $stmt->execute(['vfile' => $vfileId]);

        return array_map(
            fn (object $row) => VideofilepatchDto::fromRow($row),
            $stmt->fetchAll()
        );
    }

   
    public function findByVfileIds(array $vfileIds): array
    {
        $vfileIds = array_values(array_unique(array_map('intval', $vfileIds)));
        if ($vfileIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($vfileIds as $i => $id) {
            $key = "vfile{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = $id;
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM `videofilepatch` WHERE `Vfile` IN (' . implode(', ', $placeholders) . ') ORDER BY `Vfile`, `patch`'
        );
        $stmt->execute($params);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $patch = VideofilepatchDto::fromRow($row);
            $grouped[$patch['vfile']][] = $patch;
        }

        return $grouped;
    }
}

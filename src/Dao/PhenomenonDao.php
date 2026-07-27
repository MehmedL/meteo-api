<?php

class PhenomenonDao extends BaseDao
{
    protected string $table = 'phenomenon';
    protected string $dtoClass = PhenomenonDto::class;

    public function findByName(string $phenom): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `phenomenon` WHERE `Phenom` = :phenom LIMIT 1');
        $stmt->execute(['phenom' => trim($phenom)]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return PhenomenonDto::fromRow($row);
    }

    public function create(string $phenom): int
    {
        return $this->insert(['Phenom' => trim($phenom)]);
    }
}

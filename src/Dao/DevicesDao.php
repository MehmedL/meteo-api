<?php

class DevicesDao extends BaseDao
{
    protected string $table = 'devices';
    protected string $dtoClass = DevicesDto::class;

    public function findByName(string $device): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `devices` WHERE `Device` = :device LIMIT 1');
        $stmt->execute(['device' => trim($device)]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return DevicesDto::fromRow($row);
    }

    public function create(string $device): int
    {
        return $this->insert(['Device' => trim($device)]);
    }
}

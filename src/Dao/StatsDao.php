<?php

class StatsDao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function totals(): array
    {
        return [
            'videofiles' => (int) $this->db->query('SELECT COUNT(*) FROM `videofile`')->fetchColumn(),
            'devices'    => (int) $this->db->query('SELECT COUNT(*) FROM `devices`')->fetchColumn(),
            'phenomena'  => (int) $this->db->query('SELECT COUNT(*) FROM `phenomenon`')->fetchColumn(),
        ];
    }

    public function countByPhenomenon(): array
    {
        $stmt = $this->db->query(
            'SELECT phenomenon.ID AS id, phenomenon.Phenom AS phenom,
                    COUNT(DISTINCT videofilepatch.Vfile) AS cnt
             FROM `phenomenon`
             LEFT JOIN `patchphenom` ON patchphenom.Phenom = phenomenon.ID
             LEFT JOIN `videofilepatch` ON videofilepatch.ID = patchphenom.VFP
             GROUP BY phenomenon.ID, phenomenon.Phenom
             ORDER BY cnt DESC'
        );

        return array_map(
            fn (object $row) => [
                'id'     => (int) $row->id,
                'phenom' => $row->phenom,
                'count'  => (int) $row->cnt,
            ],
            $stmt->fetchAll()
        );
    }

    public function countByDevice(): array
    {
        $stmt = $this->db->query(
            'SELECT devices.ID AS id, devices.Device AS device,
                    COUNT(videofile.ID) AS cnt
             FROM `devices`
             LEFT JOIN `videofile` ON CAST(videofile.Device AS UNSIGNED) = devices.ID
             GROUP BY devices.ID, devices.Device
             ORDER BY cnt DESC'
        );

        return array_map(
            fn (object $row) => [
                'id'     => (int) $row->id,
                'device' => $row->device,
                'count'  => (int) $row->cnt,
            ],
            $stmt->fetchAll()
        );
    }
}

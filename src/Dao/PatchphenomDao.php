<?php

class PatchphenomDao extends BaseDao
{
    protected string $table = 'patchphenom';

    public function add(int $vfp, int $phenom): void
    {
        $this->insert(['VFP' => $vfp, 'Phenom' => $phenom]);
    }

    public function findPhenomenaByVfp(int $vfp): array
    {
        $stmt = $this->db->prepare(
            'SELECT phenomenon.ID, phenomenon.Phenom
             FROM `patchphenom`
             JOIN `phenomenon` ON phenomenon.ID = patchphenom.Phenom
             WHERE patchphenom.VFP = :vfp
             ORDER BY phenomenon.Phenom'
        );
        $stmt->execute(['vfp' => $vfp]);

        return array_map(
            fn (object $row) => ['id' => (int) $row->ID, 'phenom' => $row->Phenom],
            $stmt->fetchAll()
        );
    }
}

<?php

/**
 * Записва цял videofile запис заедно с пачовете, явленията и парснатите
 * txt редове — атомарно, в една транзакция върху споделената връзка.
 */
class VideoImportService
{
    private PDO $db;
    private VideofileDao $videofiles;
    private VideofilepatchDao $patchesDao;
    private PatchphenomDao $patchPhenomDao;
    private TxtfileDao $txtfilesDao;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->videofiles = new VideofileDao();
        $this->patchesDao = new VideofilepatchDao();
        $this->patchPhenomDao = new PatchphenomDao();
        $this->txtfilesDao = new TxtfileDao();
    }

    /**
     * @param array $meta Ключове: Filepath, Device, xGPS, yGPS, Dir, Sdata, Imgfile, Zipfile, cammod
     * @param array $patches Списък от ['patch'=>int, 'phenomena'=>int[], 'rows'=>array<int,array>]
     * @return array{videofileId:int, patches:int, phenomena:int, textrows:int}
     */
    public function save(array $meta, array $patches): array
    {
        $this->db->beginTransaction();
        try {
            $videofileId = $this->videofiles->create($meta);

            $patchCount = 0;
            $phenomCount = 0;
            $rowCount = 0;

            foreach ($patches as $patch) {
                $vfp = $this->patchesDao->create((int) $patch['patch'], $videofileId);
                $patchCount++;

                foreach ($patch['phenomena'] as $phenomId) {
                    $this->patchPhenomDao->add($vfp, (int) $phenomId);
                    $phenomCount++;
                }

                if (!empty($patch['rows'])) {
                    $rowCount += $this->txtfilesDao->insertForPatch($vfp, $patch['rows']);
                }
            }

            $this->db->commit();

            return [
                'videofileId' => $videofileId,
                'patches'     => $patchCount,
                'phenomena'   => $phenomCount,
                'textrows'    => $rowCount,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}

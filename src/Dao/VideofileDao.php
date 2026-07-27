<?php

class VideofileDao extends BaseDao
{
    protected string $table = 'videofile';
    protected string $dtoClass = VideofileDto::class;

    public const FILTERABLE_TXT_COLUMNS = [
        'Frame',
        'avR', 'avG', 'avB', 'avDCP',
        'dvR', 'dvG', 'dvB', 'dvDCP',
        'Intens', 'Satur',
        'stHUE', 'stxCCT', 'styCCT', 'stCCT', 'stCCTal',
        'bktHUE', 'bkxCCT', 'bkyCCT', 'bkCCT', 'bkCCTal',
        'Dav', 'Ddev', 'Dcct',
    ];

    private const MAX_RESULTS = 2000;

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    
    public function search(array $filters): array
    {
        $where = [];
        $params = [];

        $deviceId = $filters['deviceId'] ?? null;
        if ($deviceId !== null) {
            $where[] = 'CAST(videofile.Device AS UNSIGNED) = :deviceId';
            $params['deviceId'] = $deviceId;
        }

        $dir = $filters['dir'] ?? null;
        if ($dir !== null) {
            $where[] = 'videofile.Dir = :dir';
            $params['dir'] = $dir;
        }

        $sdataFrom = $filters['sdataFrom'] ?? null;
        if ($sdataFrom !== null) {
            $where[] = 'videofile.Sdata >= :sdataFrom';
            $params['sdataFrom'] = $sdataFrom;
        }

        $sdataTo = $filters['sdataTo'] ?? null;
        if ($sdataTo !== null) {
            $where[] = 'videofile.Sdata <= :sdataTo';
            $params['sdataTo'] = $sdataTo;
        }

        $phenomIds = array_values(array_unique(array_map('intval', $filters['phenomIds'] ?? [])));
        if ($phenomIds !== []) {
            $placeholders = [];
            foreach ($phenomIds as $i => $id) {
                $key = "phenom{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $id;
            }
            $where[] = 'videofile.ID IN (
                SELECT vp.Vfile FROM videofilepatch vp
                JOIN patchphenom pp ON pp.VFP = vp.ID
                WHERE pp.Phenom IN (' . implode(', ', $placeholders) . ')
            )';
        }

        $ranges = $filters['ranges'] ?? [];
        $rangeIndex = 0;
        foreach (self::FILTERABLE_TXT_COLUMNS as $column) {
            $range = $ranges[$column] ?? null;
            if ($range === null) {
                continue;
            }

            $min = $range['min'] ?? null;
            $max = $range['max'] ?? null;
            if ($min === null && $max === null) {
                continue;
            }

            $conditions = [];
            if ($min !== null) {
                $key = "rmin{$rangeIndex}";
                $conditions[] = "tf.`{$column}` >= :{$key}";
                $params[$key] = (float) $min;
            }
            if ($max !== null) {
                $key = "rmax{$rangeIndex}";
                $conditions[] = "tf.`{$column}` <= :{$key}";
                $params[$key] = (float) $max;
            }
            $rangeIndex++;

            $where[] = 'videofile.ID IN (
                SELECT vp.Vfile FROM videofilepatch vp
                JOIN textfile tf ON tf.VFP = vp.ID
                WHERE ' . implode(' AND ', $conditions) . '
            )';
        }

        $sql = 'SELECT videofile.*,
                       devices.Device AS DeviceName,
                       cameramode.Mode AS CammodName,
                       GROUP_CONCAT(DISTINCT phenomenon.ID) AS PhenomIds,
                       GROUP_CONCAT(DISTINCT phenomenon.Phenom SEPARATOR ", ") AS PhenomNames
                FROM videofile
                LEFT JOIN devices ON devices.ID = CAST(videofile.Device AS UNSIGNED)
                LEFT JOIN cameramode ON cameramode.ID = videofile.cammod
                LEFT JOIN videofilepatch ON videofilepatch.Vfile = videofile.ID
                LEFT JOIN patchphenom ON patchphenom.VFP = videofilepatch.ID
                LEFT JOIN phenomenon ON phenomenon.ID = patchphenom.Phenom';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY videofile.ID ORDER BY videofile.ID DESC LIMIT ' . self::MAX_RESULTS;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDetailById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT videofile.*,
                    devices.Device AS DeviceName,
                    cameramode.Mode AS CammodName
             FROM videofile
             LEFT JOIN devices ON devices.ID = CAST(videofile.Device AS UNSIGNED)
             LEFT JOIN cameramode ON cameramode.ID = videofile.cammod
             WHERE videofile.ID = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}

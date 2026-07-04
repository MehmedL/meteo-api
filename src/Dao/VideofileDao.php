<?php

class VideofileDao extends BaseDao
{
    protected string $table = 'videofile';
    protected string $dtoClass = VideofileDto::class;

    /**
     * Вмъква нов videofile ред и връща новото ID.
     * $data ключове (по колоните на таблицата):
     * Filepath, Device, xGPS, yGPS, Dir, Sdata, Imgfile, Zipfile, cammod
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }
}

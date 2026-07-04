<?php

class VideofilepatchDao extends BaseDao
{
    protected string $table = 'videofilepatch';
    protected string $dtoClass = VideofilepatchDto::class;

    /** Създава пач за даден videofile и връща новото ID. */
    public function create(int $patch, int $vfile): int
    {
        return $this->insert(['patch' => $patch, 'Vfile' => $vfile]);
    }
}

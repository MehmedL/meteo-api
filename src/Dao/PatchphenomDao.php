<?php

class PatchphenomDao extends BaseDao
{
    protected string $table = 'patchphenom';
    protected string $dtoClass = PatchphenomDto::class;

    /** Свързва пач (VFP = videofilepatch.ID) с явление (Phenom = phenomenon.ID). */
    public function add(int $vfp, int $phenom): void
    {
        $this->insert(['VFP' => $vfp, 'Phenom' => $phenom]);
    }
}

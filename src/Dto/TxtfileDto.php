<?php

class TxtfileDto
{
    public static function fromRow(object $row): array
    {
        return [
            'vfp'     => (int) $row->vfp,
            'frame'   => (int) $row->frame,
            'avR'     => (float) $row->avR,
            'avG'     => (float) $row->avG,
            'avB'     => (float) $row->avB,
            'avDCP'   => (float) $row->avDCP,
            'dvR'     => (float) $row->dvR,
            'dvG'     => (float) $row->dvG,
            'dvB'     => (float) $row->dvB,
            'dvDCP'   => (float) $row->dvDCP,
            'intens'  => (float) $row->intens,
            'satur'   => (float) $row->satur,
            'stHUE'   => (int) $row->stHUE,
            'stxCCT'  => (float) $row->stxCCT,
            'styCCT'  => (float) $row->styCCT,
            'stCCT'   => (int) $row->stCCT,
            'stCCTal' => (int) $row->stCCTal,
            'bktHUE'  => (int) $row->bktHUE,
            'bkxCCT'  => (float) $row->bkxCCT,
            'bkyCCT'  => (float) $row->bkyCCT,
            'bkCCT'   => (int) $row->bkCCT,
            'bkCCTal' => (int) $row->bkCCTal,
            'dav'     => (float) $row->dav,
            'ddev'    => (float) $row->ddev,
            'dcct'    => (int) $row->dcct,
        ];
    }
}

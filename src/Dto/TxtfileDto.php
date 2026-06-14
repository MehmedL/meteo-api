<?php

class TxtfileDto
{
    public static function fromRow(object $row): array
    {
        return [
            'vfp'     => (int) $row->VFP,
            'frame'   => (int) $row->Frame,
            'avR'     => (float) $row->avR,
            'avG'     => (float) $row->avG,
            'avB'     => (float) $row->avB,
            'avDCP'   => (float) $row->avDCP,
            'dvR'     => (float) $row->dvR,
            'dvG'     => (float) $row->dvG,
            'dvB'     => (float) $row->dvB,
            'dvDCP'   => (float) $row->dvDCP,
            'intens'  => (float) $row->Intens,
            'satur'   => (float) $row->Satur,
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
            'dav'     => (float) $row->Dav,
            'ddev'    => (float) $row->Ddev,
            'dcct'    => (int) $row->Dcct,
        ];
    }
}

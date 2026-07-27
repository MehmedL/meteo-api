<?php

return [
    'baseDir'      => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads',

    'kinds' => [
        'video' => [
            'dir'     => 'videos',
            'maxSize' => 1024 * 1024 * 1024, // 1 GB
            'ext'     => ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'],
            'mime'    => [
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',
                'video/webm',
                'application/octet-stream', 
            ],
        ],
        'image' => [
            'dir'     => 'images',
            'maxSize' => 25 * 1024 * 1024, // 25 MB
            'ext'     => ['jpg', 'jpeg', 'png', 'webp'],
            'mime'    => [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
        ],
        'zip' => [
            'dir'     => 'zips',
            'maxSize' => 512 * 1024 * 1024, // 512 MB
            'ext'     => ['zip'],
            'mime'    => [
                'application/zip',
                'application/x-zip-compressed',
                'multipart/x-zip',
                'application/octet-stream',
            ],
        ],
    ],
];

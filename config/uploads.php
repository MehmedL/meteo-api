<?php

// Конфигурация за качване на файлове (видео, изображение, zip).
// baseDir е абсолютният път на диска; storedPrefix е това, което се пази в базата.
return [
    // Абсолютна базова папка за качванията.
    'baseDir'      => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads',

    // Префикс, който се записва в базата пред относителния път (с наклонени черти напред).
    'storedPrefix' => 'uploads',

    // Дефиниции по вид файл.
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
                'application/octet-stream', // finfo често връща това за някои видео контейнери
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

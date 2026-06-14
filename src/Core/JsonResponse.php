<?php

class JsonResponse
{
    public static function success(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data'    => $data,
            'error'   => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $code = 500): never
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'data'    => null,
            'error'   => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

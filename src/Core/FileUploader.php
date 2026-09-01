<?php


class FileUploader
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/uploads.php';
    }

    /**
     * @param array $file Елемент от $_FILES (напр. $_FILES['video'])
     * @param string $kind 'video' | 'image' | 'zip'
     * @return string Относителният път за запис в базата (напр. "uploads/videos/ab12.mp4")
     * @throws RuntimeException при невалиден вход или грешка при записа
     */
    public function store(array $file, string $kind): string
    {
        $rules = $this->config['kinds'][$kind] ?? null;
        if ($rules === null) {
            throw new RuntimeException("Неизвестен тип файл: {$kind}");
        }

        $this->assertUploadOk($file['error'] ?? UPLOAD_ERR_NO_FILE, $kind);

        $tmp = $file['tmp_name'] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException("Невалиден качен файл ({$kind}).");
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException("Празен файл ({$kind}).");
        }
        if ($size > $rules['maxSize']) {
            $mb = (int) round($rules['maxSize'] / (1024 * 1024));
            throw new RuntimeException("Файлът ({$kind}) е твърде голям. Максимум {$mb} MB.");
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $rules['ext'], true)) {
            $allowed = implode(', ', $rules['ext']);
            throw new RuntimeException("Недопустимо разширение за {$kind}. Позволени: {$allowed}.");
        }

        $mime = $this->detectMime($tmp);
        if (!in_array($mime, $rules['mime'], true)) {
            throw new RuntimeException("Недопустим тип съдържание за {$kind} ({$mime}).");
        }

        if ($kind === 'image' && @getimagesize($tmp) === false) {
            throw new RuntimeException('Файлът не е валидно изображение.');
        }

        $this->verifySignature($tmp, $kind, $ext);

        $targetDir = $this->config['baseDir'] . DIRECTORY_SEPARATOR . $rules['dir'];
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Папката за качване не може да бъде създадена.');
        }

        $originalName = pathinfo((string) ($file['name'] ?? ''), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $originalName);
        if ($safeName === '') {
            $safeName = 'file';
        }

        $prefix = bin2hex(random_bytes(8));

        $name = $prefix . '__' . $safeName . '.' . $ext;
        $absolute = $targetDir . DIRECTORY_SEPARATOR . $name;

        $suffix = 1;
        while (file_exists($absolute)) {
            $name = $prefix . '__' . $safeName . '_' . $suffix . '.' . $ext;
            $absolute = $targetDir . DIRECTORY_SEPARATOR . $name;
            $suffix++;
        }

        if (!move_uploaded_file($tmp, $absolute)) {
            throw new RuntimeException("Файлът ({$kind}) не може да бъде записан.");
        }

        if ($kind === 'zip') {
            $this->assertSafeZip($absolute);
        }

        return $absolute;
    }

    public function delete(?string $storedPath): void
    {
        if ($storedPath === null || $storedPath === '') {
            return;
        }

        $base = realpath($this->config['baseDir']);
        $real = realpath($storedPath);

        if ($base !== false && $real !== false && str_starts_with($real, $base) && is_file($real)) {
            @unlink($real);
        }
    }

    private function detectMime(string $path): string
    {
        if (!class_exists('finfo')) {
            return 'application/octet-stream';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return $mime !== false ? $mime : 'application/octet-stream';
    }

    private function verifySignature(string $path, string $kind, string $ext): void
    {
        if ($kind === 'image') {
            return; // вече покрито от точния MIME whitelist + getimagesize().
        }

        $head = @file_get_contents($path, false, null, 0, 16);
        if ($head === false || $head === '') {
            throw new RuntimeException('Файлът не може да бъде прочетен за проверка на съдържанието.');
        }

        $ok = $kind === 'zip' ? $this->looksLikeZip($head) : $this->looksLikeVideo($head);

        if (!$ok) {
            throw new RuntimeException("Съдържанието на файла не отговаря на заявения формат ({$kind}).");
        }
    }

    private function looksLikeZip(string $head): bool
    {
        $signature = substr($head, 0, 4);

        return in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
    }

    private function looksLikeVideo(string $head): bool
    {
        if (substr($head, 4, 4) === 'ftyp') {
            return true;
        }

        if (substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'AVI ') {
            return true;
        }

        if (substr($head, 0, 4) === "\x1A\x45\xDF\xA3") {
            return true;
        }

        return false;
    }

    private function assertSafeZip(string $absolute): void
    {
        $maxEntries = 20000;
        $maxUncompressedTotal = 5 * 1024 * 1024 * 1024; // 5 GB

        $zip = new ZipArchive();
        if ($zip->open($absolute) !== true) {
            @unlink($absolute);
            throw new RuntimeException('Невалиден zip архив.');
        }

        $count = $zip->numFiles;
        if ($count > $maxEntries) {
            $zip->close();
            @unlink($absolute);
            throw new RuntimeException('Zip архивът съдържа твърде много файлове.');
        }

        $totalUncompressed = 0;
        for ($i = 0; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }

            $name = (string) $stat['name'];
            if (
                str_contains($name, '..')
                || str_starts_with($name, '/')
                || preg_match('#^[A-Za-z]:[\\\\/]#', $name) === 1
            ) {
                $zip->close();
                @unlink($absolute);
                throw new RuntimeException('Zip архивът съдържа недопустим път: ' . $name);
            }

            $totalUncompressed += (int) $stat['size'];
            if ($totalUncompressed > $maxUncompressedTotal) {
                $zip->close();
                @unlink($absolute);
                throw new RuntimeException('Zip архивът е твърде голям след разархивиране.');
            }
        }

        $zip->close();
    }

    private function assertUploadOk(int $error, string $kind): void
    {
        switch ($error) {
            case UPLOAD_ERR_OK:
                return;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException("Файлът ({$kind}) надвишава допустимия размер.");
            case UPLOAD_ERR_PARTIAL:
                throw new RuntimeException("Файлът ({$kind}) е качен частично.");
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException("Липсва файл ({$kind}).");
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
            default:
                throw new RuntimeException("Грешка при качване на файл ({$kind}).");
        }
    }
}

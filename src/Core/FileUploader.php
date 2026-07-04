<?php

/**
 * Сигурно качване на файлове (видео, изображение, zip).
 *
 * Проверки: UPLOAD_ERR, реално качен файл, лимит за размер, allowlist по
 * разширение, реален MIME тип (finfo), допълнителна проверка за изображения.
 * Името на файла се генерира сървърно (никога не се доверяваме на подаденото),
 * което предпазва от path traversal и презаписване.
 */
class FileUploader
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/uploads.php';
    }

    /**
     * Съхранява един качен файл от $_FILES.
     *
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

        $targetDir = $this->config['baseDir'] . DIRECTORY_SEPARATOR . $rules['dir'];
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Папката за качване не може да бъде създадена.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $absolute = $targetDir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($tmp, $absolute)) {
            throw new RuntimeException("Файлът ({$kind}) не може да бъде записан.");
        }

        return $this->config['storedPrefix'] . '/' . $rules['dir'] . '/' . $name;
    }

    /** Изтрива вече записан файл по относителния му път (за rollback). Безопасно е при липса. */
    public function delete(?string $storedPath): void
    {
        if ($storedPath === null || $storedPath === '') {
            return;
        }

        $base = realpath($this->config['baseDir']);
        if ($base === false) {
            return;
        }

        $relative = ltrim(str_replace($this->config['storedPrefix'], '', $storedPath), '/\\');
        $candidate = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($candidate);

        // Гарантираме, че пътят е вътре в базовата папка (без traversal).
        if ($real !== false && str_starts_with($real, $base) && is_file($real)) {
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

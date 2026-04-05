<?php
declare(strict_types=1);

namespace App\Services;

final class FileUploader
{
    public function __construct(private readonly array $config)
    {
    }

    public static function fromConfig(string $configPath): self
    {
        $config = require $configPath;

        return new self($config);
    }

    public function handleRequest(string $fieldName = 'file'): array
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $this->error('METHOD_NOT_ALLOWED', 'მხოლოდ POST მოთხოვნაა დაშვებული.');
        }

        if (!isset($_FILES[$fieldName])) {
            return $this->error('NO_FILE', 'ფაილი არ არის არჩეული.');
        }

        return $this->upload($_FILES[$fieldName]);
    }

    public function responseStatusCode(array $result): int
    {
        if (($result['success'] ?? false) === true) {
            return 200;
        }

        return match ($result['errorCode'] ?? 'UPLOAD_ERROR') {
            'METHOD_NOT_ALLOWED' => 405,
            'SIZE_ERROR' => 413,
            'TYPE_ERROR' => 415,
            default => 400,
        };
    }

    public function upload(array $file): array
    {
        if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            return $this->error('NO_FILE', 'ფაილი ვერ მოიძებნა.');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return $this->mapPhpUploadError((int) $file['error']);
        }

        if ((int) $file['size'] > (int) $this->config['max_file_size']) {
            return $this->error('SIZE_ERROR', 'ფაილის ზომა ლიმიტს აღემატება.');
        }

        $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_extensions'], true)) {
            return $this->error('TYPE_ERROR', 'ფაილის ტიპი დაუშვებელია.');
        }

        $mimeType = $this->detectMimeType((string) $file['tmp_name']);
        if ($mimeType === null || !in_array($mimeType, $this->config['allowed_mime_types'], true)) {
            return $this->error('TYPE_ERROR', 'ფაილის MIME ტიპი დაუშვებელია.');
        }

        $uploadDir = (string) $this->config['upload_dir'];
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return $this->error('SERVER_ERROR', 'ატვირთვის დირექტორია ვერ შეიქმნა.');
        }

        $baseName = (string) pathinfo((string) $file['name'], PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $baseName) ?: 'file';
        $storedFileName = sprintf(
            '%s-%s-%s.%s',
            $safeBaseName,
            date('Ymd-His'),
            bin2hex(random_bytes(3)),
            $extension
        );

        $destination = $uploadDir . $storedFileName;
        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            return $this->error('MOVE_ERROR', 'ფაილის შენახვა ვერ მოხერხდა.');
        }

        clearstatcache(true, $destination);
        $storedSize = filesize($destination) ?: (int) $file['size'];

        return [
            'success' => true,
            'fileName' => $storedFileName,
            'originalName' => (string) $file['name'],
            'fileSize' => (int) $storedSize,
            'fileSizeFormatted' => $this->formatBytes((int) $storedSize),
            'message' => 'ფაილი წარმატებით აიტვირთა.',
        ];
    }

    private function detectMimeType(string $tmpFile): ?string
    {
        if (!is_file($tmpFile)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mimeType = finfo_file($finfo, $tmpFile) ?: null;
        finfo_close($finfo);

        return is_string($mimeType) ? $mimeType : null;
    }

    private function mapPhpUploadError(int $errorCode): array
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $this->error('SIZE_ERROR', 'ფაილის ზომა ლიმიტს აღემატება.'),
            UPLOAD_ERR_NO_FILE => $this->error('NO_FILE', 'ფაილი არ არის არჩეული.'),
            default => $this->error('UPLOAD_ERROR', 'ატვირთვისას დაფიქსირდა შეცდომა.'),
        };
    }

    private function error(string $code, string $message): array
    {
        return [
            'success' => false,
            'errorCode' => $code,
            'message' => $message,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;

        foreach ($units as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return number_format($size, 2) . ' ' . $unit;
            }

            $size /= 1024;
        }

        return $bytes . ' B';
    }
}

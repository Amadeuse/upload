<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

if (!class_exists('FileUploader', false)) {
    class_alias(App\Services\FileUploader::class, 'FileUploader');
}

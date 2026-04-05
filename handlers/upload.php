<?php
declare(strict_types=1);

use App\Services\FileUploader;

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../autoload.php';

$uploader = FileUploader::fromConfig(__DIR__ . '/../config/upload-config.php');
$result = $uploader->handleRequest('file');

http_response_code($uploader->responseStatusCode($result));
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/upload-config.php';
$maxSizeMb = (int) ($config['max_file_size'] / 1024 / 1024);
$allowedExtensions = implode(', ', $config['allowed_extensions']);
?><!doctype html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/upload.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 text-center">
                        <h1 class="h4 mb-2">ფაილის ატვირთვა</h1>
                        <p class="text-muted mb-4">გამოძახება ხდება ასე: <code>&lt;div class="upload-section"&gt;&lt;/div&gt;</code></p>

                        <div class="upload-section" data-upload-url="handlers/upload.php"></div>

                        <div id="upload-log" class="alert alert-secondary mt-4 mb-0">
                            აქ გამოჩნდება ატვირთული ფაილის სახელი, ზომა ან შეცდომა.
                        </div>

                        <div class="small text-muted mt-4">
                            მაქს. ზომა: <strong><?= $maxSizeMb ?>MB</strong><br>
                            დაშვებული ტიპები: <strong><?= htmlspecialchars($allowedExtensions, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/upload.js"></script>
    <script>
        const uploadSection = document.querySelector('.upload-section');
        const uploadLog = document.getElementById('upload-log');

        uploadSection.addEventListener('upload:success', (event) => {
            const { fileName, fileSizeFormatted } = event.detail;
            uploadLog.className = 'alert alert-success mt-4 mb-0';
            uploadLog.textContent = `ატვირთულია: ${fileName} (${fileSizeFormatted})`;
        });

        uploadSection.addEventListener('upload:error', (event) => {
            const { errorCode, message } = event.detail;
            uploadLog.className = 'alert alert-danger mt-4 mb-0';
            uploadLog.textContent = `${errorCode}: ${message}`;
        });
    </script>
</body>
</html>

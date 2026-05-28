<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;

class UploadService
{
    private const MAX_FILE_SIZE = 10485760;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => ['extension' => 'jpg', 'type' => 'photo'],
        'image/png' => ['extension' => 'png', 'type' => 'photo'],
        'image/webp' => ['extension' => 'webp', 'type' => 'photo'],
        'application/pdf' => ['extension' => 'pdf', 'type' => 'intel_report'],
    ];

    public function validate(array $files): array
    {
        $errors = [];
        if (empty($files['name']) || !is_array($files['name'])) {
            return $errors;
        }

        foreach ($files['name'] as $index => $name) {
            $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = sprintf('Upload failed for %s.', (string)$name);
                continue;
            }

            $size = (int)($files['size'][$index] ?? 0);
            if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
                $errors[] = sprintf('Attachment %s exceeds the 10 MB limit.', (string)$name);
                continue;
            }

            $tmpName = (string)($files['tmp_name'][$index] ?? '');
            $mimeType = $this->detectMimeType($tmpName);
            if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
                $errors[] = sprintf('Attachment %s has an unsupported file type.', (string)$name);
            }
        }

        return $errors;
    }

    public function storeMany(array $files, int $incidentId, int $uploadedBy): array
    {
        $attachmentModel = new Attachment();
        $results = [];
        if (empty($files['name']) || !is_array($files['name'])) {
            return $results;
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/private/uploads/' . date('Y/m');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        foreach ($files['name'] as $index => $name) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = (string)($files['tmp_name'][$index] ?? '');
            $mimeType = $this->detectMimeType($tmpName);
            $config = self::ALLOWED_MIME_TYPES[$mimeType] ?? null;
            if ($config === null) {
                continue;
            }

            $storedName = bin2hex(random_bytes(16)) . '.' . $config['extension'];
            $destination = $storageDir . '/' . $storedName;
            if (!move_uploaded_file($tmpName, $destination)) {
                continue;
            }

            $relativePath = 'storage/private/uploads/' . date('Y/m') . '/' . $storedName;
            $attachmentId = $attachmentModel->create(
                $incidentId,
                basename((string)$name),
                $relativePath,
                $config['type'],
                $uploadedBy,
                $mimeType,
                (int)($files['size'][$index] ?? 0)
            );

            $results[] = [
                'id' => $attachmentId,
                'original_name' => basename((string)$name),
                'stored_path' => $relativePath,
                'mime_type' => $mimeType,
                'file_type' => $config['type'],
            ];
        }

        return $results;
    }

    private function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        try {
            return (string)finfo_file($finfo, $path);
        } finally {
            finfo_close($finfo);
        }
    }
}
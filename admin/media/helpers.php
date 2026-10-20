<?php

require_once __DIR__ . '/../../config/config.php';

function mediaUploadDirectory(): string
{
    $directory = __DIR__ . '/../../uploads/media';
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    return $directory;
}

function mediaPublicPath(string $filename): string
{
    return BASE_URL . '/uploads/media/' . $filename;
}

function mediaResolveUrl(?string $dbPath): string
{
    if (!$dbPath) return '';
    if (preg_match('#^https?://#i', $dbPath)) return $dbPath;
    if (strpos($dbPath, '/uploads/media/') !== false) {
        return BASE_URL . '/uploads/media/' . rawurlencode(basename($dbPath));
    }
    return $dbPath;
}

function mediaDeleteFile(?string $path): void
{
    if (!$path || strpos($path, '/uploads/media/') === false) return;
    $filename = basename($path);
    if ($filename === '' || $filename === '.' || $filename === '..') return;
    
    $fullPath = mediaUploadDirectory() . '/' . $filename;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

/**
 * Handles single file processing (used inside a loop for multi-uploads)
 */
function mediaProcessSingleFile(array $file, string $expectedType): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed for ' . $file['name'] . ' (Code: ' . $file['error'] . ').');
    }

    $maxSize = $expectedType === 'video' ? 1024 * 1024 * 1024 : 15 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new RuntimeException("File {$file['name']} exceeds size limit.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'],
        'video' => ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'],
    ];

    if (!isset($allowed[$expectedType][$mime])) {
        throw new RuntimeException("File {$file['name']} has invalid MIME type: {$mime}. Expected {$expectedType}.");
    }

    $extension = $allowed[$expectedType][$mime];
    // Create safe filename
    $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = $cleanOriginalName . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

    $destination = mediaUploadDirectory() . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException("Could not save {$file['name']}. Check folder permissions.");
    }
    @chmod($destination, 0644);

    return [
        'file_path' => mediaPublicPath($filename),
        'file_name' => $file['name'],
        'mime_type' => $mime,
        'file_size' => $file['size']
    ];
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
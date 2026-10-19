<?php

require_once __DIR__ . '/../../config/config.php';

/**
 * Gallery upload & URL resolution helper functions.
 */

function galleryUploadDirectory(): string
{
    $directory = __DIR__ . '/../../uploads/gallery';

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    return $directory;
}

function galleryPublicPath(string $filename): string
{
    return BASE_URL . '/uploads/gallery/' . $filename;
}

/**
 * Resolves stored DB paths like '/pingaksh-admin/uploads/gallery/abc.jpg'
 * or '/uploads/gallery/abc.jpg' so they always work on both localhost and Hostinger root.
 */
function galleryResolveUrl(?string $dbPath): string
{
    if (!$dbPath) {
        return '';
    }

    // If it's already an external http/https URL, return as-is
    if (preg_match('#^https?://#i', $dbPath)) {
        return $dbPath;
    }

    // Extract the filename and build a clean URL matching the current BASE_URL
    if (strpos($dbPath, '/uploads/gallery/') !== false) {
        $filename = basename($dbPath);
        return BASE_URL . '/uploads/gallery/' . rawurlencode($filename);
    }

    return $dbPath;
}

/**
 * Returns the best preview image URL for a gallery item row.
 * Falls back to media_url when thumbnail_url is NULL!
 */
function galleryPreviewUrl(array $item): string
{
    if (!empty($item['thumbnail_url'])) {
        return galleryResolveUrl($item['thumbnail_url']);
    }

    if (($item['type'] ?? '') === 'image' && !empty($item['media_url'])) {
        return galleryResolveUrl($item['media_url']);
    }

    return '';
}

function galleryDeleteFile(?string $path): void
{
    if (!$path) {
        return;
    }

    // Accept both '/pingaksh-admin/uploads/gallery/' and '/uploads/gallery/'
    if (strpos($path, '/uploads/gallery/') === false) {
        return;
    }

    $filename = basename($path);

    if ($filename === '' || $filename === '.' || $filename === '..') {
        return;
    }

    $fullPath = galleryUploadDirectory() . '/' . $filename;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function galleryUploadFile(
    array $file,
    string $type,
    bool $required = true
): ?string {

    if (empty($file) || !isset($file['error'])) {
        if ($required) {
            throw new RuntimeException('Please select a file.');
        }
        return null;
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Please select a file.');
        }
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed (Error code: ' . $file['error'] . ').');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $maxSize = $type === 'video'
        ? 100 * 1024 * 1024
        : 15 * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        $limit = $type === 'video' ? '100 MB' : '15 MB';
        throw new RuntimeException("File is too large. Maximum allowed size is {$limit}.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image' => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ],
        'video' => [
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
        ],
    ];

    if (!isset($allowed[$type][$mime])) {
        throw new RuntimeException(
            'Invalid file type (' . htmlspecialchars((string)$mime) . '). Allowed formats: ' .
            implode(', ', array_values($allowed[$type]))
        );
    }

    $extension = $allowed[$type][$mime];

    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;

    $directory = galleryUploadDirectory();
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded file. Check folder permissions on uploads/gallery.');
    }

    @chmod($destination, 0644);

    return galleryPublicPath($filename);
}
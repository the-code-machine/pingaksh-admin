<?php

/**
 * Gallery upload helper functions.
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
    return '/pingaksh-admin/uploads/gallery/' . $filename;
}

function galleryDeleteFile(?string $path): void
{
    if (!$path) {
        return;
    }

    /*
     * Only delete files belonging to our gallery upload directory.
     */
    if (strpos($path, '/pingaksh-admin/uploads/gallery/') !== 0) {
        return;
    }

    $filename = basename($path);

    if ($filename === '') {
        return;
    }

    $fullPath = galleryUploadDirectory() . '/' . $filename;

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function galleryUploadFile(
    array $file,
    string $type,
    bool $required = true
): ?string {

    if (!isset($file) || !is_array($file)) {
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
        throw new RuntimeException('File upload failed.');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $maxSize = $type === 'video'
        ? 100 * 1024 * 1024
        : 15 * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        $limit = $type === 'video' ? '100 MB' : '15 MB';

        throw new RuntimeException(
            "File is too large. Maximum allowed size is {$limit}."
        );
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
            'Invalid file type. Allowed formats: ' .
            implode(', ', array_values($allowed[$type]))
        );
    }

    $extension = $allowed[$type][$mime];

    $filename =
        bin2hex(random_bytes(16))
        . '_'
        . time()
        . '.'
        . $extension;

    $directory = galleryUploadDirectory();

    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException(
            'Could not save uploaded file.'
        );
    }

    chmod($destination, 0644);

    return galleryPublicPath($filename);
}
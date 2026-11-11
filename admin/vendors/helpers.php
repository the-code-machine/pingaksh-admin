<?php

require_once __DIR__ . '/../../config/config.php';

function vendorUploadDirectory(): string
{
    $dir = __DIR__ . '/../../uploads/vendors';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function vendorPublicPath(string $filename): string
{
    return BASE_URL . '/uploads/vendors/' . $filename;
}

function vendorDeleteEnclosure(?string $path): void
{
    if (!$path || strpos($path, '/uploads/vendors/') === false) return;
    $filename = basename($path);
    if ($filename === '' || $filename === '.' || $filename === '..') return;
    $full = vendorUploadDirectory() . '/' . $filename;
    if (is_file($full)) {
        @unlink($full);
    }
}

function vendorUploadEnclosure(array $file, string $prefix = 'kyc'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error for ' . ($file['name'] ?? 'document'));
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Enclosure exceeds 15MB maximum size.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException("Invalid file format ({$mime}). Allowed: PDF, JPG, PNG, WebP.");
    }

    $ext = $allowed[$mime];
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = $prefix . '_' . $cleanName . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = vendorUploadDirectory() . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to save uploaded vendor document.');
    }
    @chmod($dest, 0644);

    return vendorPublicPath($filename);
}
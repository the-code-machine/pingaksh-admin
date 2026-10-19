<?php

require_once __DIR__ . '/../../config/config.php';

/**
 * JSON Helpers for Tags
 */
function announcementsValidateTags(?string $jsonString) {
    if (empty(trim($jsonString))) return json_encode([]);
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Invalid JSON format provided for Tags.");
    }
    if (!is_array($decoded)) {
        throw new RuntimeException("Tags must be a JSON array of strings.");
    }
    return json_encode($decoded);
}

function announcementsPrettyJson(?string $jsonString): string {
    if (empty($jsonString)) return "[\n  \n]";
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) return $jsonString;
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * Image Upload & Path Helpers
 */
function announcementsUploadDirectory(): string {
    $directory = __DIR__ . '/../../uploads/announcements';
    if (!is_dir($directory)) mkdir($directory, 0755, true);
    return $directory;
}

function announcementsPublicPath(string $filename): string {
    return BASE_URL . '/uploads/announcements/' . $filename;
}

function announcementsResolveUrl(?string $dbPath): string {
    if (!$dbPath) return '';
    if (preg_match('#^https?://#i', $dbPath)) return $dbPath;
    if (strpos($dbPath, '/uploads/announcements/') !== false) {
        return BASE_URL . '/uploads/announcements/' . rawurlencode(basename($dbPath));
    }
    return $dbPath;
}

function announcementsDeleteFile(?string $path): void {
    if (!$path || strpos($path, '/uploads/announcements/') === false) return;
    $filename = basename($path);
    if ($filename === '' || $filename === '.' || $filename === '..') return;
    $fullPath = announcementsUploadDirectory() . '/' . $filename;
    if (is_file($fullPath)) @unlink($fullPath);
}

function announcementsUploadFile(array $file, bool $required = true): ?string {
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) throw new RuntimeException('Please select an image.');
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed (Error code: ' . $file['error'] . ').');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException("Image is too large. Maximum 5 MB allowed.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Invalid file type. Only JPG, PNG, and WebP are allowed.');
    }

    $filename = 'press_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $allowed[$mime];
    $destination = announcementsUploadDirectory() . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save image. Check folder permissions.');
    }
    @chmod($destination, 0644);

    return announcementsPublicPath($filename);
}
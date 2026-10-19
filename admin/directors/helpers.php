<?php

require_once __DIR__ . '/../../config/config.php';

/**
 * JSON Helpers
 */
function directorsValidateJson(?string $jsonString) {
    if (empty(trim($jsonString))) return null;
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Invalid JSON format provided.");
    }
    return json_encode($decoded);
}

function directorsPrettyJson(?string $jsonString): string {
    if (empty($jsonString)) return '';
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) return $jsonString;
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * Portrait Upload & Path Helpers
 */
function directorsUploadDirectory(): string {
    $directory = __DIR__ . '/../../uploads/directors';
    if (!is_dir($directory)) mkdir($directory, 0755, true);
    return $directory;
}

function directorsPublicPath(string $filename): string {
    return BASE_URL . '/uploads/directors/' . $filename;
}

function directorsResolveUrl(?string $dbPath): string {
    if (!$dbPath) return '';
    if (preg_match('#^https?://#i', $dbPath)) return $dbPath;
    if (strpos($dbPath, '/uploads/directors/') !== false) {
        return BASE_URL . '/uploads/directors/' . rawurlencode(basename($dbPath));
    }
    return $dbPath;
}

function directorsDeleteFile(?string $path): void {
    if (!$path || strpos($path, '/uploads/directors/') === false) return;
    $filename = basename($path);
    if ($filename === '' || $filename === '.' || $filename === '..') return;
    $fullPath = directorsUploadDirectory() . '/' . $filename;
    if (is_file($fullPath)) @unlink($fullPath);
}

function directorsUploadFile(array $file, bool $required = true): ?string {
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) throw new RuntimeException('Please select a portrait image.');
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed (Error code: ' . $file['error'] . ').');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException("Portrait image is too large. Maximum 5 MB allowed.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Invalid file type. Only JPG, PNG, and WebP are allowed.');
    }

    $filename = 'director_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $allowed[$mime];
    $destination = directorsUploadDirectory() . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save portrait image. Check folder permissions.');
    }
    @chmod($destination, 0644);

    return directorsPublicPath($filename);
}
<?php

function formatFileSizeLabel(int $bytes, string $suffix = ''): string
{
    if ($bytes <= 0) {
        return $suffix !== '' ? "PDF · 1.0 MB" : "1.0 MB";
    }
    $mb = round($bytes / (1024 * 1024), 1);
    if ($mb < 0.1) {
        $kb = round($bytes / 1024, 0);
        $label = $kb . ' KB';
    } else {
        $label = number_format($mb, 1) . ' MB';
    }
    return $suffix !== '' ? "{$suffix} · {$label}" : $label;
}

function handleFinancialUpload(string $fieldName, array $allowedExt, string $fallbackHref = ''): array
{
    if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['href' => $fallbackHref, 'file_size' => null];
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload failed for {$fieldName} (code " . $_FILES[$fieldName]['error'] . ").");
    }

    $origName = $_FILES[$fieldName]['name'];
    $tmpName = $_FILES[$fieldName]['tmp_name'];
    $sizeBytes = (int)$_FILES[$fieldName]['size'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException("Invalid file type (.{$ext}) for {$fieldName}. Allowed: " . implode(', ', $allowedExt));
    }

    $uploadDir = __DIR__ . '/../../uploads/investors';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($origName, PATHINFO_FILENAME));
    $safeBase = trim($safeBase, '-');
    if ($safeBase === '') {
        $safeBase = 'financial-doc';
    }

    $uniqueName = $safeBase . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $uniqueName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException("Failed to move uploaded file to uploads/investors.");
    }

    $publicHref = BASE_URL . '/uploads/investors/' . $uniqueName;
    $sizeLabel = ($ext === 'pdf') ? formatFileSizeLabel($sizeBytes, 'PDF') : formatFileSizeLabel($sizeBytes);

    return [
        'href' => $publicHref,
        'file_size' => $sizeLabel,
    ];
}
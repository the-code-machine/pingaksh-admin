<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT file_path FROM media WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $delete = $pdo->prepare("DELETE FROM media WHERE id = ?");
    $delete->execute([$id]);
    $pdo->commit();

    if (!empty($item['file_path'])) {
        mediaDeleteFile($item['file_path']);
    }

    header('Location: index.php?deleted=1');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    die('Could not delete media file.');
}
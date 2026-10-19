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

$stmt = $pdo->prepare("SELECT image_url FROM announcements WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $delete = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $delete->execute([$id]);
    $pdo->commit();

    // Delete image file only after successful DB deletion
    if (!empty($item['image_url'])) {
        announcementsDeleteFile($item['image_url']);
    }

    header('Location: index.php?deleted=1');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    die('Could not delete announcement.');
}
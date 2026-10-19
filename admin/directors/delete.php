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

$stmt = $pdo->prepare("SELECT portrait_image FROM directors WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $delete = $pdo->prepare("DELETE FROM directors WHERE id = ?");
    $delete->execute([$id]);
    $pdo->commit();

    // Delete portrait file only after successful DB deletion
    if (!empty($item['portrait_image'])) {
        directorsDeleteFile($item['portrait_image']);
    }

    header('Location: index.php?deleted=1');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    die('Could not delete director profile.');
}
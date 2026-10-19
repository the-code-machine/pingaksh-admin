<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: index.php?deleted=1');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    die('Could not delete project. Database error.');
}
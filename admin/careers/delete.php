<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid application ID.'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT resume_path FROM career_applications WHERE id = ?");
    $stmt->execute([$id]);
    $resumePath = $stmt->fetchColumn();

    if ($resumePath) {
        $fileName = basename(parse_url($resumePath, PHP_URL_PATH));
        $localFile = __DIR__ . '/../../uploads/resumes/' . $fileName;
        if (is_file($localFile)) {
            @unlink($localFile);
        }
    }

    $pdo->prepare("DELETE FROM career_applications WHERE id = ?")->execute([$id]);
    header('Location: index.php?success=' . urlencode('Application deleted.'));
    exit;
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Delete failed: ' . $e->getMessage()));
    exit;
}
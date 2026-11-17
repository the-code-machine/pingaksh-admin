<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['annual', 'quarterly', 'subsidiary'], true) || $id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid delete request.'));
    exit;
}

try {
    if ($type === 'annual') {
        $stmt = $pdo->prepare("DELETE FROM financial_annual_reports WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?success=' . urlencode('Annual report deleted.'));
        exit;
    }

    if ($type === 'quarterly') {
        $stmt = $pdo->prepare("DELETE FROM financial_quarterly_packs WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?success=' . urlencode('Quarterly pack deleted.'));
        exit;
    }

    if ($type === 'subsidiary') {
        $stmt = $pdo->prepare("DELETE FROM financial_subsidiaries WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: index.php?success=' . urlencode('Subsidiary financial record deleted.'));
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode('Delete failed: ' . $e->getMessage()));
    exit;
}
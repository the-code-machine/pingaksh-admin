<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']); // Only super admin can delete; editors can only update/deactivate.
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM carbon_metrics WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: index.php?success=Deleted');
exit;
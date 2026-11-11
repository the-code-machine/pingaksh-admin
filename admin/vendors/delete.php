<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $pdo->prepare("SELECT cancelled_cheque_copy, pan_scan_copy, gstin_certificate, ssi_msmed_em_copy FROM vendor_registrations WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $del = $pdo->prepare("DELETE FROM vendor_registrations WHERE id = ?");
        $del->execute([$id]);

        foreach (['cancelled_cheque_copy', 'pan_scan_copy', 'gstin_certificate', 'ssi_msmed_em_copy'] as $col) {
            if (!empty($row[$col])) {
                vendorDeleteEnclosure($row[$col]);
            }
        }
    }
}

header('Location: index.php?deleted=1');
exit;
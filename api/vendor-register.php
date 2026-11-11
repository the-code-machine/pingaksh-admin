<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/vendors/helpers.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

$p = $_POST;

$requiredFields = [
    'nameOfParty', 'contactPerson', 'contactMobile',
    'principalAddress', 'principalCity', 'principalPinCode', 'principalState', 'principalEmailId',
    'gstContactName', 'gstContactMobileNo', 'gstContactEmailId',
    'director1', 'gstStatus', 'materialServicesDealing', 'hsnSacCode', 'panNumber',
    'bankName', 'accountType', 'accountNumber', 'ifscCode', 'mfgUnitStatus', 'msmedSection2n'
];

foreach ($requiredFields as $field) {
    if (trim($p[$field] ?? '') === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Missing mandatory field: {$field}"]);
        exit;
    }
}

try {
    $refId = 'PRPL-KYC-' . date('Y') . '-' . random_int(100000, 999999);

    $chequePath = !empty($_FILES['cancelledChequeCopy'])
        ? vendorUploadEnclosure($_FILES['cancelledChequeCopy'], 'cheque')
        : null;

    $panPath = !empty($_FILES['panScanCopy'])
        ? vendorUploadEnclosure($_FILES['panScanCopy'], 'pan')
        : null;

    $gstPath = !empty($_FILES['gstinCertificate'])
        ? vendorUploadEnclosure($_FILES['gstinCertificate'], 'gst')
        : null;

    $msmedPath = !empty($_FILES['ssiMsmedEmCopy'])
        ? vendorUploadEnclosure($_FILES['ssiMsmedEmCopy'], 'msmed')
        : null;

    if (!$chequePath || !$panPath) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Cancelled Cheque Copy and PAN Scan Copy are mandatory enclosures.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO vendor_registrations (
            reference_id, name_of_party, contact_person, contact_mobile,
            principal_address, principal_city, principal_pin_code, principal_state, principal_tel_no, principal_fax_no, principal_email_id,
            gst_contact_name, gst_contact_designation, gst_contact_address, gst_contact_city, gst_contact_pin_code, gst_contact_state, gst_contact_tel_no, gst_contact_mobile_no, gst_contact_email_id,
            director_1, director_2, director_3, director_4,
            gst_status, gst_reg_number, material_services_dealing, hsn_sac_code, pan_number, ssi_msmed_number, iec_number,
            bank_name, account_type, account_number, ifsc_code,
            mfg_unit_status, msmed_section_2n,
            cancelled_cheque_copy, pan_scan_copy, gstin_certificate, ssi_msmed_em_copy,
            agree_terms, status
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            1, 'pending'
        )
    ");

    $stmt->execute([
        $refId,
        trim($p['nameOfParty']),
        trim($p['contactPerson']),
        trim($p['contactMobile']),
        trim($p['principalAddress']),
        trim($p['principalCity']),
        trim($p['principalPinCode']),
        trim($p['principalState']),
        trim($p['principalTelNo'] ?? ''),
        trim($p['principalFaxNo'] ?? ''),
        trim($p['principalEmailId']),
        trim($p['gstContactName']),
        trim($p['gstContactDesignation'] ?? ''),
        trim($p['gstContactAddress'] ?? ''),
        trim($p['gstContactCity'] ?? ''),
        trim($p['gstContactPinCode'] ?? ''),
        trim($p['gstContactState'] ?? ''),
        trim($p['gstContactTelNo'] ?? ''),
        trim($p['gstContactMobileNo']),
        trim($p['gstContactEmailId']),
        trim($p['director1']),
        trim($p['director2'] ?? ''),
        trim($p['director3'] ?? ''),
        trim($p['director4'] ?? ''),
        $p['gstStatus'],
        trim($p['gstRegNumber'] ?? ''),
        trim($p['materialServicesDealing']),
        trim($p['hsnSacCode']),
        strtoupper(trim($p['panNumber'])),
        trim($p['ssiMsmedNumber'] ?? ''),
        trim($p['iecNumber'] ?? ''),
        trim($p['bankName']),
        trim($p['accountType']),
        trim($p['accountNumber']),
        strtoupper(trim($p['ifscCode'])),
        trim($p['mfgUnitStatus']),
        $p['msmedSection2n'] === 'Yes' ? 'Yes' : 'No',
        $chequePath,
        $panPath,
        $gstPath,
        $msmedPath
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Vendor KYC registration submitted successfully.',
        'reference_id' => $refId,
        'id' => (int)$pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
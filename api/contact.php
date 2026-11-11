<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$fullName     = trim($data['fullName'] ?? $data['full_name'] ?? '');
$organization = trim($data['organization'] ?? '');
$email        = trim($data['email'] ?? '');
$phone        = trim($data['phone'] ?? '');
$subject      = trim($data['subject'] ?? '');
$message      = trim($data['message'] ?? '');

if ($fullName === '' || $organization === '' || $email === '' || $phone === '' || $subject === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'All mandatory inquiry fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid official email address.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO contacts (full_name, organization, email, phone, subject, message, status)
        VALUES (?, ?, ?, ?, ?, ?, 'new')
    ");
    $stmt->execute([$fullName, $organization, $email, $phone, $subject, $message]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Inquiry logged successfully.',
        'inquiry_id' => (int)$pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while saving inquiry.']);
}
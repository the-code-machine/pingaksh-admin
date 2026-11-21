<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $preferredLocation = trim($_POST['preferred_location'] ?? '');
    $experienceLevel = trim($_POST['experience_level'] ?? '');
    $portfolioUrl = trim($_POST['portfolio_url'] ?? '');
    $confirmed = $_POST['confirmed'] ?? '';

    if ($fullName === '' || $email === '' || $contactNumber === '' || $position === '' || $preferredLocation === '' || $experienceLevel === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if ($confirmed !== 'true' && $confirmed !== '1' && $confirmed !== 'on') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please confirm that the information provided is accurate.']);
        exit;
    }

    $allowedExp = ['Fresher', '1–3 Years', '3–5 Years', '5+ Years'];
    if (!in_array($experienceLevel, $allowedExp, true)) {
        // Normalize hyphen vs en-dash if submitted from client
        $experienceLevel = str_replace('-', '–', $experienceLevel);
        if (!in_array($experienceLevel, $allowedExp, true)) {
            $experienceLevel = 'Fresher';
        }
    }

    // Validate Resume Upload
    if (empty($_FILES['resume']) || ($_FILES['resume']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please upload your Resume / CV (PDF or DOC).']);
        exit;
    }

    $origName = $_FILES['resume']['name'];
    $tmpName = $_FILES['resume']['tmp_name'];
    $fileSize = (int)$_FILES['resume']['size'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    $allowedExt = ['pdf', 'doc', 'docx'];
    if (!in_array($ext, $allowedExt, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only PDF, DOC, or DOCX files are allowed.']);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Resume file size must be under 5 MB.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/resumes';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($origName, PATHINFO_FILENAME));
    $safeBase = trim($safeBase, '-') ?: 'resume';
    $uniqueFileName = $safeBase . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $uniqueFileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Failed to save uploaded resume.');
    }

    $publicResumePath = BASE_URL . '/uploads/resumes/' . $uniqueFileName;

    $stmt = $pdo->prepare("
        INSERT INTO career_applications (
            full_name, email, contact_number, position, preferred_location,
            experience_level, resume_path, resume_name, portfolio_url, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'New')
    ");
    $stmt->execute([
        $fullName,
        $email,
        $contactNumber,
        $position,
        $preferredLocation,
        $experienceLevel,
        $publicResumePath,
        $origName,
        $portfolioUrl !== '' ? $portfolioUrl : null
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully. Our HR team will get in touch with shortlisted candidates.',
        'application_id' => (int)$pdo->lastInsertId(),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while submitting application.']);
}
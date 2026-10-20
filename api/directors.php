<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

try {
    $sql = "
        SELECT
            id,
            slug,
            name,
            designation,
            board_category,
            is_founder,
            din_number,
            appointed_since,
            qualifications,
            alma_mater,
            portrait_image,
            portrait_alt,
            short_summary,
            executive_quote,
            biography_paragraphs,
            key_responsibilities,
            committees,
            directorships_and_affiliations,
            created_at,
            updated_at
        FROM directors
        ORDER BY
            CASE board_category
                WHEN 'Promoter & Executive' THEN 1
                WHEN 'Executive Director' THEN 2
                WHEN 'Independent Director' THEN 3
                ELSE 4
            END,
            CAST(appointed_since AS UNSIGNED) ASC,
            id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $directors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert JSON columns back into proper JSON objects/arrays
    $jsonFields = [
        'qualifications',
        'biography_paragraphs',
        'key_responsibilities',
        'committees',
        'directorships_and_affiliations'
    ];

    foreach ($directors as &$director) {
        foreach ($jsonFields as $field) {
            if (!empty($director[$field])) {
                $decoded = json_decode($director[$field], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $director[$field] = $decoded;
                }
            }
        }

        $director['id'] = (int) $director['id'];
        $director['is_founder'] = (bool) $director['is_founder'];
    }

    unset($director);

    echo json_encode([
        'success' => true,
        'count' => count($directors),
        'data' => $directors
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch directors'
    ]);
}
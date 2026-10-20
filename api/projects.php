<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

try {

    $slug = isset($_GET['slug'])
        ? trim($_GET['slug'])
        : '';

    $limit = isset($_GET['limit'])
        ? (int) $_GET['limit']
        : 0;

    $sql = "
        SELECT
            id,
            slug,
            name,
            short_name,
            legal_entity,

            assets_heading,
            assets_description,

            seo_data,
            hero_data,
            overview_data,
            plant_units,
            telemetry_data,
            media_gallery_data,
            timeline_data,

            display_order,
            is_published,

            created_at,
            updated_at

        FROM projects

        WHERE is_published = 1
    ";

    $params = [];

    if ($slug !== '') {
        $sql .= " AND slug = :slug";
        $params['slug'] = $slug;
    }

    $sql .= "
        ORDER BY
            display_order ASC,
            id ASC
    ";

    if ($limit > 0 && $slug === '') {
        $limit = min($limit, 100);
        $sql .= " LIMIT :limit";
    }

    $stmt = $pdo->prepare($sql);

    if ($slug !== '') {
        $stmt->bindValue(
            ':slug',
            $slug,
            PDO::PARAM_STR
        );
    }

    if ($limit > 0 && $slug === '') {
        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );
    }

    $stmt->execute();

    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $jsonFields = [
        'seo_data',
        'hero_data',
        'overview_data',
        'plant_units',
        'telemetry_data',
        'media_gallery_data',
        'timeline_data'
    ];

    foreach ($projects as &$project) {

        $project['id'] =
            (int) $project['id'];

        $project['display_order'] =
            (int) $project['display_order'];

        $project['is_published'] =
            (bool) $project['is_published'];

        foreach ($jsonFields as $field) {

            if (
                $project[$field] !== null &&
                $project[$field] !== ''
            ) {

                $decoded = json_decode(
                    $project[$field],
                    true
                );

                if (json_last_error() === JSON_ERROR_NONE) {
                    $project[$field] = $decoded;
                }
            } else {
                $project[$field] = null;
            }
        }
    }

    /*
     * If a single slug was requested,
     * return the project directly in data.
     */
    if ($slug !== '') {

        if (count($projects) === 0) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Project not found',
                'data' => null
            ]);

            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $projects[0]
        ], JSON_UNESCAPED_SLASHES);

        exit;
    }

    echo json_encode([
        'success' => true,
        'count' => count($projects),
        'data' => $projects
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {

    error_log(
        'Projects API Error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch projects'
    ]);
}
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

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;
    $categoryId = isset($_GET['category_id'])
        ? (int) $_GET['category_id']
        : 0;

    $sql = "
        SELECT
            gi.id,
            gi.type,
            gi.category_id,

            gc.name AS category_name,
            gc.slug AS category_slug,

            gi.title,
            gi.caption,
            gi.location,
            gi.date_label,
            gi.iso_date,
            gi.asset_code,

            gi.thumbnail_url,
            gi.media_url,
            gi.video_duration,

            gi.alt_text,
            gi.technical_specs,
            gi.featured_span,

            gi.is_published,
            gi.created_at,
            gi.updated_at

        FROM gallery_items gi

        INNER JOIN gallery_categories gc
            ON gc.id = gi.category_id

        WHERE gi.is_published = 1
    ";

    $params = [];

    if ($categoryId > 0) {
        $sql .= " AND gi.category_id = :category_id";
        $params['category_id'] = $categoryId;
    }

    $sql .= "
        ORDER BY
            COALESCE(gi.iso_date, DATE(gi.created_at)) DESC,
            gi.id DESC
    ";

    if ($limit > 0) {
        $limit = min($limit, 100);
        $sql .= " LIMIT :limit";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            ':' . $key,
            $value,
            PDO::PARAM_INT
        );
    }

    if ($limit > 0) {
        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );
    }

    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {

        $item['id'] = (int) $item['id'];
        $item['category_id'] = (int) $item['category_id'];
        $item['is_published'] = (bool) $item['is_published'];

        // technical_specs may contain JSON
        if (!empty($item['technical_specs'])) {
            $decoded = json_decode(
                $item['technical_specs'],
                true
            );

            if (json_last_error() === JSON_ERROR_NONE) {
                $item['technical_specs'] = $decoded;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($items),
        'data' => $items
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {

    error_log(
        'Gallery API Error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch gallery data'
    ]);
}
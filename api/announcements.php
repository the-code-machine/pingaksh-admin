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

    $limit = isset($_GET['limit'])
        ? (int) $_GET['limit']
        : 0;

    $category = isset($_GET['category'])
        ? trim($_GET['category'])
        : '';

    $featured = isset($_GET['featured'])
        ? (int) $_GET['featured']
        : -1;

    $sql = "
        SELECT
            id,
            slug,
            title,
            excerpt,
            category,
            release_code,
            date_label,
            iso_date,
            release_year,
            location,
            image_url,
            image_alt,
            redirect_href,
            pdf_download_href,
            is_featured,
            tags,
            is_published,
            created_at,
            updated_at

        FROM announcements

        WHERE is_published = 1
    ";

    $params = [];

    if ($category !== '') {
        $sql .= " AND category = :category";
        $params['category'] = $category;
    }

    if ($featured === 0 || $featured === 1) {
        $sql .= " AND is_featured = :featured";
        $params['featured'] = $featured;
    }

    $sql .= "
        ORDER BY
            COALESCE(iso_date, DATE(created_at)) DESC,
            id DESC
    ";

    if ($limit > 0) {
        $limit = min($limit, 100);
        $sql .= " LIMIT :limit";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {

        if ($key === 'featured') {
            $stmt->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_INT
            );
        } else {
            $stmt->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }
    }

    if ($limit > 0) {
        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );
    }

    $stmt->execute();

    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($announcements as &$announcement) {

        $announcement['id'] =
            (int) $announcement['id'];

        $announcement['is_featured'] =
            (bool) $announcement['is_featured'];

        $announcement['is_published'] =
            (bool) $announcement['is_published'];

        /*
         * tags is stored as LONGTEXT.
         * If it contains JSON, return it as an array.
         */
        if (!empty($announcement['tags'])) {

            $decoded = json_decode(
                $announcement['tags'],
                true
            );

            if (json_last_error() === JSON_ERROR_NONE) {
                $announcement['tags'] = $decoded;
            }
        } else {
            $announcement['tags'] = [];
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($announcements),
        'data' => $announcements
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {

    error_log(
        'Announcements API Error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch announcements'
    ]);
}
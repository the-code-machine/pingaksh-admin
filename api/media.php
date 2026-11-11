<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/media/helpers.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$type   = trim($_GET['type'] ?? ''); // 'image' or 'video'
$search = trim($_GET['search'] ?? '');
$sort   = strtolower($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where = [];
$params = [];

if (in_array($type, ['image', 'video'], true)) {
    $where[] = "type = ?";
    $params[] = $type;
}

if ($search !== '') {
    $where[] = "(file_name LIKE ? OR file_path LIKE ?)";
    $val = "%{$search}%";
    array_push($params, $val, $val);
}

$whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";

try {
    // Total Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM media" . $whereSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Paginated Data
    $sql = "SELECT id, type, file_name, file_path, mime_type, file_size, created_at
            FROM media
            {$whereSql}
            ORDER BY created_at {$sort}
            LIMIT {$limit} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '';

    $items = array_map(function ($row) use ($scheme, $host) {
        $resolved = mediaResolveUrl($row['file_path']);
        if ($host && strpos($resolved, 'http') !== 0) {
            $resolved = $scheme . '://' . $host . '/' . ltrim($resolved, '/');
        }
        return [
            'id'         => (int)$row['id'],
            'type'       => $row['type'],
            'file_name'  => $row['file_name'],
            'db_path'    => $row['file_path'],
            'url'        => $resolved,
            'mime_type'  => $row['mime_type'],
            'file_size'  => (int)$row['file_size'],
            'created_at' => $row['created_at'],
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
            'has_more'    => ($page * $limit) < $total,
        ],
        'urls' => array_column($items, 'url'),
        'data' => $items,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch media items.']);
}
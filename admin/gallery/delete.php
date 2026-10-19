<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT
        id,
        media_url,
        thumbnail_url
    FROM gallery_items
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$item = $stmt->fetch();

if (!$item) {

    header('Location: index.php');

    exit;
}


try {

    $pdo->beginTransaction();


    $delete = $pdo->prepare("
        DELETE FROM gallery_items
        WHERE id = ?
    ");

    $delete->execute([$id]);


    $pdo->commit();


    /*
     * Delete physical files only after
     * successful DB deletion.
     */

    galleryDeleteFile(
        $item['media_url']
    );

    galleryDeleteFile(
        $item['thumbnail_url']
    );


    header(
        'Location: index.php?deleted=1'
    );

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    die(
        'Could not delete gallery item.'
    );
}
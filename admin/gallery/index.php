<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';

$stmt = $pdo->query("
    SELECT 
        g.*,
        c.name AS category_name
    FROM gallery_items g
    LEFT JOIN gallery_categories c
        ON g.category_id = c.id
    ORDER BY g.created_at DESC
");

$items = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gallery - Pingaksh Renewables</title>

<style>

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
}

.main {
    margin-left: 250px;
    padding: 30px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.header h1 {
    margin: 0;
}

.add-btn {
    background: #0b4f9c;
    color: white;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 7px;
}

.table-box {
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    background: #f8fafc;
}

.thumb {
    width: 80px;
    height: 55px;
    object-fit: cover;
    border-radius: 6px;
}

.actions a {
    text-decoration: none;
    margin-right: 10px;
}

.edit {
    color: #2563eb;
}

.delete {
    color: #dc2626;
}

</style>

</head>

<body>

<main class="main">

    <div class="header">

        <div>
            <h1>Gallery</h1>
            <p>Manage website gallery media.</p>
        </div>

        <a
            class="add-btn"
            href="create.php"
        >
            + Add Media
        </a>

    </div>


    <div class="table-box">

        <table>

            <thead>

                <tr>
                    <th>Media</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (!$items): ?>

                <tr>
                    <td colspan="6">
                        No gallery items found.
                    </td>
                </tr>

            <?php endif; ?>


            <?php foreach ($items as $item): ?>

                <tr>

                    <td>

                        <?php if ($item['type'] === 'image' && $item['thumbnail_url']): ?>

                            <img
                                class="thumb"
                                src="<?= htmlspecialchars($item['thumbnail_url']) ?>"
                            >

                        <?php else: ?>

                            🎬 Video

                        <?php endif; ?>

                    </td>

                    <td>
                        <?= htmlspecialchars($item['title']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($item['category_name']) ?>
                    </td>

                    <td>
                        <?= ucfirst($item['type']) ?>
                    </td>

                    <td>
                        <?= $item['is_published'] ? 'Published' : 'Draft' ?>
                    </td>

                    <td class="actions">

                        <a
                            class="edit"
                            href="edit.php?id=<?= $item['id'] ?>"
                        >
                            Edit
                        </a>

                        <a
                            class="delete"
                            href="delete.php?id=<?= $item['id'] ?>"
                            onclick="return confirm('Delete this media?')"
                        >
                            Delete
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>
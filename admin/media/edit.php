<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Edit Media Info';
$activeMenu = 'media';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM media WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die('Media not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileName = trim($_POST['file_name'] ?? '');

    if ($fileName === '') $errors[] = 'Internal File Name is required.';

    if (!$errors) {
        try {
            $updateStmt = $pdo->prepare("UPDATE media SET file_name = ? WHERE id = ?");
            $updateStmt->execute([$fileName, $id]);

            header('Location: index.php?updated=1');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    $item['file_name'] = $fileName;
}

$resolvedUrl = mediaResolveUrl($item['file_path']);
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Media
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Edit Media Metadata</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 font-medium">
            <?= implode('<br>', $errors) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="id" value="<?= $id ?>">

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start mb-6 pb-6 border-b border-slate-200">
                <div class="h-32 w-48 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 shrink-0">
                    <?php if ($item['type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($resolvedUrl) ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                        <video src="<?= htmlspecialchars($resolvedUrl) ?>" controls class="h-full w-full object-cover"></video>
                    <?php endif; ?>
                </div>
                <div class="space-y-2 text-sm text-slate-600">
                    <p><strong class="text-pingaksh-navy">DB Path:</strong> <br><span class="font-mono text-xs break-all"><?= htmlspecialchars($item['file_path']) ?></span></p>
                    <p><strong class="text-pingaksh-navy">MIME Type:</strong> <?= htmlspecialchars($item['mime_type']) ?></p>
                    <p><strong class="text-pingaksh-navy">Size:</strong> <?= formatBytes($item['file_size']) ?></p>
                    <p><strong class="text-pingaksh-navy">Uploaded:</strong> <?= $item['created_at'] ?></p>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display File Name (Internal use)</label>
                <input type="text" name="file_name" required value="<?= htmlspecialchars($item['file_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover">
                <i data-lucide="save" class="h-4 w-4"></i> Save Details
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
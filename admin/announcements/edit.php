<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Edit Announcement';
$activeMenu = 'announcements';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die('Announcement not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = $_POST['category'] ?? 'Corporate & Governance';
    $releaseCode = trim($_POST['release_code'] ?? '');
    $dateLabel = trim($_POST['date_label'] ?? '');
    $isoDate = trim($_POST['iso_date'] ?? '');
    $releaseYear = trim($_POST['release_year'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $imageAlt = trim($_POST['image_alt'] ?? '');
    $redirectHref = trim($_POST['redirect_href'] ?? '');
    $pdfDownloadHref = trim($_POST['pdf_download_href'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '') $errors[] = 'Headline is required.';
    if ($slug === '') $errors[] = 'Slug is required.';

    $validatedTags = json_encode([]);
    try {
        $validatedTags = announcementsValidateTags($_POST['tags'] ?? '');
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    $newImagePath = null;

    if (!$errors) {
        try {
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $newImagePath = announcementsUploadFile($_FILES['image_file'], false);
            }

            $imagePath = $newImagePath ?: $item['image_url'];

            if (isset($_POST['remove_image'])) {
                if ($item['image_url']) announcementsDeleteFile($item['image_url']);
                $imagePath = null;
                $newImagePath = null;
            }

            $updateStmt = $pdo->prepare("
                UPDATE announcements SET
                    slug = ?, title = ?, excerpt = ?, category = ?, release_code = ?,
                    date_label = ?, iso_date = ?, release_year = ?, location = ?,
                    image_url = ?, image_alt = ?, redirect_href = ?, pdf_download_href = ?,
                    tags = ?, is_featured = ?, is_published = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $slug, $title, $excerpt, $category, $releaseCode,
                $dateLabel, $isoDate ?: null, $releaseYear, $location,
                $imagePath, $imageAlt, $redirectHref, $pdfDownloadHref,
                $validatedTags, $isFeatured, $isPublished, $id
            ]);

            if ($newImagePath && $item['image_url'] && $item['image_url'] !== $newImagePath) {
                announcementsDeleteFile($item['image_url']);
            }

            header('Location: index.php?updated=1');
            exit;
        } catch (PDOException $e) {
            announcementsDeleteFile($newImagePath);
            if ($e->errorInfo[1] === 1062) $errors[] = 'Slug must be unique.';
            else $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    $item = array_merge($item, $_POST);
}

$previewUrl = announcementsResolveUrl($item['image_url'] ?? '');
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Announcements
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Edit Announcement</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <ul class="list-disc pl-5 text-sm text-red-700 font-medium">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="id" value="<?= $id ?>">

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Featured Image</h3>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="relative h-28 w-44 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shrink-0">
                    <?php if ($previewUrl): ?>
                        <img src="<?= htmlspecialchars($previewUrl) ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center text-slate-400"><i data-lucide="image" class="h-8 w-8"></i></div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 space-y-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Replace Image</label>
                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-pingaksh-sand file:px-4 file:py-2 file:font-semibold file:text-pingaksh-navy">
                    </div>
                    <?php if ($item['image_url']): ?>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded border-slate-300 text-red-600">
                        <span class="text-sm text-slate-700">Remove current image</span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Use exact same form grids as create.php from here down -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Core Details</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Headline / Title *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($item['title']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($item['slug']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Category</label>
                    <select name="category" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                        <option value="Hydro Power" <?= $item['category'] === 'Hydro Power' ? 'selected' : '' ?>>Hydro Power</option>
                        <option value="Bio-Energy & Fuels" <?= $item['category'] === 'Bio-Energy & Fuels' ? 'selected' : '' ?>>Bio-Energy & Fuels</option>
                        <option value="Corporate & Governance" <?= $item['category'] === 'Corporate & Governance' ? 'selected' : '' ?>>Corporate & Governance</option>
                        <option value="Sustainability & CSR" <?= $item['category'] === 'Sustainability & CSR' ? 'selected' : '' ?>>Sustainability & CSR</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Excerpt / Summary</label>
                    <textarea name="excerpt" rows="3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($item['excerpt']) ?></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Date, Location & Routing</h3>
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Release Code</label>
                    <input type="text" name="release_code" value="<?= htmlspecialchars($item['release_code'] ?? '') ?>" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($item['location'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Release Year</label>
                    <input type="text" name="release_year" value="<?= htmlspecialchars($item['release_year'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Date</label>
                    <input type="text" name="date_label" value="<?= htmlspecialchars($item['date_label'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">ISO Date</label>
                    <input type="date" name="iso_date" value="<?= htmlspecialchars($item['iso_date'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Tags (JSON Array)</label>
                    <textarea name="tags" rows="2" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 px-3 py-2 outline-none"><?= htmlspecialchars(announcementsPrettyJson($item['tags'] ?? '')) ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Redirect Href (Read Full Route)</label>
                    <input type="text" name="redirect_href" value="<?= htmlspecialchars($item['redirect_href'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm font-mono focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">PDF Download Href</label>
                    <input type="text" name="pdf_download_href" value="<?= htmlspecialchars($item['pdf_download_href'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm font-mono focus:border-pingaksh-terracotta outline-none">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Settings</h3>
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                    <span class="text-sm font-bold text-pingaksh-navy">Mark as Featured</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" <?= $item['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                    <span class="text-sm font-bold text-pingaksh-navy">Publish Live</span>
                </label>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover">
                <i data-lucide="save" class="h-4 w-4"></i> Save Updates
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
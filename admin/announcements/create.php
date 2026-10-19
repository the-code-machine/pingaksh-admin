<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'New Announcement';
$activeMenu = 'announcements';

$errors = [];
$defaultTags = "[\n  \"Pingaksh Group\",\n  \"Corporate\"\n]";

$form = [
    'slug' => '', 'title' => '', 'excerpt' => '', 'category' => 'Corporate & Governance',
    'release_code' => '', 'date_label' => date('d F Y'), 'iso_date' => date('Y-m-d'), 'release_year' => date('Y'),
    'location' => 'New Delhi', 'image_alt' => '', 'redirect_href' => '/media/press-releases/', 
    'pdf_download_href' => '', 'tags' => $defaultTags, 'is_featured' => '0', 'is_published' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, $_POST);
    $form['is_featured'] = isset($_POST['is_featured']) ? '1' : '0';
    $form['is_published'] = isset($_POST['is_published']) ? '1' : '0';

    if (empty(trim($form['title']))) $errors[] = 'Headline (Title) is required.';
    if (empty(trim($form['slug']))) $errors[] = 'URL Slug is required.';

    $validatedTags = json_encode([]);
    try {
        $validatedTags = announcementsValidateTags($form['tags']);
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    $imagePath = null;

    if (!$errors) {
        try {
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $imagePath = announcementsUploadFile($_FILES['image_file'], false);
            }

            $stmt = $pdo->prepare("
                INSERT INTO announcements (
                    slug, title, excerpt, category, release_code, date_label, iso_date, release_year, location,
                    image_url, image_alt, redirect_href, pdf_download_href, tags, is_featured, is_published
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim($form['slug']), trim($form['title']), trim($form['excerpt']), $form['category'],
                trim($form['release_code']), trim($form['date_label']), trim($form['iso_date']) ?: null, trim($form['release_year']),
                trim($form['location']), $imagePath, trim($form['image_alt']), trim($form['redirect_href']),
                trim($form['pdf_download_href']), $validatedTags, $form['is_featured'], $form['is_published']
            ]);

            header('Location: index.php?created=1');
            exit;
        } catch (PDOException $e) {
            announcementsDeleteFile($imagePath); // cleanup on fail
            if ($e->errorInfo[1] === 1062) $errors[] = 'An announcement with this slug already exists.';
            else $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Announcements
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Publish Announcement</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <ul class="list-disc pl-5 text-sm text-red-700 font-medium">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Core Details</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Headline / Title *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($form['title']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($form['slug']) ?>" placeholder="e.g. grid-connectivity-2026" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Category</label>
                    <select name="category" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                        <option value="Hydro Power" <?= $form['category'] === 'Hydro Power' ? 'selected' : '' ?>>Hydro Power</option>
                        <option value="Bio-Energy & Fuels" <?= $form['category'] === 'Bio-Energy & Fuels' ? 'selected' : '' ?>>Bio-Energy & Fuels</option>
                        <option value="Corporate & Governance" <?= $form['category'] === 'Corporate & Governance' ? 'selected' : '' ?>>Corporate & Governance</option>
                        <option value="Sustainability & CSR" <?= $form['category'] === 'Sustainability & CSR' ? 'selected' : '' ?>>Sustainability & CSR</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Excerpt / Summary</label>
                    <textarea name="excerpt" rows="3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['excerpt']) ?></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Date, Location & Routing</h3>
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Release Code</label>
                    <input type="text" name="release_code" value="<?= htmlspecialchars($form['release_code']) ?>" placeholder="e.g. PR/UBHP/2026/104" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($form['location']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Release Year</label>
                    <input type="text" name="release_year" value="<?= htmlspecialchars($form['release_year']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Date (Label)</label>
                    <input type="text" name="date_label" value="<?= htmlspecialchars($form['date_label']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">ISO Date (For sorting)</label>
                    <input type="date" name="iso_date" value="<?= htmlspecialchars($form['iso_date']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Tags (JSON Array)</label>
                    <textarea name="tags" rows="2" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 px-3 py-2 outline-none"><?= htmlspecialchars($form['tags']) ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Redirect Href (Read Full Route)</label>
                    <input type="text" name="redirect_href" value="<?= htmlspecialchars($form['redirect_href']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm font-mono focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">PDF Download Href (Optional)</label>
                    <input type="text" name="pdf_download_href" value="<?= htmlspecialchars($form['pdf_download_href']) ?>" placeholder="/investors/statutory-filings#pdf" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm font-mono focus:border-pingaksh-terracotta outline-none">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Media & Visibility</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Featured Image</label>
                    <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-pingaksh-sand file:px-4 file:py-2.5 file:font-semibold file:text-pingaksh-navy">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Image Alt Text</label>
                    <input type="text" name="image_alt" value="<?= htmlspecialchars($form['image_alt']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center gap-6 mt-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= $form['is_featured'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Mark as Featured</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live</span>
                    </label>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover">
                <i data-lucide="megaphone" class="h-4 w-4"></i> Publish Announcement
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
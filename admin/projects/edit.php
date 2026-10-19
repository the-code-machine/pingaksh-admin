<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Edit Business Vertical';
$activeMenu = 'projects';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die('Project not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $legalEntity = trim($_POST['legal_entity'] ?? '');
    $assetsHeading = trim($_POST['assets_heading'] ?? '');
    $assetsDesc = trim($_POST['assets_description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($name === '') $errors[] = 'Name is required.';
    if ($slug === '') $errors[] = 'Slug is required.';

    $validatedJson = [];
    $jsonFields = ['seo_data', 'hero_data', 'overview_data', 'plant_units', 'telemetry_data', 'media_gallery_data', 'timeline_data'];
    
    foreach ($jsonFields as $field) {
        try {
            $validatedJson[$field] = projectsValidateJson($_POST[$field] ?? '');
        } catch (Exception $e) {
            $errors[] = "Invalid JSON in field: " . str_replace('_', ' ', strtoupper($field));
        }
    }

    if (!$errors) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE projects SET
                    name = ?, short_name = ?, slug = ?, legal_entity = ?, assets_heading = ?, assets_description = ?,
                    seo_data = ?, hero_data = ?, overview_data = ?, plant_units = ?, telemetry_data = ?, media_gallery_data = ?, timeline_data = ?,
                    display_order = ?, is_published = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $name, $shortName, $slug, $legalEntity, $assetsHeading, $assetsDesc,
                $validatedJson['seo_data'], $validatedJson['hero_data'], $validatedJson['overview_data'],
                $validatedJson['plant_units'], $validatedJson['telemetry_data'], $validatedJson['media_gallery_data'], $validatedJson['timeline_data'],
                $displayOrder, $isPublished, $id
            ]);

            header('Location: index.php?updated=1');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) $errors[] = 'Slug must be unique.';
            else $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // Merge failed POST data back into $item for re-render
    $item = array_merge($item, $_POST);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Verticals
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Edit Business Vertical</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Please fix the following:</p>
            <ul class="list-disc pl-5 text-sm text-red-700">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?= $id ?>" method="POST" class="space-y-6">
        <input type="hidden" name="id" value="<?= $id ?>">

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Core Identity</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($item['name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Name / Tab Label *</label>
                    <input type="text" name="short_name" required value="<?= htmlspecialchars($item['short_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($item['slug']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Legal Entity / SPV</label>
                    <input type="text" name="legal_entity" value="<?= htmlspecialchars($item['legal_entity']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Assets Heading</label>
                    <input type="text" name="assets_heading" value="<?= htmlspecialchars($item['assets_heading'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Assets Description</label>
                    <textarea name="assets_description" rows="3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($item['assets_description'] ?? '') ?></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-bold text-pingaksh-navy">Structured Content (JSON)</h3>
            <p class="text-xs text-slate-500 mb-6">Raw JSON data mapping to Next.js components.</p>
            
            <div class="grid gap-6 md:grid-cols-2">
                <?php 
                $jsonFields = ['seo_data', 'hero_data', 'overview_data', 'plant_units', 'telemetry_data', 'media_gallery_data', 'timeline_data'];
                foreach ($jsonFields as $field): 
                    $prettyJson = projectsPrettyJson($item[$field] ?? '');
                ?>
                <div class="<?= in_array($field, ['overview_data', 'plant_units']) ? 'md:col-span-2' : '' ?>">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy uppercase tracking-wider"><?= str_replace('_', ' ', $field) ?></label>
                    <textarea name="<?= $field ?>" rows="<?= in_array($field, ['overview_data', 'plant_units']) ? 8 : 5 ?>" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($prettyJson) ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Order</label>
                    <input type="number" name="display_order" value="<?= (int)$item['display_order'] ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= $item['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live on Website</span>
                    </label>
                </div>
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
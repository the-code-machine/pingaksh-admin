<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Add Business Vertical';
$activeMenu = 'projects';

$errors = [];

// Pre-fill JSON schemas to make life easy
$defaultSeo = "{\n  \"title\": \"\",\n  \"description\": \"\",\n  \"keywords\": []\n}";
$defaultHero = "{\n  \"title\": \"\",\n  \"subtitle\": \"\",\n  \"heroImage\": \"\",\n  \"heroImageAlt\": \"\",\n  \"keyMetrics\": [\n    { \"label\": \"\", \"value\": \"\", \"unit\": \"\" }\n  ]\n}";
$defaultOverview = "{\n  \"headline\": \"\",\n  \"leadParagraph\": \"\",\n  \"bodyParagraph\": \"\",\n  \"highlights\": [\n    { \"title\": \"\", \"detail\": \"\" }\n  ]\n}";
$defaultPlantUnits = "[\n  {\n    \"id\": \"plant-1\",\n    \"name\": \"\",\n    \"location\": \"\",\n    \"status\": \"\",\n    \"capacity\": \"\",\n    \"summary\": \"\",\n    \"image\": \"\",\n    \"imageAlt\": \"\",\n    \"technicalSpecs\": [\n      { \"parameter\": \"\", \"specification\": \"\" }\n    ]\n  }\n]";

$form = [
    'name' => '', 'short_name' => '', 'slug' => '', 'legal_entity' => '',
    'assets_heading' => '', 'assets_description' => '', 'display_order' => '0',
    'seo_data' => $defaultSeo, 'hero_data' => $defaultHero, 'overview_data' => $defaultOverview,
    'plant_units' => $defaultPlantUnits, 'telemetry_data' => '', 'media_gallery_data' => '', 'timeline_data' => '',
    'is_published' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, $_POST);
    $form['is_published'] = isset($_POST['is_published']) ? '1' : '0';

    if (empty(trim($form['name']))) $errors[] = 'Vertical Name is required.';
    if (empty(trim($form['slug']))) $errors[] = 'Slug URL is required.';

    // Validate JSON fields safely
    $validatedJson = [];
    $jsonFields = ['seo_data', 'hero_data', 'overview_data', 'plant_units', 'telemetry_data', 'media_gallery_data', 'timeline_data'];
    
    foreach ($jsonFields as $field) {
        try {
            $validatedJson[$field] = projectsValidateJson($form[$field]);
        } catch (Exception $e) {
            $errors[] = "Invalid JSON in field: " . str_replace('_', ' ', strtoupper($field));
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (
                    name, short_name, slug, legal_entity, assets_heading, assets_description,
                    seo_data, hero_data, overview_data, plant_units, telemetry_data, media_gallery_data, timeline_data,
                    display_order, is_published
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim($form['name']), trim($form['short_name']), trim($form['slug']), trim($form['legal_entity']),
                trim($form['assets_heading']), trim($form['assets_description']),
                $validatedJson['seo_data'], $validatedJson['hero_data'], $validatedJson['overview_data'],
                $validatedJson['plant_units'], $validatedJson['telemetry_data'], $validatedJson['media_gallery_data'], $validatedJson['timeline_data'],
                (int)$form['display_order'], $form['is_published']
            ]);

            header('Location: index.php?created=1');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                $errors[] = 'A project with this slug already exists.';
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Verticals
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Add Business Vertical</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Please fix the following:</p>
            <ul class="list-disc pl-5 text-sm text-red-700">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Basic Info -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Core Identity</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($form['name']) ?>" placeholder="e.g. Hydro Power Generation" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Name / Tab Label *</label>
                    <input type="text" name="short_name" required value="<?= htmlspecialchars($form['short_name']) ?>" placeholder="e.g. Hydro Power" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($form['slug']) ?>" placeholder="e.g. hydro-power" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Legal Entity / SPV</label>
                    <input type="text" name="legal_entity" value="<?= htmlspecialchars($form['legal_entity']) ?>" placeholder="e.g. Uttar Bharat Hydro Power Pvt. Ltd." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Assets Heading</label>
                    <input type="text" name="assets_heading" value="<?= htmlspecialchars($form['assets_heading']) ?>" placeholder="e.g. Generation Infrastructure" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Assets Description</label>
                    <textarea name="assets_description" rows="3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['assets_description']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- Advanced JSON Data Blocks -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-bold text-pingaksh-navy">Structured Content (JSON)</h3>
            <p class="text-xs text-slate-500 mb-6">These fields map directly to your Next.js TypeScript interfaces. Ensure valid JSON format.</p>
            
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">SEO Data</label>
                    <textarea name="seo_data" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['seo_data']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Data</label>
                    <textarea name="hero_data" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['hero_data']) ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Overview Content</label>
                    <textarea name="overview_data" rows="6" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['overview_data']) ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Plant Units (Assets Array)</label>
                    <textarea name="plant_units" rows="8" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['plant_units']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Telemetry Data</label>
                    <textarea name="telemetry_data" rows="4" placeholder="{ ... }" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['telemetry_data']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Timeline Data</label>
                    <textarea name="timeline_data" rows="4" placeholder="{ ... }" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['timeline_data']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- Settings -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Order</label>
                    <input type="number" name="display_order" value="<?= htmlspecialchars($form['display_order']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live on Website</span>
                    </label>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover">
                <i data-lucide="save" class="h-4 w-4"></i> Save Project
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
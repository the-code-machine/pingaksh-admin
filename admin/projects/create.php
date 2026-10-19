<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Add Business Vertical';$activeMenu = 'projects';

$errors = [];

/**
 * Helper wrapper: Uses projectsUploadFile() from helpers.php if defined,
 * otherwise falls back to standard image upload into /uploads/projects/
 */
function handleProjectImageUpload(array $fileData): ?string {
    if (empty($fileData) || ($fileData['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (function_exists('projectsUploadFile')) {
        return projectsUploadFile($fileData, false);
    }
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error code: ' . $fileData['error']);
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileData['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        throw new Exception('Invalid image format. Allowed: JPG, PNG, WEBP.');
    }
    $uploadDir = __DIR__ . '/../../uploads/projects/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'proj_' . uniqid('', true) . '.' . $allowed[$mime];
    if (!move_uploaded_file($fileData['tmp_name'], $uploadDir . $filename)) {
        throw new Exception('Failed to save uploaded image.');
    }
    return '/uploads/projects/' . $filename;
}

// Pre-fill default JSON structures
$defaultSeo = json_encode(['title' => '', 'description' => '', 'keywords' => []]);
$defaultHero = json_encode([
    'title' => '', 'subtitle' => '', 'heroImage' => '', 'heroImageAlt' => '',
    'keyMetrics' => [['label' => '', 'value' => '', 'unit' => '']]
]);
$defaultOverview = json_encode([
    'headline' => '', 'leadParagraph' => '', 'bodyParagraph' => '',
    'highlights' => [['title' => '', 'detail' => '']]
]);
$defaultPlantUnits = json_encode([
    [
        'id' => 'plant-1', 'name' => '', 'location' => '', 'status' => 'Operational',
        'capacity' => '', 'summary' => '', 'image' => '', 'imageAlt' => '',
        'technicalSpecs' => [['parameter' => '', 'specification' => '']]
    ]
]);

$form = [
    'name' => '', 'short_name' => '', 'slug' => '', 'legal_entity' => '',
    'assets_heading' => '', 'assets_description' => '', 'display_order' => '0',
    'seo_data' => $defaultSeo,
    'hero_data' => $defaultHero,
    'overview_data' => $defaultOverview,
    'plant_units' => $defaultPlantUnits,
    'telemetry_data' => '[]',
    'media_gallery_data' => '[]',
    'timeline_data' => '[]',
    'is_published' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, $_POST);
    $form['is_published'] = isset($_POST['is_published']) ? '1' : '0';

    if (empty(trim($form['name']))) $errors[] = 'Vertical Name is required.';
    if (empty(trim($form['slug']))) $errors[] = 'Slug URL is required.';

    $uploadedFilesCleanup = [];

    try {
        // 1. Process Hero Image Upload & merge into hero_data JSON
        $heroArr = json_decode($form['hero_data'] ?? '{}', true) ?: [];
        if (isset($_FILES['hero_image_file']) && $_FILES['hero_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $heroPath = handleProjectImageUpload($_FILES['hero_image_file']);
            if ($heroPath) {
                $heroArr['heroImage'] = $heroPath;
                $uploadedFilesCleanup[] = $heroPath;
            }
        }
        $form['hero_data'] = json_encode($heroArr);

        // 2. Process Plant Unit Image Uploads & merge into plant_units JSON
        $plantsArr = json_decode($form['plant_units'] ?? '[]', true) ?: [];
        if (isset($_FILES['plant_images']['name']) && is_array($_FILES['plant_images']['name'])) {
            foreach ($_FILES['plant_images']['name'] as $idx => $name) {
                if ($_FILES['plant_images']['error'][$idx] !== UPLOAD_ERR_NO_FILE) {
                    $singleFile = [
                        'name'     => $_FILES['plant_images']['name'][$idx],
                        'type'     => $_FILES['plant_images']['type'][$idx],
                        'tmp_name' => $_FILES['plant_images']['tmp_name'][$idx],
                        'error'    => $_FILES['plant_images']['error'][$idx],
                        'size'     => $_FILES['plant_images']['size'][$idx],
                    ];
                    $plantImgPath = handleProjectImageUpload($singleFile);
                    if ($plantImgPath && isset($plantsArr[$idx])) {
                        $plantsArr[$idx]['image'] = $plantImgPath;
                        $uploadedFilesCleanup[] = $plantImgPath;
                    }
                }
            }
        }
        $form['plant_units'] = json_encode($plantsArr);

        // 3. Process Media Gallery Image Uploads & merge into media_gallery_data JSON
        $galleryArr = json_decode($form['media_gallery_data'] ?? '[]', true) ?: [];
        if (isset($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            foreach ($_FILES['gallery_images']['name'] as $idx => $name) {
                if ($_FILES['gallery_images']['error'][$idx] !== UPLOAD_ERR_NO_FILE) {
                    $singleFile = [
                        'name'     => $_FILES['gallery_images']['name'][$idx],
                        'type'     => $_FILES['gallery_images']['type'][$idx],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$idx],
                        'error'    => $_FILES['gallery_images']['error'][$idx],
                        'size'     => $_FILES['gallery_images']['size'][$idx],
                    ];
                    $galImgPath = handleProjectImageUpload($singleFile);
                    if ($galImgPath && isset($galleryArr[$idx])) {
                        $galleryArr[$idx]['image'] = $galImgPath;
                        $uploadedFilesCleanup[] = $galImgPath;
                    }
                }
            }
        }
        $form['media_gallery_data'] = json_encode($galleryArr);

    } catch (Exception $e) {
        $errors[] = 'Image Upload Error: ' . $e->getMessage();
    }

    // Validate all JSON fields
    $validatedJson = [];
    $jsonFields = ['seo_data', 'hero_data', 'overview_data', 'plant_units', 'telemetry_data', 'media_gallery_data', 'timeline_data'];
    
    foreach ($jsonFields as $field) {
        try {
            $rawVal = trim($form[$field] ?? '');
            if ($rawVal === '') {
                $rawVal = in_array($field, ['seo_data', 'hero_data', 'overview_data']) ? '{}' : '[]';
            }
            $validatedJson[$field] = projectsValidateJson($rawVal);
        } catch (Exception $e) {
            $errors[] = "System error parsing " . str_replace('_', ' ', strtoupper($field));
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
            // Cleanup uploaded files if DB insert fails
            foreach ($uploadedFilesCleanup as $path) {
                if (function_exists('projectsDeleteFile')) projectsDeleteFile($path);
            }
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

<div class="mx-auto max-w-5xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Verticals
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Add Business Vertical</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Please fix the following:</p>
            <ul class="list-disc pl-5 text-sm text-red-700 font-medium">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="projectForm">
        <!-- 1. Core Identity -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Core Identity</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" id="nameInput" name="name" required value="<?= htmlspecialchars($form['name']) ?>" placeholder="e.g. Hydro Power Generation" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Name / Tab Label *</label>
                    <input type="text" name="short_name" required value="<?= htmlspecialchars($form['short_name']) ?>" placeholder="e.g. Hydro Power" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug (Auto-Generated or Custom) *</label>
                    <input type="text" id="slugInput" name="slug" required value="<?= htmlspecialchars($form['slug']) ?>" placeholder="e.g. hydro-power" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
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
                    <textarea name="assets_description" rows="2" placeholder="Brief overview of the assets and plants under this vertical..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['assets_description']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- 2. Hero Section & Key Metrics -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-bold text-pingaksh-navy">Hero Banner & Key Metrics</h3>
            <p class="text-xs text-slate-500 mb-5">Configure the top banner presentation, upload the hero image, and highlight key metrics.</p>
            
            <div class="grid gap-5 md:grid-cols-2 mb-6">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Title</label>
                    <input type="text" id="hero_title" placeholder="e.g. Clean Run-of-the-River Hydro Energy" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Subtitle</label>
                    <input type="text" id="hero_subtitle" placeholder="e.g. Harnessing Himalayan river basins sustainably" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Banner Image</label>
                    <input type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-pingaksh-sand file:px-4 file:py-2.5 file:font-semibold file:text-pingaksh-navy cursor-pointer">
                    <div id="hero_current_img_badge" class="mt-1.5 text-xs text-slate-500 hidden"></div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Image Alt Text (SEO)</label>
                    <input type="text" id="hero_image_alt" placeholder="e.g. Dam and hydro turbine facility" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-semibold text-pingaksh-navy">Key Metrics (Hero Counters)</label>
                    <button type="button" onclick="addHeroMetric()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Metric
                    </button>
                </div>
                <div id="hero_metrics_container" class="space-y-3"></div>
            </div>
            <input type="hidden" name="hero_data" id="hero_data_hidden" value="<?= htmlspecialchars($form['hero_data']) ?>">
        </section>

        <!-- 3. Overview Content & Highlights -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-bold text-pingaksh-navy">Overview & Strategic Highlights</h3>
            <p class="text-xs text-slate-500 mb-5">Detailed narrative and key highlight cards displayed in the overview section.</p>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Overview Headline</label>
                    <input type="text" id="overview_headline" placeholder="e.g. Engineering Reliable Base-Load Renewable Power" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Lead Paragraph</label>
                        <textarea id="overview_lead" rows="3" placeholder="Primary introductory paragraph..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"></textarea>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Body Paragraph</label>
                        <textarea id="overview_body" rows="3" placeholder="Secondary supporting paragraph..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-semibold text-pingaksh-navy">Overview Highlights</label>
                    <button type="button" onclick="addOverviewHighlight()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Highlight
                    </button>
                </div>
                <div id="overview_highlights_container" class="space-y-3"></div>
            </div>
            <input type="hidden" name="overview_data" id="overview_data_hidden" value="<?= htmlspecialchars($form['overview_data']) ?>">
        </section>

        <!-- 4. Plant Units (Assets Array with Image Upload) -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-bold text-pingaksh-navy">Plant Units & Infrastructure Assets</h3>
                    <p class="text-xs text-slate-500">Add individual power plants, upload unit photos, and list technical specifications.</p>
                </div>
                <button type="button" onclick="addPlantUnit()" class="text-xs px-3.5 py-2 rounded-xl bg-pingaksh-navy text-pingaksh-sand hover:bg-pingaksh-navyDark transition font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Plant Unit
                </button>
            </div>

            <div id="plant_units_container" class="space-y-6"></div>
            <p id="plant_units_empty" class="text-xs text-slate-400 italic hidden">No plant units added yet. Click "Add Plant Unit" to create one.</p>
            <input type="hidden" name="plant_units" id="plant_units_hidden" value="<?= htmlspecialchars($form['plant_units']) ?>">
        </section>

        <!-- 5. Telemetry, Media Gallery & Timeline -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm space-y-8">
            <div>
                <h3 class="mb-1 font-bold text-pingaksh-navy">Telemetry, Media Gallery & Timeline</h3>
                <p class="text-xs text-slate-500">Manage operational telemetry metrics, upload gallery photos, and add historical milestones.</p>
            </div>

            <!-- Telemetry Data -->
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-sm font-semibold text-pingaksh-navy">Telemetry / Operational Metrics</label>
                        <p class="text-xs text-slate-400">Live or recorded performance indicators (Label, Value, Unit, Status).</p>
                    </div>
                    <button type="button" onclick="addTelemetryItem()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Telemetry Metric
                    </button>
                </div>
                <div id="telemetry_container" class="space-y-3"></div>
                <input type="hidden" name="telemetry_data" id="telemetry_data_hidden" value="<?= htmlspecialchars($form['telemetry_data']) ?>">
            </div>

            <!-- Media Gallery Data (With Image Upload) -->
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-sm font-semibold text-pingaksh-navy">Media Gallery</label>
                        <p class="text-xs text-slate-400">Upload site photos and provide alt text and captions.</p>
                    </div>
                    <button type="button" onclick="addGalleryItem()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Gallery Image
                    </button>
                </div>
                <div id="gallery_container" class="space-y-3"></div>
                <p id="gallery_empty" class="text-xs text-slate-400 italic hidden">No gallery images added. Click + to add.</p>
                <input type="hidden" name="media_gallery_data" id="media_gallery_data_hidden" value="<?= htmlspecialchars($form['media_gallery_data']) ?>">
            </div>

            <!-- Timeline Data -->
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-sm font-semibold text-pingaksh-navy">Project Timeline & Milestones</label>
                        <p class="text-xs text-slate-400">Key chronological milestones (Year/Period, Milestone Title, Description).</p>
                    </div>
                    <button type="button" onclick="addTimelineItem()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Milestone
                    </button>
                </div>
                <div id="timeline_container" class="space-y-3"></div>
                <input type="hidden" name="timeline_data" id="timeline_data_hidden" value="<?= htmlspecialchars($form['timeline_data']) ?>">
            </div>
        </section>

        <!-- 6. SEO & Metadata -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-bold text-pingaksh-navy">SEO & Search Metadata</h3>
            <p class="text-xs text-slate-500 mb-5">Configure search engine meta tags and keywords for Next.js page head.</p>

            <div class="grid gap-5 md:grid-cols-2 mb-5">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Meta Title</label>
                    <input type="text" id="seo_title" placeholder="e.g. Hydro Power Generation | Pingaksh Energy" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Meta Description</label>
                    <textarea id="seo_description" rows="2" placeholder="Brief SEO summary for search engine results..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"></textarea>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-semibold text-pingaksh-navy">Target Keywords</label>
                    <button type="button" onclick="addSeoKeyword()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Keyword
                    </button>
                </div>
                <div id="seo_keywords_container" class="grid gap-2 sm:grid-cols-2 md:grid-cols-3"></div>
            </div>
            <input type="hidden" name="seo_data" id="seo_data_hidden" value="<?= htmlspecialchars($form['seo_data']) ?>">
        </section>

        <!-- 7. Settings -->
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

        <!-- Sticky Footer Actions -->
        <div class="flex justify-end gap-3 sticky bottom-6 z-40 bg-white/80 backdrop-blur-md p-4 rounded-2xl border border-sand-200 shadow-xl">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="button" onclick="submitProjectForm()" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                <i data-lucide="save" class="h-4 w-4"></i> Save Project
            </button>
        </div>
    </form>
</div>

<script>
// --- Auto-Generate Slug on Create ---
let slugManuallyEdited = false;
document.getElementById('slugInput').addEventListener('input', () => { slugManuallyEdited = true; });
document.getElementById('nameInput').addEventListener('input', (e) => {
    if (!slugManuallyEdited) {
        const slug = e.target.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
        document.getElementById('slugInput').value = slug;
    }
});

function safeParse(jsonStr, fallback) {
    if (!jsonStr || jsonStr.trim() === '') return fallback;
    try {
        const parsed = JSON.parse(jsonStr);
        return parsed !== null && typeof parsed === 'object' ? parsed : fallback;
    } catch (e) {
        return fallback;
    }
}

const rawSeo = safeParse(document.getElementById('seo_data_hidden').value, {});
const rawHero = safeParse(document.getElementById('hero_data_hidden').value, {});
const rawOverview = safeParse(document.getElementById('overview_data_hidden').value, {});
const rawPlants = safeParse(document.getElementById('plant_units_hidden').value, []);
const rawTelemetry = safeParse(document.getElementById('telemetry_data_hidden').value, []);
const rawGallery = safeParse(document.getElementById('media_gallery_data_hidden').value, []);
const rawTimeline = safeParse(document.getElementById('timeline_data_hidden').value, []);

const state = {
    seo: {
        title: rawSeo.title || '',
        description: rawSeo.description || '',
        keywords: Array.isArray(rawSeo.keywords) ? rawSeo.keywords : []
    },
    hero: {
        title: rawHero.title || '',
        subtitle: rawHero.subtitle || '',
        heroImage: rawHero.heroImage || '',
        heroImageAlt: rawHero.heroImageAlt || '',
        keyMetrics: Array.isArray(rawHero.keyMetrics) ? rawHero.keyMetrics : []
    },
    overview: {
        headline: rawOverview.headline || '',
        leadParagraph: rawOverview.leadParagraph || '',
        bodyParagraph: rawOverview.bodyParagraph || '',
        highlights: Array.isArray(rawOverview.highlights) ? rawOverview.highlights : []
    },
    telemetry: Array.isArray(rawTelemetry) ? rawTelemetry : (Array.isArray(rawTelemetry.metrics) ? rawTelemetry.metrics : []),
    timeline: Array.isArray(rawTimeline) ? rawTimeline : (Array.isArray(rawTimeline.milestones) ? rawTimeline.milestones : [])
};

const TRASH_ICON = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>`;

function bindStaticInputs() {
    // SEO
    const seoTitle = document.getElementById('seo_title');
    const seoDesc = document.getElementById('seo_description');
    seoTitle.value = state.seo.title;
    seoDesc.value = state.seo.description;
    seoTitle.addEventListener('input', e => { state.seo.title = e.target.value; syncAllToHidden(); });
    seoDesc.addEventListener('input', e => { state.seo.description = e.target.value; syncAllToHidden(); });

    // Hero
    const heroTitle = document.getElementById('hero_title');
    const heroSub = document.getElementById('hero_subtitle');
    const heroAlt = document.getElementById('hero_image_alt');
    const heroBadge = document.getElementById('hero_current_img_badge');
    heroTitle.value = state.hero.title;
    heroSub.value = state.hero.subtitle;
    heroAlt.value = state.hero.heroImageAlt;
    if (state.hero.heroImage) {
        heroBadge.classList.remove('hidden');
        heroBadge.innerHTML = `Saved Image: <span class="font-mono text-pingaksh-navy">${state.hero.heroImage}</span>`;
    }
    heroTitle.addEventListener('input', e => { state.hero.title = e.target.value; syncAllToHidden(); });
    heroSub.addEventListener('input', e => { state.hero.subtitle = e.target.value; syncAllToHidden(); });
    heroAlt.addEventListener('input', e => { state.hero.heroImageAlt = e.target.value; syncAllToHidden(); });

    // Overview
    const ovHead = document.getElementById('overview_headline');
    const ovLead = document.getElementById('overview_lead');
    const ovBody = document.getElementById('overview_body');
    ovHead.value = state.overview.headline;
    ovLead.value = state.overview.leadParagraph;
    ovBody.value = state.overview.bodyParagraph;
    ovHead.addEventListener('input', e => { state.overview.headline = e.target.value; syncAllToHidden(); });
    ovLead.addEventListener('input', e => { state.overview.leadParagraph = e.target.value; syncAllToHidden(); });
    ovBody.addEventListener('input', e => { state.overview.bodyParagraph = e.target.value; syncAllToHidden(); });
}

// --- 1. SEO Keywords ---
function renderSeoKeywords() {
    const container = document.getElementById('seo_keywords_container');
    container.innerHTML = '';
    if (state.seo.keywords.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic col-span-full">No keywords added. Click + to add.</p>`;
        return;
    }
    state.seo.keywords.forEach((kw, idx) => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-1.5';
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'flex-1 rounded-lg border border-slate-200 bg-pingaksh-sandLight px-3 py-1.5 text-xs outline-none focus:border-pingaksh-terracotta';
        input.placeholder = 'e.g. Clean Energy';
        input.value = kw;
        input.addEventListener('input', e => { state.seo.keywords[idx] = e.target.value; syncAllToHidden(); });

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer shrink-0';
        btn.innerHTML = TRASH_ICON;
        btn.onclick = () => { state.seo.keywords.splice(idx, 1); renderSeoKeywords(); syncAllToHidden(); };

        div.append(input, btn);
        container.appendChild(div);
    });
}
window.addSeoKeyword = function() {
    state.seo.keywords.push('');
    renderSeoKeywords();
    syncAllToHidden();
};

// --- 2. Hero Key Metrics ---
function renderHeroMetrics() {
    const container = document.getElementById('hero_metrics_container');
    container.innerHTML = '';
    if (state.hero.keyMetrics.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No metrics added. Click + to add.</p>`;
        return;
    }
    state.hero.keyMetrics.forEach((m, idx) => {
        const div = document.createElement('div');
        div.className = 'grid grid-cols-1 md:grid-cols-10 gap-2 items-center p-3 rounded-xl border border-slate-200 bg-slate-50/60';

        const labelIn = document.createElement('input');
        labelIn.type = 'text';
        labelIn.className = 'md:col-span-4 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        labelIn.placeholder = 'Label (e.g. Installed Capacity)';
        labelIn.value = m.label || '';
        labelIn.addEventListener('input', e => { state.hero.keyMetrics[idx].label = e.target.value; syncAllToHidden(); });

        const valIn = document.createElement('input');
        valIn.type = 'text';
        valIn.className = 'md:col-span-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        valIn.placeholder = 'Value (e.g. 24.5)';
        valIn.value = m.value || '';
        valIn.addEventListener('input', e => { state.hero.keyMetrics[idx].value = e.target.value; syncAllToHidden(); });

        const unitIn = document.createElement('input');
        unitIn.type = 'text';
        unitIn.className = 'md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        unitIn.placeholder = 'Unit (e.g. MW)';
        unitIn.value = m.unit || '';
        unitIn.addEventListener('input', e => { state.hero.keyMetrics[idx].unit = e.target.value; syncAllToHidden(); });

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'md:col-span-1 flex justify-center p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
        btn.innerHTML = TRASH_ICON;
        btn.onclick = () => { state.hero.keyMetrics.splice(idx, 1); renderHeroMetrics(); syncAllToHidden(); };

        div.append(labelIn, valIn, unitIn, btn);
        container.appendChild(div);
    });
}
window.addHeroMetric = function() {
    state.hero.keyMetrics.push({ label: '', value: '', unit: '' });
    renderHeroMetrics();
    syncAllToHidden();
};

// --- 3. Overview Highlights ---
function renderOverviewHighlights() {
    const container = document.getElementById('overview_highlights_container');
    container.innerHTML = '';
    if (state.overview.highlights.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No highlights added. Click + to add.</p>`;
        return;
    }
    state.overview.highlights.forEach((h, idx) => {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50/60';

        const grid = document.createElement('div');
        grid.className = 'flex-1 grid gap-2 md:grid-cols-3';

        const titleIn = document.createElement('input');
        titleIn.type = 'text';
        titleIn.className = 'rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        titleIn.placeholder = 'Highlight Title (e.g. High PLF)';
        titleIn.value = h.title || '';
        titleIn.addEventListener('input', e => { state.overview.highlights[idx].title = e.target.value; syncAllToHidden(); });

        const detailIn = document.createElement('input');
        detailIn.type = 'text';
        detailIn.className = 'md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        detailIn.placeholder = 'Detail description';
        detailIn.value = h.detail || '';
        detailIn.addEventListener('input', e => { state.overview.highlights[idx].detail = e.target.value; syncAllToHidden(); });

        grid.append(titleIn, detailIn);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer shrink-0';
        btn.innerHTML = TRASH_ICON;
        btn.onclick = () => { state.overview.highlights.splice(idx, 1); renderOverviewHighlights(); syncAllToHidden(); };

        div.append(grid, btn);
        container.appendChild(div);
    });
}
window.addOverviewHighlight = function() {
    state.overview.highlights.push({ title: '', detail: '' });
    renderOverviewHighlights();
    syncAllToHidden();
};

// --- 4. Plant Units (DOM-Preserving Builder so File Inputs Never Reset) ---
function updatePlantUnitsEmptyState() {
    const container = document.getElementById('plant_units_container');
    const emptyMsg = document.getElementById('plant_units_empty');
    const cards = container.querySelectorAll('.plant-unit-card');
    emptyMsg.classList.toggle('hidden', cards.length > 0);
    cards.forEach((card, idx) => {
        card.querySelector('.plant-unit-title').textContent = `Plant Unit #${idx + 1}`;
    });
}

function createSpecRowElement(spec = { parameter: '', specification: '' }) {
    const sRow = document.createElement('div');
    sRow.className = 'spec-row flex items-center gap-2';

    const paramIn = document.createElement('input');
    paramIn.type = 'text';
    paramIn.className = 'spec-param flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs outline-none focus:border-pingaksh-terracotta';
    paramIn.placeholder = 'Parameter (e.g. Turbine Type)';
    paramIn.value = spec.parameter || '';
    paramIn.addEventListener('input', syncAllToHidden);

    const valIn = document.createElement('input');
    valIn.type = 'text';
    valIn.className = 'spec-val flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs outline-none focus:border-pingaksh-terracotta';
    valIn.placeholder = 'Specification (e.g. Horizontal Francis)';
    valIn.value = spec.specification || '';
    valIn.addEventListener('input', syncAllToHidden);

    const sDel = document.createElement('button');
    sDel.type = 'button';
    sDel.className = 'p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
    sDel.innerHTML = TRASH_ICON;
    sDel.onclick = () => {
        sRow.remove();
        syncAllToHidden();
    };

    sRow.append(paramIn, valIn, sDel);
    return sRow;
}

window.addPlantUnit = function(plantData = null) {
    const container = document.getElementById('plant_units_container');
    const nextNum = container.querySelectorAll('.plant-unit-card').length + 1;
    const p = plantData || {
        id: `plant-${nextNum}`, name: '', location: '', status: 'Operational',
        capacity: '', summary: '', image: '', imageAlt: '',
        technicalSpecs: [{ parameter: '', specification: '' }]
    };

    const card = document.createElement('div');
    card.className = 'plant-unit-card rounded-2xl border border-slate-200 bg-slate-50/70 p-5 space-y-4';
    card.dataset.existingImage = p.image || '';

    // Header
    const header = document.createElement('div');
    header.className = 'flex items-center justify-between border-b border-slate-200 pb-3';
    header.innerHTML = `<span class="plant-unit-title text-sm font-bold text-pingaksh-navy">Plant Unit #${nextNum}</span>`;
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'text-xs font-semibold text-red-600 hover:text-red-800 inline-flex items-center gap-1 cursor-pointer';
    delBtn.innerHTML = `${TRASH_ICON} Remove Unit`;
    delBtn.onclick = () => {
        card.remove();
        updatePlantUnitsEmptyState();
        syncAllToHidden();
    };
    header.appendChild(delBtn);

    // Grid Fields
    const grid = document.createElement('div');
    grid.className = 'grid gap-4 md:grid-cols-3';

    const makeInput = (label, cls, placeholder, val, colSpan = '') => {
        const wrap = document.createElement('div');
        if (colSpan) wrap.className = colSpan;
        wrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-slate-600">${label}</label>`;
        const input = document.createElement('input');
        input.type = 'text';
        input.className = `${cls} w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta`;
        input.placeholder = placeholder;
        input.value = val || '';
        input.addEventListener('input', syncAllToHidden);
        wrap.appendChild(input);
        return wrap;
    };

    // File Upload Field for Plant Image
    const fileWrap = document.createElement('div');
    fileWrap.className = 'md:col-span-2';
    fileWrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-slate-600">Plant Unit Image (Choose File)</label>`;
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.name = 'plant_images[]';
    fileInput.accept = 'image/jpeg,image/png,image/webp';
    fileInput.className = 'block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-3 file:border-0 file:bg-pingaksh-sand file:px-3 file:py-2 file:font-semibold file:text-pingaksh-navy cursor-pointer';
    fileWrap.appendChild(fileInput);
    if (p.image) {
        const badge = document.createElement('div');
        badge.className = 'mt-1 text-xs text-slate-500';
        badge.innerHTML = `Saved Image: <span class="font-mono text-pingaksh-navy">${p.image}</span>`;
        fileWrap.appendChild(badge);
    }

    grid.append(
        makeInput('Unit ID', 'plant-id', 'e.g. plant-1', p.id),
        makeInput('Plant Name', 'plant-name', 'e.g. SHP-I Bageshwar', p.name),
        makeInput('Location', 'plant-location', 'e.g. Uttarakhand, India', p.location),
        makeInput('Status', 'plant-status', 'e.g. Operational', p.status),
        makeInput('Capacity', 'plant-capacity', 'e.g. 12.5 MW', p.capacity),
        makeInput('Image Alt Text', 'plant-image-alt', 'e.g. Turbine hall interior', p.imageAlt),
        fileWrap
    );

    // Summary
    const sumWrap = document.createElement('div');
    sumWrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-slate-600">Unit Summary</label>`;
    const sumArea = document.createElement('textarea');
    sumArea.rows = 2;
    sumArea.className = 'plant-summary w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
    sumArea.placeholder = 'Brief operational summary of this plant unit...';
    sumArea.value = p.summary || '';
    sumArea.addEventListener('input', syncAllToHidden);
    sumWrap.appendChild(sumArea);

    // Technical Specs
    const specsWrap = document.createElement('div');
    specsWrap.className = 'rounded-xl bg-white p-4 border border-slate-200/80';
    const specsHeader = document.createElement('div');
    specsHeader.className = 'flex items-center justify-between mb-3';
    specsHeader.innerHTML = `<span class="text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Technical Specifications</span>`;

    const specsList = document.createElement('div');
    specsList.className = 'specs-list space-y-2';

    const addSpecBtn = document.createElement('button');
    addSpecBtn.type = 'button';
    addSpecBtn.className = 'text-xs font-bold text-pingaksh-terracotta hover:underline cursor-pointer';
    addSpecBtn.textContent = '+ Add Spec Row';
    addSpecBtn.onclick = () => {
        specsList.appendChild(createSpecRowElement());
        syncAllToHidden();
    };
    specsHeader.appendChild(addSpecBtn);

    const specsArr = Array.isArray(p.technicalSpecs) ? p.technicalSpecs : [];
    specsArr.forEach(s => specsList.appendChild(createSpecRowElement(s)));

    specsWrap.append(specsHeader, specsList);
    card.append(header, grid, sumWrap, specsWrap);
    container.appendChild(card);

    updatePlantUnitsEmptyState();
    syncAllToHidden();
};

// --- 5. Telemetry ---
function renderTelemetry() {
    const container = document.getElementById('telemetry_container');
    container.innerHTML = '';
    if (state.telemetry.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No telemetry metrics added. Click + to add.</p>`;
        return;
    }
    state.telemetry.forEach((t, idx) => {
        const div = document.createElement('div');
        div.className = 'grid grid-cols-1 md:grid-cols-10 gap-2 items-center p-3 rounded-xl border border-slate-200 bg-slate-50/60';

        const createIn = (colClass, placeholder, key) => {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = `${colClass} rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta`;
            input.placeholder = placeholder;
            input.value = t[key] || '';
            input.addEventListener('input', e => { state.telemetry[idx][key] = e.target.value; syncAllToHidden(); });
            return input;
        };

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'md:col-span-1 flex justify-center p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
        btn.innerHTML = TRASH_ICON;
        btn.onclick = () => { state.telemetry.splice(idx, 1); renderTelemetry(); syncAllToHidden(); };

        div.append(
            createIn('md:col-span-3', 'Metric Label (e.g. Grid Frequency)', 'label'),
            createIn('md:col-span-2', 'Value (e.g. 50.02)', 'value'),
            createIn('md:col-span-2', 'Unit (e.g. Hz)', 'unit'),
            createIn('md:col-span-2', 'Status (e.g. Optimal)', 'status'),
            btn
        );
        container.appendChild(div);
    });
}
window.addTelemetryItem = function() {
    state.telemetry.push({ label: '', value: '', unit: '', status: 'Optimal' });
    renderTelemetry();
    syncAllToHidden();
};

// --- 6. Media Gallery (DOM-Preserving Builder for File Inputs) ---
function updateGalleryEmptyState() {
    const container = document.getElementById('gallery_container');
    const emptyMsg = document.getElementById('gallery_empty');
    emptyMsg.classList.toggle('hidden', container.querySelectorAll('.gallery-item-row').length > 0);
}

window.addGalleryItem = function(galData = null) {
    const container = document.getElementById('gallery_container');
    const g = galData || { image: '', alt: '', caption: '' };

    const row = document.createElement('div');
    row.className = 'gallery-item-row grid grid-cols-1 md:grid-cols-10 gap-3 items-start p-3.5 rounded-xl border border-slate-200 bg-slate-50/60';
    row.dataset.existingImage = g.image || '';

    // File input column
    const fileCol = document.createElement('div');
    fileCol.className = 'md:col-span-4';
    const fileIn = document.createElement('input');
    fileIn.type = 'file';
    fileIn.name = 'gallery_images[]';
    fileIn.accept = 'image/jpeg,image/png,image/webp';
    fileIn.className = 'block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-3 file:border-0 file:bg-pingaksh-sand file:px-3 file:py-2 file:font-semibold file:text-pingaksh-navy cursor-pointer';
    fileCol.appendChild(fileIn);

    if (g.image) {
        const badge = document.createElement('div');
        badge.className = 'mt-1 text-xs text-slate-500 truncate';
        badge.innerHTML = `Saved: <span class="font-mono text-pingaksh-navy">${g.image}</span>`;
        fileCol.appendChild(badge);
    }

    const altIn = document.createElement('input');
    altIn.type = 'text';
    altIn.className = 'gallery-alt md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
    altIn.placeholder = 'Alt Text';
    altIn.value = g.alt || '';
    altIn.addEventListener('input', syncAllToHidden);

    const capIn = document.createElement('input');
    capIn.type = 'text';
    capIn.className = 'gallery-caption md:col-span-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
    capIn.placeholder = 'Caption';
    capIn.value = g.caption || '';
    capIn.addEventListener('input', syncAllToHidden);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'md:col-span-1 flex justify-center p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
    btn.innerHTML = TRASH_ICON;
    btn.onclick = () => {
        row.remove();
        updateGalleryEmptyState();
        syncAllToHidden();
    };

    row.append(fileCol, altIn, capIn, btn);
    container.appendChild(row);
    updateGalleryEmptyState();
    syncAllToHidden();
};

// --- 7. Timeline ---
function renderTimeline() {
    const container = document.getElementById('timeline_container');
    container.innerHTML = '';
    if (state.timeline.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No timeline milestones added. Click + to add.</p>`;
        return;
    }
    state.timeline.forEach((tm, idx) => {
        const div = document.createElement('div');
        div.className = 'grid grid-cols-1 md:grid-cols-10 gap-2 items-center p-3 rounded-xl border border-slate-200 bg-slate-50/60';

        const createIn = (colClass, placeholder, key) => {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = `${colClass} rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta`;
            input.placeholder = placeholder;
            input.value = tm[key] || '';
            input.addEventListener('input', e => { state.timeline[idx][key] = e.target.value; syncAllToHidden(); });
            return input;
        };

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'md:col-span-1 flex justify-center p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
        btn.innerHTML = TRASH_ICON;
        btn.onclick = () => { state.timeline.splice(idx, 1); renderTimeline(); syncAllToHidden(); };

        div.append(
            createIn('md:col-span-2', 'Year (e.g. 2018)', 'year'),
            createIn('md:col-span-3', 'Milestone Title', 'title'),
            createIn('md:col-span-4', 'Description', 'description'),
            btn
        );
        container.appendChild(div);
    });
}
window.addTimelineItem = function() {
    state.timeline.push({ year: '', title: '', description: '' });
    renderTimeline();
    syncAllToHidden();
};

// --- Collect DOM & State into Hidden Inputs ---
function syncAllToHidden() {
    // Collect Plant Units from DOM
    const plantUnitsData = [];
    document.querySelectorAll('#plant_units_container .plant-unit-card').forEach(card => {
        const specs = [];
        card.querySelectorAll('.specs-list .spec-row').forEach(sRow => {
            specs.push({
                parameter: sRow.querySelector('.spec-param').value,
                specification: sRow.querySelector('.spec-val').value
            });
        });
        plantUnitsData.push({
            id: card.querySelector('.plant-id').value,
            name: card.querySelector('.plant-name').value,
            location: card.querySelector('.plant-location').value,
            status: card.querySelector('.plant-status').value,
            capacity: card.querySelector('.plant-capacity').value,
            summary: card.querySelector('.plant-summary').value,
            image: card.dataset.existingImage || '',
            imageAlt: card.querySelector('.plant-image-alt').value,
            technicalSpecs: specs
        });
    });

    // Collect Gallery from DOM
    const galleryData = [];
    document.querySelectorAll('#gallery_container .gallery-item-row').forEach(row => {
        galleryData.push({
            image: row.dataset.existingImage || '',
            alt: row.querySelector('.gallery-alt').value,
            caption: row.querySelector('.gallery-caption').value
        });
    });

    document.getElementById('seo_data_hidden').value = JSON.stringify(state.seo);
    document.getElementById('hero_data_hidden').value = JSON.stringify(state.hero);
    document.getElementById('overview_data_hidden').value = JSON.stringify(state.overview);
    document.getElementById('plant_units_hidden').value = JSON.stringify(plantUnitsData);
    document.getElementById('telemetry_data_hidden').value = JSON.stringify(state.telemetry);
    document.getElementById('media_gallery_data_hidden').value = JSON.stringify(galleryData);
    document.getElementById('timeline_data_hidden').value = JSON.stringify(state.timeline);
}

window.submitProjectForm = function() {
    syncAllToHidden();
    const formEl = document.getElementById('projectForm');
    if (formEl.reportValidity()) {
        formEl.submit();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    bindStaticInputs();
    renderSeoKeywords();
    renderHeroMetrics();
    renderOverviewHighlights();
    renderTelemetry();
    renderTimeline();

    // Populate initial Plant Units
    const initialPlants = Array.isArray(rawPlants) ? rawPlants : [];
    if (initialPlants.length === 0) updatePlantUnitsEmptyState();
    else initialPlants.forEach(p => addPlantUnit(p));

    // Populate initial Gallery Items
    const initialGallery = Array.isArray(rawGallery) ? rawGallery : (Array.isArray(rawGallery.images) ? rawGallery.images : []);
    if (initialGallery.length === 0) updateGalleryEmptyState();
    else initialGallery.forEach(g => addGalleryItem(g));

    syncAllToHidden();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
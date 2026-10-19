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
            $rawVal = trim($_POST[$field] ?? '');
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

<div class="mx-auto max-w-5xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Verticals
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Edit Business Vertical</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Please fix the following:</p>
            <ul class="list-disc pl-5 text-sm text-red-700 font-medium">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?= $id ?>" method="POST" class="space-y-6" id="projectForm">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- 1. Core Identity -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Core Identity</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($item['name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Name / Tab Label *</label>
                    <input type="text" name="short_name" required value="<?= htmlspecialchars($item['short_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($item['slug'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Legal Entity / SPV</label>
                    <input type="text" name="legal_entity" value="<?= htmlspecialchars($item['legal_entity'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Assets Heading</label>
                    <input type="text" name="assets_heading" value="<?= htmlspecialchars($item['assets_heading'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Assets Description</label>
                    <textarea name="assets_description" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($item['assets_description'] ?? '') ?></textarea>
                </div>
            </div>
        </section>

        <!-- 2. Hero Section & Key Metrics -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-bold text-pingaksh-navy">Hero Banner & Key Metrics</h3>
            <p class="text-xs text-slate-500 mb-5">Configure the top banner presentation and highlight metrics for this vertical.</p>
            
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
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Image URL / Path</label>
                    <input type="text" id="hero_image" placeholder="e.g. /images/projects/hydro-hero.jpg" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Hero Image Alt Text</label>
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
            <input type="hidden" name="hero_data" id="hero_data_hidden" value="<?= htmlspecialchars($item['hero_data'] ?? '{}') ?>">
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
            <input type="hidden" name="overview_data" id="overview_data_hidden" value="<?= htmlspecialchars($item['overview_data'] ?? '{}') ?>">
        </section>

        <!-- 4. Plant Units (Assets Array) -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-bold text-pingaksh-navy">Plant Units & Infrastructure Assets</h3>
                    <p class="text-xs text-slate-500">Manage individual power plants, operational units, and their technical specifications.</p>
                </div>
                <button type="button" onclick="addPlantUnit()" class="text-xs px-3.5 py-2 rounded-xl bg-pingaksh-navy text-pingaksh-sand hover:bg-pingaksh-navyDark transition font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Plant Unit
                </button>
            </div>

            <div id="plant_units_container" class="space-y-6"></div>
            <input type="hidden" name="plant_units" id="plant_units_hidden" value="<?= htmlspecialchars($item['plant_units'] ?? '[]') ?>">
        </section>

        <!-- 5. Telemetry, Media Gallery & Timeline -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm space-y-8">
            <div>
                <h3 class="mb-1 font-bold text-pingaksh-navy">Telemetry, Media Gallery & Timeline</h3>
                <p class="text-xs text-slate-500">Manage operational telemetry metrics, photo gallery items, and historical milestones.</p>
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
                <input type="hidden" name="telemetry_data" id="telemetry_data_hidden" value="<?= htmlspecialchars($item['telemetry_data'] ?? '[]') ?>">
            </div>

            <!-- Media Gallery Data -->
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-sm font-semibold text-pingaksh-navy">Media Gallery</label>
                        <p class="text-xs text-slate-400">Site photos and visual assets (Image URL, Alt Text, Caption).</p>
                    </div>
                    <button type="button" onclick="addGalleryItem()" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Gallery Image
                    </button>
                </div>
                <div id="gallery_container" class="space-y-3"></div>
                <input type="hidden" name="media_gallery_data" id="media_gallery_data_hidden" value="<?= htmlspecialchars($item['media_gallery_data'] ?? '[]') ?>">
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
                <input type="hidden" name="timeline_data" id="timeline_data_hidden" value="<?= htmlspecialchars($item['timeline_data'] ?? '[]') ?>">
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
            <input type="hidden" name="seo_data" id="seo_data_hidden" value="<?= htmlspecialchars($item['seo_data'] ?? '{}') ?>">
        </section>

        <!-- 7. Settings -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Order</label>
                    <input type="number" name="display_order" value="<?= (int)($item['display_order'] ?? 0) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= !empty($item['is_published']) ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live on Website</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Sticky Footer Actions -->
        <div class="flex justify-end gap-3 sticky bottom-6 z-40 bg-white/80 backdrop-blur-md p-4 rounded-2xl border border-sand-200 shadow-xl">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="button" onclick="submitProjectForm()" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                <i data-lucide="save" class="h-4 w-4"></i> Save Updates
            </button>
        </div>
    </form>
</div>

<script>
// --- Safe JSON Parser Helper ---
function safeParse(jsonStr, fallback) {
    if (!jsonStr || jsonStr.trim() === '') return fallback;
    try {
        const parsed = JSON.parse(jsonStr);
        return parsed !== null && typeof parsed === 'object' ? parsed : fallback;
    } catch (e) {
        return fallback;
    }
}

// --- Initialize Raw Data from Database Hidden Inputs ---
const rawSeo = safeParse(document.getElementById('seo_data_hidden').value, {});
const rawHero = safeParse(document.getElementById('hero_data_hidden').value, {});
const rawOverview = safeParse(document.getElementById('overview_data_hidden').value, {});
const rawPlants = safeParse(document.getElementById('plant_units_hidden').value, []);
const rawTelemetry = safeParse(document.getElementById('telemetry_data_hidden').value, []);
const rawGallery = safeParse(document.getElementById('media_gallery_data_hidden').value, []);
const rawTimeline = safeParse(document.getElementById('timeline_data_hidden').value, []);

// --- Unified Form State ---
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
    plantUnits: Array.isArray(rawPlants) ? rawPlants.map((p, i) => ({
        id: p.id || `plant-${i + 1}`,
        name: p.name || '',
        location: p.location || '',
        status: p.status || 'Operational',
        capacity: p.capacity || '',
        summary: p.summary || '',
        image: p.image || '',
        imageAlt: p.imageAlt || '',
        technicalSpecs: Array.isArray(p.technicalSpecs) ? p.technicalSpecs : []
    })) : [],
    telemetry: Array.isArray(rawTelemetry) ? rawTelemetry : (Array.isArray(rawTelemetry.metrics) ? rawTelemetry.metrics : []),
    gallery: Array.isArray(rawGallery) ? rawGallery : (Array.isArray(rawGallery.images) ? rawGallery.images : []),
    timeline: Array.isArray(rawTimeline) ? rawTimeline : (Array.isArray(rawTimeline.milestones) ? rawTimeline.milestones : [])
};

const TRASH_ICON = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>`;

// --- Bind Static Object Inputs ---
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
    const heroImg = document.getElementById('hero_image');
    const heroAlt = document.getElementById('hero_image_alt');
    heroTitle.value = state.hero.title;
    heroSub.value = state.hero.subtitle;
    heroImg.value = state.hero.heroImage;
    heroAlt.value = state.hero.heroImageAlt;
    heroTitle.addEventListener('input', e => { state.hero.title = e.target.value; syncAllToHidden(); });
    heroSub.addEventListener('input', e => { state.hero.subtitle = e.target.value; syncAllToHidden(); });
    heroImg.addEventListener('input', e => { state.hero.heroImage = e.target.value; syncAllToHidden(); });
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

// --- 1. Render SEO Keywords ---
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

// --- 2. Render Hero Key Metrics ---
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

// --- 3. Render Overview Highlights ---
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
        detailIn.placeholder = 'Detail description (e.g. Snow-fed perennial river ensures year-round generation)';
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

// --- 4. Render Plant Units (With Nested Technical Specs) ---
function renderPlantUnits() {
    const container = document.getElementById('plant_units_container');
    container.innerHTML = '';
    if (state.plantUnits.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No plant units added yet. Click "Add Plant Unit" to create one.</p>`;
        return;
    }

    state.plantUnits.forEach((plant, pIdx) => {
        const card = document.createElement('div');
        card.className = 'rounded-2xl border border-slate-200 bg-slate-50/70 p-5 space-y-4';

        // Header bar
        const header = document.createElement('div');
        header.className = 'flex items-center justify-between border-b border-slate-200 pb-3';
        header.innerHTML = `<span class="text-sm font-bold text-pingaksh-navy">Plant Unit #${pIdx + 1}</span>`;
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'text-xs font-semibold text-red-600 hover:text-red-800 inline-flex items-center gap-1 cursor-pointer';
        delBtn.innerHTML = `${TRASH_ICON} Remove Unit`;
        delBtn.onclick = () => { state.plantUnits.splice(pIdx, 1); renderPlantUnits(); syncAllToHidden(); };
        header.appendChild(delBtn);

        // Fields Grid
        const grid = document.createElement('div');
        grid.className = 'grid gap-4 md:grid-cols-3';

        const createField = (label, key, placeholder, colSpan = '') => {
            const wrap = document.createElement('div');
            if (colSpan) wrap.className = colSpan;
            wrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-slate-600">${label}</label>`;
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
            input.placeholder = placeholder;
            input.value = plant[key] || '';
            input.addEventListener('input', e => { state.plantUnits[pIdx][key] = e.target.value; syncAllToHidden(); });
            wrap.appendChild(input);
            return wrap;
        };

        grid.append(
            createField('Unit ID', 'id', 'e.g. plant-1'),
            createField('Plant Name', 'name', 'e.g. SHP-I Bageshwar'),
            createField('Location', 'location', 'e.g. Uttarakhand, India'),
            createField('Status', 'status', 'e.g. Operational / Under Construction'),
            createField('Capacity', 'capacity', 'e.g. 12.5 MW'),
            createField('Image URL', 'image', 'e.g. /images/plants/unit1.jpg'),
            createField('Image Alt Text', 'imageAlt', 'e.g. Turbine hall interior', 'md:col-span-3')
        );

        // Summary Textarea
        const sumWrap = document.createElement('div');
        sumWrap.innerHTML = `<label class="mb-1 block text-xs font-semibold text-slate-600">Unit Summary</label>`;
        const sumArea = document.createElement('textarea');
        sumArea.rows = 2;
        sumArea.className = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        sumArea.placeholder = 'Brief operational summary of this plant unit...';
        sumArea.value = plant.summary || '';
        sumArea.addEventListener('input', e => { state.plantUnits[pIdx].summary = e.target.value; syncAllToHidden(); });
        sumWrap.appendChild(sumArea);

        // Nested Technical Specs
        const specsWrap = document.createElement('div');
        specsWrap.className = 'rounded-xl bg-white p-4 border border-slate-200/80';
        const specsHeader = document.createElement('div');
        specsHeader.className = 'flex items-center justify-between mb-3';
        specsHeader.innerHTML = `<span class="text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Technical Specifications</span>`;
        const addSpecBtn = document.createElement('button');
        addSpecBtn.type = 'button';
        addSpecBtn.className = 'text-xs font-bold text-pingaksh-terracotta hover:underline cursor-pointer';
        addSpecBtn.textContent = '+ Add Spec Row';
        addSpecBtn.onclick = () => {
            state.plantUnits[pIdx].technicalSpecs.push({ parameter: '', specification: '' });
            renderPlantUnits();
            syncAllToHidden();
        };
        specsHeader.appendChild(addSpecBtn);
        specsWrap.appendChild(specsHeader);

        const specsList = document.createElement('div');
        specsList.className = 'space-y-2';
        if (!plant.technicalSpecs || plant.technicalSpecs.length === 0) {
            specsList.innerHTML = `<p class="text-xs text-slate-400 italic">No technical specs added.</p>`;
        } else {
            plant.technicalSpecs.forEach((spec, sIdx) => {
                const sRow = document.createElement('div');
                sRow.className = 'flex items-center gap-2';

                const paramIn = document.createElement('input');
                paramIn.type = 'text';
                paramIn.className = 'flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs outline-none focus:border-pingaksh-terracotta';
                paramIn.placeholder = 'Parameter (e.g. Turbine Type)';
                paramIn.value = spec.parameter || '';
                paramIn.addEventListener('input', e => {
                    state.plantUnits[pIdx].technicalSpecs[sIdx].parameter = e.target.value;
                    syncAllToHidden();
                });

                const valIn = document.createElement('input');
                valIn.type = 'text';
                valIn.className = 'flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs outline-none focus:border-pingaksh-terracotta';
                valIn.placeholder = 'Specification (e.g. Horizontal Francis)';
                valIn.value = spec.specification || '';
                valIn.addEventListener('input', e => {
                    state.plantUnits[pIdx].technicalSpecs[sIdx].specification = e.target.value;
                    syncAllToHidden();
                });

                const sDel = document.createElement('button');
                sDel.type = 'button';
                sDel.className = 'p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
                sDel.innerHTML = TRASH_ICON;
                sDel.onclick = () => {
                    state.plantUnits[pIdx].technicalSpecs.splice(sIdx, 1);
                    renderPlantUnits();
                    syncAllToHidden();
                };

                sRow.append(paramIn, valIn, sDel);
                specsList.appendChild(sRow);
            });
        }
        specsWrap.appendChild(specsList);

        card.append(header, grid, sumWrap, specsWrap);
        container.appendChild(card);
    });
}
window.addPlantUnit = function() {
    const nextNum = state.plantUnits.length + 1;
    state.plantUnits.push({
        id: `plant-${nextNum}`,
        name: '', location: '', status: 'Operational', capacity: '', summary: '', image: '', imageAlt: '',
        technicalSpecs: [{ parameter: '', specification: '' }]
    });
    renderPlantUnits();
    syncAllToHidden();
};

// --- 5. Render Telemetry ---
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

// --- 6. Render Media Gallery ---
function renderGallery() {
    const container = document.getElementById('gallery_container');
    container.innerHTML = '';
    if (state.gallery.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No gallery images added. Click + to add.</p>`;
        return;
    }
    state.gallery.forEach((g, idx) => {
        const div = document.createElement('div');
        div.className = 'grid grid-cols-1 md:grid-cols-10 gap-2 items-center p-3 rounded-xl border border-slate-200 bg-slate-50/60';

        const createIn = (colClass, placeholder, key) => {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = `${colClass} rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta`;
            input.placeholder = placeholder;
            input.value = g[key] || '';
            input.addEventListener('input', e => { state.gallery[idx][key] = e.target.value; syncAllToHidden(); });
            return input;
        };

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'md:col-span-1 flex justify-center p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 cursor-pointer';
        btn.innerHTML = TRASH_ICON;
        btn.onclick = () => { state.gallery.splice(idx, 1); renderGallery(); syncAllToHidden(); };

        div.append(
            createIn('md:col-span-4', 'Image URL (e.g. /images/gallery/1.jpg)', 'image'),
            createIn('md:col-span-2', 'Alt Text', 'alt'),
            createIn('md:col-span-3', 'Caption', 'caption'),
            btn
        );
        container.appendChild(div);
    });
}
window.addGalleryItem = function() {
    state.gallery.push({ image: '', alt: '', caption: '' });
    renderGallery();
    syncAllToHidden();
};

// --- 7. Render Timeline ---
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

// --- Sync State to Hidden JSON Inputs ---
function syncAllToHidden() {
    document.getElementById('seo_data_hidden').value = JSON.stringify(state.seo);
    document.getElementById('hero_data_hidden').value = JSON.stringify(state.hero);
    document.getElementById('overview_data_hidden').value = JSON.stringify(state.overview);
    document.getElementById('plant_units_hidden').value = JSON.stringify(state.plantUnits);
    document.getElementById('telemetry_data_hidden').value = JSON.stringify(state.telemetry);
    document.getElementById('media_gallery_data_hidden').value = JSON.stringify(state.gallery);
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
    renderPlantUnits();
    renderTelemetry();
    renderGallery();
    renderTimeline();
    syncAllToHidden();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
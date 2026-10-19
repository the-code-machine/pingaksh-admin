<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Gallery Media';
$activeMenu = 'gallery';

$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(g.title LIKE ? OR g.caption LIKE ? OR g.location LIKE ? OR g.asset_code LIKE ?)";
    $searchValue = "%{$search}%";
    array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
}

if ($category !== '') {
    $where[] = "g.category_id = ?";
    $params[] = $category;
}

if ($type !== '') {
    $where[] = "g.type = ?";
    $params[] = $type;
}

$sql = "
    SELECT g.*, c.name AS category_name
    FROM gallery_items g
    LEFT JOIN gallery_categories c ON g.category_id = c.id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY g.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = $pdo->query("SELECT id, name FROM gallery_categories ORDER BY name")->fetchAll();

$success = null;
if (isset($_GET['created'])) $success = 'Gallery media uploaded successfully.';
if (isset($_GET['updated'])) $success = 'Gallery media updated successfully.';
if (isset($_GET['deleted'])) $success = 'Gallery media deleted successfully.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">

    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">
                Media Library
            </p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">
                Project & Operations Gallery
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Manage images and videos displayed on the Pingaksh Renewables website.
            </p>
        </div>

        <a
            href="create.php"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-pingaksh-terracottaHover"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Media
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i data-lucide="circle-check" class="h-5 w-5 text-emerald-600"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="mb-6 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 md:grid-cols-12">
            <!-- Search -->
            <div class="md:col-span-5">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search title, location or asset code..."
                        class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-pingaksh-terracotta focus:bg-white"
                    >
                </div>
            </div>

            <!-- Category -->
            <div class="md:col-span-3">
                <select
                    name="category"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta focus:bg-white"
                >
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Type -->
            <div class="md:col-span-2">
                <select
                    name="type"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta focus:bg-white"
                >
                    <option value="">All media</option>
                    <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Images</option>
                    <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Videos</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 md:col-span-2">
                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-pingaksh-navy px-4 py-2.5 text-sm font-semibold text-pingaksh-sand transition hover:bg-pingaksh-navyDark"
                >
                    Filter
                </button>

                <?php if ($search || $category || $type): ?>
                    <a
                        href="index.php"
                        class="flex items-center justify-center rounded-xl border border-slate-200 px-3 text-slate-500 hover:bg-slate-50"
                        title="Clear filters"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-bold text-pingaksh-navy">Media Items</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?>
                </p>
            </div>
        </div>

        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pingaksh-sand/50 text-pingaksh-navy">
                    <i data-lucide="images" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 font-bold text-pingaksh-navy">No media found</h3>
                <p class="mt-1 text-sm text-slate-500">Start by adding your first gallery image or video.</p>
                <a
                    href="create.php"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-4 py-2.5 text-sm font-semibold text-white hover:bg-pingaksh-terracottaHover"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Media
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-pingaksh-sand/30">
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Preview</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Content</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Category</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Type</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <?php
                                // Uses thumbnail_url if available, otherwise falls back to media_url!
                                $previewUrl = galleryPreviewUrl($item);
                                $resolvedMediaUrl = galleryResolveUrl($item['media_url'] ?? '');
                            ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <!-- Preview Image / Video -->
                                <td class="px-5 py-4">
                                    <a
                                        href="<?= htmlspecialchars($resolvedMediaUrl ?: '#') ?>"
                                        target="_blank"
                                        title="Click to view full file"
                                        class="block relative h-16 w-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 group"
                                    >
                                        <?php if ($previewUrl !== ''): ?>
                                            <img
                                                src="<?= htmlspecialchars($previewUrl) ?>"
                                                alt="<?= htmlspecialchars($item['alt_text'] ?: $item['title']) ?>"
                                                class="h-full w-full object-cover transition group-hover:scale-105"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >
                                            <div class="hidden h-full w-full items-center justify-center text-slate-400">
                                                <i data-lucide="image-off" class="h-5 w-5"></i>
                                            </div>
                                        <?php elseif ($item['type'] === 'video' && $resolvedMediaUrl !== ''): ?>
                                            <video
                                                src="<?= htmlspecialchars($resolvedMediaUrl) ?>"
                                                muted
                                                class="h-full w-full object-cover"
                                            ></video>
                                        <?php else: ?>
                                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                <i data-lucide="<?= $item['type'] === 'video' ? 'video' : 'image' ?>" class="h-5 w-5"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </td>

                                <!-- Content -->
                                <td class="px-5 py-4">
                                    <p class="max-w-xs truncate font-bold text-pingaksh-navy">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </p>
                                    <?php if (!empty($item['location'])): ?>
                                        <div class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                                            <i data-lucide="map-pin" class="h-3.5 w-3.5 text-pingaksh-terracotta"></i>
                                            <?= htmlspecialchars($item['location']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['asset_code'])): ?>
                                        <span class="mt-1 inline-block font-mono text-[11px] text-slate-400">
                                            <?= htmlspecialchars($item['asset_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Category -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-lg bg-pingaksh-sand/60 px-2.5 py-1 text-xs font-semibold text-pingaksh-navy">
                                        <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>
                                    </span>
                                </td>

                                <!-- Type -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                        <i data-lucide="<?= $item['type'] === 'video' ? 'video' : 'image' ?>" class="h-4 w-4 text-pingaksh-terracotta"></i>
                                        <?= ucfirst($item['type']) ?>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <?php if ($item['is_published']): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Published
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1.5">
                                        <a
                                            href="edit.php?id=<?= (int)$item['id'] ?>"
                                            title="Edit"
                                            class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy transition hover:border-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>

                                        <a
                                            href="delete.php?id=<?= (int)$item['id'] ?>"
                                            title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this media?')"
                                            class="rounded-lg border border-slate-200 p-2 text-red-600 transition hover:border-red-600 hover:bg-red-600 hover:text-white"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Business Verticals';
$activeMenu = 'projects';

$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR short_name LIKE ? OR slug LIKE ? OR legal_entity LIKE ?)";
    $searchValue = "%{$search}%";
    array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
}

$sql = "SELECT id, name, short_name, slug, legal_entity, display_order, is_published, updated_at FROM projects";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY display_order ASC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$success = null;
if (isset($_GET['created'])) $success = 'Project vertical created successfully.';
if (isset($_GET['updated'])) $success = 'Project vertical updated successfully.';
if (isset($_GET['deleted'])) $success = 'Project vertical deleted successfully.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">
                Infrastructure Portfolio
            </p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">
                Business Verticals (Projects)
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Manage operational plants, telemetry data, and business units.
            </p>
        </div>

        <a
            href="create.php"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-pingaksh-terracottaHover"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Vertical
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i data-lucide="circle-check" class="h-5 w-5 text-emerald-600"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-bold text-pingaksh-navy">Business Units</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    <?= count($items) ?> registered unit<?= count($items) === 1 ? '' : 's' ?>
                </p>
            </div>
        </div>

        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pingaksh-sand/50 text-pingaksh-navy">
                    <i data-lucide="folder-kanban" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 font-bold text-pingaksh-navy">No projects found</h3>
                <p class="mt-1 text-sm text-slate-500">Start by creating your first business vertical.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-pingaksh-sand/30">
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Vertical Name</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Legal Entity</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Slug / URL</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Order</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-pingaksh-navy"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($item['short_name']) ?></p>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                    <?= htmlspecialchars($item['legal_entity'] ?: 'N/A') ?>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-block rounded bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600">
                                        /our-businesses/<?= htmlspecialchars($item['slug']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center text-sm font-bold text-pingaksh-navy">
                                    <?= (int)$item['display_order'] ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ($item['is_published']): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="edit.php?id=<?= (int)$item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy transition hover:bg-pingaksh-navy hover:text-white">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        <a href="delete.php?id=<?= (int)$item['id'] ?>" onclick="return confirm('Delete this vertical? This cannot be undone.')" class="rounded-lg border border-slate-200 p-2 text-red-600 transition hover:bg-red-600 hover:text-white">
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
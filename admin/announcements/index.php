<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Corporate Announcements';
$activeMenu = 'announcements';

$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(title LIKE ? OR release_code LIKE ? OR slug LIKE ?)";
    $searchValue = "%{$search}%";
    array_push($params, $searchValue, $searchValue, $searchValue);
}

if ($category !== '') {
    $where[] = "category = ?";
    $params[] = $category;
}

$sql = "SELECT id, title, category, release_code, iso_date, image_url, is_featured, is_published FROM announcements";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY iso_date DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$success = null;
if (isset($_GET['created'])) $success = 'Announcement published successfully.';
if (isset($_GET['updated'])) $success = 'Announcement updated successfully.';
if (isset($_GET['deleted'])) $success = 'Announcement deleted successfully.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Press & Media</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Corporate Announcements</h2>
            <p class="mt-1 text-sm text-slate-600">Manage press releases, off-take pacts, and board disclosures.</p>
        </div>
        <a href="create.php" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-pingaksh-terracottaHover">
            <i data-lucide="plus" class="h-4 w-4"></i> New Dispatch
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
            <div class="md:col-span-6">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title or release code..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight py-2.5 pl-10 pr-4 text-sm outline-none focus:border-pingaksh-terracotta focus:bg-white">
                </div>
            </div>
            <div class="md:col-span-4">
                <select name="category" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta focus:bg-white">
                    <option value="">All Categories</option>
                    <option value="Hydro Power" <?= $category === 'Hydro Power' ? 'selected' : '' ?>>Hydro Power</option>
                    <option value="Bio-Energy & Fuels" <?= $category === 'Bio-Energy & Fuels' ? 'selected' : '' ?>>Bio-Energy & Fuels</option>
                    <option value="Corporate & Governance" <?= $category === 'Corporate & Governance' ? 'selected' : '' ?>>Corporate & Governance</option>
                    <option value="Sustainability & CSR" <?= $category === 'Sustainability & CSR' ? 'selected' : '' ?>>Sustainability & CSR</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="flex-1 rounded-xl bg-pingaksh-navy px-4 py-2.5 text-sm font-semibold text-pingaksh-sand hover:bg-pingaksh-navyDark">Filter</button>
                <?php if ($search || $category): ?>
                    <a href="index.php" class="flex items-center justify-center rounded-xl border border-slate-200 px-3 text-slate-500 hover:bg-slate-50"><i data-lucide="x" class="h-4 w-4"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pingaksh-sand/50 text-pingaksh-navy">
                    <i data-lucide="megaphone" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 font-bold text-pingaksh-navy">No announcements found</h3>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-pingaksh-sand/30">
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Image</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Headline & Code</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Category</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Date</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase text-pingaksh-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <?php $imgUrl = announcementsResolveUrl($item['image_url']); ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <td class="px-5 py-4">
                                    <div class="h-14 w-20 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                        <?php if ($imgUrl): ?>
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <div class="flex h-full w-full items-center justify-center text-slate-400"><i data-lucide="image" class="h-5 w-5"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="max-w-xs truncate font-bold text-pingaksh-navy" title="<?= htmlspecialchars($item['title']) ?>">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="inline-block rounded bg-pingaksh-sand px-2 py-0.5 font-mono text-[10px] font-bold text-pingaksh-navy">
                                            <?= htmlspecialchars($item['release_code'] ?: 'NO CODE') ?>
                                        </span>
                                        <?php if ($item['is_featured']): ?>
                                            <span class="text-[10px] font-bold text-pingaksh-terracotta"><i data-lucide="star" class="inline h-3 w-3"></i> Featured</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                    <?= htmlspecialchars($item['category']) ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <?= htmlspecialchars($item['iso_date'] ?? 'N/A') ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ($item['is_published']): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Live</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="edit.php?id=<?= (int)$item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                        <a href="delete.php?id=<?= (int)$item['id'] ?>" onclick="return confirm('Delete this announcement?')" class="rounded-lg border border-slate-200 p-2 text-red-600 hover:bg-red-600 hover:text-white"><i data-lucide="trash-2" class="h-4 w-4"></i></a>
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
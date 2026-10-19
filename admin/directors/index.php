<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Board of Directors';
$activeMenu = 'directors';

$search = trim($_GET['search'] ?? '');
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR designation LIKE ? OR din_number LIKE ?)";
    $searchValue = "%{$search}%";
    array_push($params, $searchValue, $searchValue, $searchValue);
}

$sql = "SELECT id, name, designation, board_category, portrait_image, display_order, is_published FROM directors";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY display_order ASC, created_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$success = null;
if (isset($_GET['created'])) $success = 'Director profile created successfully.';
if (isset($_GET['updated'])) $success = 'Director profile updated successfully.';
if (isset($_GET['deleted'])) $success = 'Director profile deleted successfully.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Leadership & Governance</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Board of Directors</h2>
            <p class="mt-1 text-sm text-slate-600">Manage Parent and Subsidiary board members.</p>
        </div>
        <a href="create.php" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-pingaksh-terracottaHover">
            <i data-lucide="plus" class="h-4 w-4"></i> Add Director
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i data-lucide="circle-check" class="h-5 w-5 text-emerald-600"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pingaksh-sand/50 text-pingaksh-navy">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 font-bold text-pingaksh-navy">No directors found</h3>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-pingaksh-sand/30">
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Portrait</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Director Details</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Board Category</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold uppercase text-pingaksh-navy">Order</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase text-pingaksh-navy">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase text-pingaksh-navy">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <?php $imgUrl = directorsResolveUrl($item['portrait_image']); ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <td class="px-5 py-4">
                                    <div class="h-14 w-14 overflow-hidden rounded-full border border-slate-200 bg-slate-100">
                                        <?php if ($imgUrl): ?>
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Portrait" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <div class="flex h-full w-full items-center justify-center text-slate-400"><i data-lucide="user" class="h-6 w-6"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-pingaksh-navy"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($item['designation']) ?></p>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                    <span class="inline-block rounded bg-pingaksh-sand/60 px-2.5 py-1 text-xs text-pingaksh-navy font-semibold">
                                        <?= htmlspecialchars($item['board_category']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center text-sm font-bold text-pingaksh-navy"><?= (int)$item['display_order'] ?></td>
                                <td class="px-5 py-4">
                                    <?php if ($item['is_published']): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Published</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="edit.php?id=<?= (int)$item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                        <a href="delete.php?id=<?= (int)$item['id'] ?>" onclick="return confirm('Delete this director?')" class="rounded-lg border border-slate-200 p-2 text-red-600 hover:bg-red-600 hover:text-white"><i data-lucide="trash-2" class="h-4 w-4"></i></a>
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
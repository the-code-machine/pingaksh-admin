<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Contact Inquiries';$activeMenu = 'contacts';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];$params = [];

if ($search !== '') {$where[] = "(full_name LIKE ? OR organization LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ?)";
    $val = "%{$search}%";
    array_push($params,$val, $val,$val, $val,$val);
}

if ($status !== '') {$where[] = "status = ?";
    $params[] =$status;
}

$sql = "SELECT * FROM contacts";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items =$stmt->fetchAll();

$success = null;
if (isset($_GET['created']))$success = 'Contact inquiry added successfully.';
if (isset($_GET['updated']))$success = 'Contact inquiry updated successfully.';
if (isset($_GET['deleted']))$success = 'Contact inquiry deleted.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Corporate Secretariat</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Contact Inquiries</h2>
            <p class="mt-1 text-sm text-slate-600">Manage institutional correspondence submitted from the website.</p>
        </div>
        <a href="create.php" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-pingaksh-terracottaHover">
            <i data-lucide="plus" class="h-4 w-4"></i> Add Inquiry
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i data-lucide="circle-check" class="h-5 w-5 text-emerald-600"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="mb-6 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 md:grid-cols-12">
            <div class="md:col-span-6">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, organization, email, phone, or subject..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta">
            </div>
            <div class="md:col-span-3">
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta">
                    <option value="">All Statuses</option>
                    <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-3">
                <button type="submit" class="flex-1 rounded-xl bg-pingaksh-navy px-4 py-2.5 text-sm font-semibold text-pingaksh-sand hover:bg-pingaksh-navyDark">Filter</button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="index.php" class="flex items-center justify-center rounded-xl border border-slate-200 px-3 text-slate-500 hover:bg-slate-50"><i data-lucide="x" class="h-4 w-4"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center text-slate-500">No contact inquiries found.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">
                            <th class="px-5 py-3.5">Sender & Organization</th>
                            <th class="px-5 py-3.5">Contact Info</th>
                            <th class="px-5 py-3.5">Subject & Message</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as$item): ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-pingaksh-navy"><?= htmlspecialchars($item['full_name']) ?></p>
                                    <p class="text-xs font-semibold text-pingaksh-terracotta"><?= htmlspecialchars($item['organization']) ?></p>
                                    <p class="mt-1 text-[11px] text-slate-400"><?= date('M d, Y H:i', strtotime($item['created_at'])) ?></p>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-600">
                                    <p class="font-medium text-pingaksh-navy"><?= htmlspecialchars($item['email']) ?></p>
                                    <p class="mt-0.5"><?= htmlspecialchars($item['phone']) ?></p>
                                </td>
                                <td class="px-5 py-4 max-w-md">
                                    <p class="text-xs font-bold text-pingaksh-navy"><?= htmlspecialchars($item['subject']) ?></p>
                                    <p class="mt-1 text-xs text-slate-600 line-clamp-2"><?= htmlspecialchars($item['message']) ?></p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-pingaksh-sand px-3 py-1 text-[11px] font-bold uppercase text-pingaksh-navy">
                                        <?= htmlspecialchars(str_replace('_', ' ', $item['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="edit.php?id=<?= (int)$item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                        <a href="delete.php?id=<?= (int)$item['id'] ?>" onclick="return confirm('Delete this inquiry permanently?')" class="rounded-lg border border-slate-200 p-2 text-red-600 hover:bg-red-600 hover:text-white"><i data-lucide="trash-2" class="h-4 w-4"></i></a>
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
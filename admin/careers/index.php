<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Career Applications';
$activeMenu = 'careers';

$statusFilter = $_GET['status'] ?? 'ALL';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM career_applications WHERE 1=1";
$params = [];

if (in_array($statusFilter, ['New', 'Shortlisted', 'Reviewed', 'Rejected'], true)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR position LIKE ? OR preferred_location LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flashSuccess = $_GET['success'] ?? null;
$flashError = $_GET['error'] ?? null;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl pb-20 space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">HR & Recruitment</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Career Applications</h2>
            <p class="mt-1 text-sm text-slate-600">Review candidate applications, download resumes, and update shortlisting status.</p>
        </div>
        <a href="create.php" class="inline-flex items-center gap-1.5 rounded-xl bg-pingaksh-terracotta px-4 py-2.5 text-xs font-bold text-white hover:bg-pingaksh-terracottaHover">
            <i data-lucide="plus" class="h-4 w-4"></i> Add Application Manually
        </a>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap gap-2">
            <?php foreach (['ALL', 'New', 'Shortlisted', 'Reviewed', 'Rejected'] as $st): ?>
                <a href="?status=<?= urlencode($st) ?>&search=<?= urlencode($search) ?>"
                   class="rounded-lg px-3 py-1.5 text-xs font-bold <?= $statusFilter === $st ? 'bg-pingaksh-navy text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    <?= $st === 'ALL' ? 'All' : $st ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex gap-2">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, position..." class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs w-60">
            <button type="submit" class="rounded-xl bg-pingaksh-navy px-4 py-1.5 text-xs font-bold text-white cursor-pointer">Search</button>
        </div>
    </form>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase text-slate-500">
                        <th class="py-3 px-4">Candidate</th>
                        <th class="py-3 px-4">Position & Location</th>
                        <th class="py-3 px-4">Experience</th>
                        <th class="py-3 px-4">Resume / Links</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($applications)): ?>
                        <tr><td colspan="6" class="py-10 text-center text-sm text-slate-500">No career applications found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($applications as $app):
                        $badge = match ($app['status']) {
                            'Shortlisted' => 'bg-emerald-100 text-emerald-800',
                            'Reviewed'    => 'bg-blue-100 text-blue-800',
                            'Rejected'    => 'bg-red-100 text-red-700',
                            default       => 'bg-amber-100 text-amber-800',
                        };
                    ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-pingaksh-navy"><?= htmlspecialchars($app['full_name']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($app['email']) ?> · <?= htmlspecialchars($app['contact_number']) ?></div>
                                <div class="text-[11px] font-mono text-slate-400 mt-0.5"><?= date('d M Y, h:i A', strtotime($app['created_at'])) ?></div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-pingaksh-navy"><?= htmlspecialchars($app['position']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($app['preferred_location']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold whitespace-nowrap"><?= htmlspecialchars($app['experience_level']) ?></td>
                            <td class="py-3.5 px-4 text-xs space-y-1">
                                <div>
                                    <a href="<?= htmlspecialchars($app['resume_path']) ?>" target="_blank" class="font-semibold text-pingaksh-terracotta underline">Download Resume</a>
                                </div>
                                <?php if (!empty($app['portfolio_url'])): ?>
                                    <div>
                                        <a href="<?= htmlspecialchars($app['portfolio_url']) ?>" target="_blank" class="text-slate-600 underline">Portfolio / LinkedIn</a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $badge ?>"><?= htmlspecialchars($app['status']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                                <a href="edit.php?id=<?= (int)$app['id'] ?>" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-pingaksh-navy hover:bg-slate-100">Edit</a>
                                <a href="delete.php?id=<?= (int)$app['id'] ?>" onclick="return confirm('Delete this application and its resume?')" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
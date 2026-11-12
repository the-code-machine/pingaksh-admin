<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin', 'carbon_editor']); // Both roles allowed
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Carbon Telemetry Matrix';
$activeMenu = 'carbon';

$stmt = $pdo->query("SELECT * FROM carbon_metrics ORDER BY created_at DESC");
$items = $stmt->fetchAll();

$success = $_GET['success'] ?? null;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Real-Time ESG Engine</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Carbon Telemetry Matrix</h2>
        </div>
        <a href="create.php" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white hover:bg-pingaksh-terracottaHover">
            <i data-lucide="plus" class="h-4 w-4"></i> Add Metric Tracker
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-pingaksh-navy">
                <tr>
                    <th class="px-5 py-3.5">Metric & Asset</th>
                    <th class="px-5 py-3.5">Base Value & Timestamp</th>
                    <th class="px-5 py-3.5">Increment Rate (Per Sec)</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-pingaksh-sandLight transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-bold text-pingaksh-navy"><?= htmlspecialchars($item['metric_name']) ?></p>
                            <p class="text-[11px] font-mono font-semibold text-pingaksh-terracotta mt-0.5"><?= htmlspecialchars($item['asset_tag']) ?></p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold"><?= number_format($item['base_value'], 2) ?> <?= htmlspecialchars($item['unit']) ?></p>
                            <p class="text-xs text-slate-500 mt-0.5">Since: <?= $item['base_timestamp'] ?></p>
                        </td>
                        <td class="px-5 py-4 font-mono text-emerald-600 font-bold">
                            +<?= number_format($item['rate_per_second'], 6) ?> / sec
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase <?= $item['status'] === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $item['status'] ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-1.5">
                                <a href="edit.php?id=<?= $item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                <a href="delete.php?id=<?= $item['id'] ?>" onclick="return confirm('Delete this metric?')" class="rounded-lg border border-slate-200 p-2 text-red-600 hover:bg-red-600 hover:text-white"><i data-lucide="trash-2" class="h-4 w-4"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
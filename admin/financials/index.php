<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Financial Reports & Annual Returns';
$activeMenu = 'financials';

$selectedFy = $_GET['fy'] ?? 'ALL';
$validFys = ['FY 2026-27', 'FY 2025-26', 'FY 2024-25'];

// Fetch Annual Reports
if (in_array($selectedFy, $validFys, true)) {
    $stmt = $pdo->prepare("SELECT * FROM financial_annual_reports WHERE fiscal_year = ? ORDER BY iso_date DESC");
    $stmt->execute([$selectedFy]);
    $annualReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $annualReports = $pdo->query("SELECT * FROM financial_annual_reports ORDER BY iso_date DESC")->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Quarterly Packs + Documents
if (in_array($selectedFy, $validFys, true)) {
    $stmtQ = $pdo->prepare("SELECT * FROM financial_quarterly_packs WHERE fiscal_year = ? ORDER BY iso_date DESC, FIELD(quarter, 'Q4','Q3','Q2','Q1')");
    $stmtQ->execute([$selectedFy]);
    $quarterlyPacks = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
} else {
    $quarterlyPacks = $pdo->query("SELECT * FROM financial_quarterly_packs ORDER BY iso_date DESC, FIELD(quarter, 'Q4','Q3','Q2','Q1')")->fetchAll(PDO::FETCH_ASSOC);
}

$docsByPack = [];
if (!empty($quarterlyPacks)) {
    $packIds = array_column($quarterlyPacks, 'id');
    $placeholders = implode(',', array_fill(0, count($packIds), '?'));
    $stmtDocs = $pdo->prepare("SELECT * FROM financial_quarterly_documents WHERE quarter_pack_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
    $stmtDocs->execute($packIds);
    foreach ($stmtDocs->fetchAll(PDO::FETCH_ASSOC) as $doc) {
        $docsByPack[$doc['quarter_pack_id']][] = $doc;
    }
}

// Fetch Subsidiary Financials
$subsidiaries = $pdo->query("SELECT * FROM financial_subsidiaries ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$flashSuccess = $_GET['success'] ?? null;
$flashError = $_GET['error'] ?? null;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl pb-20 space-y-10">
    <!-- Top Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Investor Relations CMS</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Financial Reports & Annual Returns</h2>
            <p class="mt-1 text-sm text-slate-600">Manage Annual Reports, Q1–Q4 Quarterly Packs & PDFs, and Subsidiary Financial Statements.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/api/financial-reports.php" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-pingaksh-navy hover:bg-slate-50">
                <i data-lucide="code-2" class="h-4 w-4 text-pingaksh-terracotta"></i> JSON API
            </a>
            <a href="create.php?type=annual" class="inline-flex items-center gap-1.5 rounded-xl bg-pingaksh-navy px-4 py-2.5 text-xs font-bold text-pingaksh-sand hover:bg-navy-800">
                <i data-lucide="plus" class="h-4 w-4"></i> Add Annual Report
            </a>
            <a href="create.php?type=quarterly" class="inline-flex items-center gap-1.5 rounded-xl bg-pingaksh-terracotta px-4 py-2.5 text-xs font-bold text-white hover:bg-pingaksh-terracottaHover">
                <i data-lucide="plus" class="h-4 w-4"></i> Add Quarterly Pack
            </a>
            <a href="create.php?type=subsidiary" class="inline-flex items-center gap-1.5 rounded-xl border border-pingaksh-navy/20 bg-amber-50 px-4 py-2.5 text-xs font-bold text-pingaksh-navy hover:bg-amber-100">
                <i data-lucide="plus" class="h-4 w-4"></i> Add Subsidiary
            </a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <?= htmlspecialchars($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <?= htmlspecialchars($flashError) ?>
        </div>
    <?php endif; ?>

    <!-- Fiscal Year Filter -->
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-2">Fiscal Year:</span>
        <a href="?fy=ALL" class="rounded-lg px-3.5 py-1.5 text-xs font-bold <?= $selectedFy === 'ALL' ? 'bg-pingaksh-navy text-white' : 'bg-white border border-slate-200 text-slate-700' ?>">All Years</a>
        <?php foreach ($validFys as $fy): ?>
            <a href="?fy=<?= urlencode($fy) ?>" class="rounded-lg px-3.5 py-1.5 text-xs font-bold <?= $selectedFy === $fy ? 'bg-pingaksh-navy text-white' : 'bg-white border border-slate-200 text-slate-700' ?>">
                <?= htmlspecialchars($fy) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- 1. ANNUAL REPORTS -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wider text-pingaksh-navy">Annual Reports & Highlights</h3>
            <span class="text-xs font-mono text-slate-500"><?= count($annualReports) ?> records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                        <th class="py-3 px-4">Fiscal Year</th>
                        <th class="py-3 px-4">Theme & Subtitle</th>
                        <th class="py-3 px-4">Release Date</th>
                        <th class="py-3 px-4">Docs / Size</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($annualReports as $ar): ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3.5 px-4 font-mono font-bold text-pingaksh-terracotta whitespace-nowrap"><?= htmlspecialchars($ar['fiscal_year']) ?></td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-pingaksh-navy"><?= htmlspecialchars($ar['theme_title']) ?></div>
                                <div class="text-xs text-slate-500 line-clamp-1"><?= htmlspecialchars($ar['subtitle']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-mono whitespace-nowrap"><?= htmlspecialchars($ar['release_date']) ?></td>
                            <td class="py-3.5 px-4 text-xs font-mono whitespace-nowrap"><?= htmlspecialchars($ar['page_count']) ?> · <?= htmlspecialchars($ar['file_size']) ?></td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                                <a href="edit.php?type=annual&id=<?= (int)$ar['id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-pingaksh-navy hover:bg-slate-100">Edit</a>
                                <a href="delete.php?type=annual&id=<?= (int)$ar['id'] ?>" onclick="return confirm('Delete this Annual Report?')" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. QUARTERLY PACKS -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wider text-pingaksh-navy">Quarterly Results Matrix (Q1–Q4)</h3>
            <span class="text-xs font-mono text-slate-500"><?= count($quarterlyPacks) ?> quarters</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                        <th class="py-3 px-4">FY & Quarter</th>
                        <th class="py-3 px-4">Period & Highlights</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Attached Docs</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($quarterlyPacks as $qp):
                        $qDocs = $docsByPack[$qp['id']] ?? [];
                    ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3.5 px-4 font-mono font-bold text-pingaksh-navy whitespace-nowrap">
                                <?= htmlspecialchars($qp['fiscal_year']) ?> · <span class="text-pingaksh-terracotta"><?= htmlspecialchars($qp['quarter']) ?></span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-pingaksh-navy"><?= htmlspecialchars($qp['period_ended']) ?></div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    <?= htmlspecialchars($qp['highlight_1_label']) ?>: <strong><?= htmlspecialchars($qp['highlight_1_value']) ?></strong> ·
                                    <?= htmlspecialchars($qp['highlight_2_label']) ?>: <strong><?= htmlspecialchars($qp['highlight_2_value']) ?></strong>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $qp['status'] === 'Published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                    <?= htmlspecialchars($qp['status']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-mono"><?= count($qDocs) ?> file(s)</td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                                <a href="edit.php?type=quarterly&id=<?= (int)$qp['id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-pingaksh-navy hover:bg-slate-100">Edit & Docs</a>
                                <a href="delete.php?type=quarterly&id=<?= (int)$qp['id'] ?>" onclick="return confirm('Delete this Quarterly Pack and its documents?')" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. SUBSIDIARY FINANCIALS -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wider text-pingaksh-navy">Subsidiary & SPV Financials</h3>
            <span class="text-xs font-mono text-slate-500"><?= count($subsidiaries) ?> entities</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                        <th class="py-3 px-4">Entity & Role</th>
                        <th class="py-3 px-4">Audit Status</th>
                        <th class="py-3 px-4">Report & File</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($subsidiaries as $sub): ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-pingaksh-navy"><?= htmlspecialchars($sub['entity_name']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($sub['spv_role']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-slate-700"><?= htmlspecialchars($sub['latest_audit_status']) ?></td>
                            <td class="py-3.5 px-4 text-xs">
                                <a href="<?= htmlspecialchars($sub['href']) ?>" target="_blank" class="font-semibold text-pingaksh-terracotta underline"><?= htmlspecialchars($sub['report_title']) ?></a>
                                <span class="font-mono text-slate-500 ml-1">(<?= htmlspecialchars($sub['file_size']) ?>)</span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                                <a href="edit.php?type=subsidiary&id=<?= (int)$sub['id'] ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-pingaksh-navy hover:bg-slate-100">Edit</a>
                                <a href="delete.php?type=subsidiary&id=<?= (int)$sub['id'] ?>" onclick="return confirm('Delete this Subsidiary Financial record?')" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
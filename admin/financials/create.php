<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/upload_helper.php';

$pageTitle = 'Create Financial Record';
$activeMenu = 'financials';

$type = $_GET['type'] ?? ($_POST['record_type'] ?? 'quarterly');
if (!in_array($type, ['annual', 'quarterly', 'subsidiary'], true)) {
    $type = 'quarterly';
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($type === 'annual') {
            $fy = trim($_POST['fiscal_year'] ?? 'FY 2026-27');
            $themeTitle = trim($_POST['theme_title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $releaseDate = trim($_POST['release_date'] ?? '');
            $isoDate = trim($_POST['iso_date'] ?? date('Y-m-d'));
            $pageCount = trim($_POST['page_count'] ?? '100 Pages');
            $fileSize = trim($_POST['file_size'] ?? '4.0 MB PDF');

            $coverUp = handleFinancialUpload('cover_file', ['jpg', 'jpeg', 'png', 'webp'], trim($_POST['cover_image'] ?? ''));
            $reportUp = handleFinancialUpload('full_report_file', ['pdf'], trim($_POST['full_report_href'] ?? ''));
            $mgt7Up = handleFinancialUpload('mgt7_file', ['pdf'], trim($_POST['mgt7_return_href'] ?? ''));
            $standUp = handleFinancialUpload('standalone_file', ['pdf'], trim($_POST['standalone_accounts_href'] ?? ''));

            if ($themeTitle === '' || $reportUp['href'] === '') {
                throw new RuntimeException('Theme title and Full Report PDF/URL are required.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO financial_annual_reports (
                    fiscal_year, theme_title, subtitle, release_date, iso_date,
                    cover_image, cover_alt, page_count, file_size,
                    full_report_href, mgt7_return_href, standalone_accounts_href,
                    kpi_1_metric, kpi_1_value, kpi_1_growth, kpi_1_bar,
                    kpi_2_metric, kpi_2_value, kpi_2_growth, kpi_2_bar,
                    kpi_3_metric, kpi_3_value, kpi_3_growth, kpi_3_bar
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    theme_title = VALUES(theme_title),
                    subtitle = VALUES(subtitle),
                    release_date = VALUES(release_date),
                    iso_date = VALUES(iso_date),
                    cover_image = VALUES(cover_image),
                    page_count = VALUES(page_count),
                    file_size = VALUES(file_size),
                    full_report_href = VALUES(full_report_href),
                    mgt7_return_href = VALUES(mgt7_return_href),
                    standalone_accounts_href = VALUES(standalone_accounts_href),
                    kpi_1_metric = VALUES(kpi_1_metric), kpi_1_value = VALUES(kpi_1_value), kpi_1_growth = VALUES(kpi_1_growth), kpi_1_bar = VALUES(kpi_1_bar),
                    kpi_2_metric = VALUES(kpi_2_metric), kpi_2_value = VALUES(kpi_2_value), kpi_2_growth = VALUES(kpi_2_growth), kpi_2_bar = VALUES(kpi_2_bar),
                    kpi_3_metric = VALUES(kpi_3_metric), kpi_3_value = VALUES(kpi_3_value), kpi_3_growth = VALUES(kpi_3_growth), kpi_3_bar = VALUES(kpi_3_bar)
            ");

            $stmt->execute([
                $fy, $themeTitle, $subtitle, $releaseDate, $isoDate,
                $coverUp['href'], $themeTitle . ' Cover', $pageCount, $reportUp['file_size'] ?? $fileSize,
                $reportUp['href'], $mgt7Up['href'], $standUp['href'],
                trim($_POST['kpi_1_metric'] ?? ''), trim($_POST['kpi_1_value'] ?? ''), trim($_POST['kpi_1_growth'] ?? ''), (int)($_POST['kpi_1_bar'] ?? 90),
                trim($_POST['kpi_2_metric'] ?? ''), trim($_POST['kpi_2_value'] ?? ''), trim($_POST['kpi_2_growth'] ?? ''), (int)($_POST['kpi_2_bar'] ?? 90),
                trim($_POST['kpi_3_metric'] ?? ''), trim($_POST['kpi_3_value'] ?? ''), trim($_POST['kpi_3_growth'] ?? ''), (int)($_POST['kpi_3_bar'] ?? 80)
            ]);

            header('Location: index.php?success=' . urlencode("Saved Annual Report for {$fy}."));
            exit;
        }

        if ($type === 'quarterly') {
            $fy = trim($_POST['fiscal_year'] ?? 'FY 2026-27');
            $quarter = in_array($_POST['quarter'] ?? 'Q1', ['Q1', 'Q2', 'Q3', 'Q4'], true) ? $_POST['quarter'] : 'Q1';
            $periodEnded = trim($_POST['period_ended'] ?? '');
            $releaseDate = trim($_POST['release_date'] ?? '');
            $isoDate = trim($_POST['iso_date'] ?? date('Y-m-d'));
            $status = ($_POST['status'] ?? 'Published') === 'Upcoming' ? 'Upcoming' : 'Published';

            $stmtPack = $pdo->prepare("
                INSERT INTO financial_quarterly_packs (
                    fiscal_year, quarter, period_ended, release_date, iso_date, status,
                    highlight_1_label, highlight_1_value, highlight_2_label, highlight_2_value
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    period_ended = VALUES(period_ended),
                    release_date = VALUES(release_date),
                    iso_date = VALUES(iso_date),
                    status = VALUES(status),
                    highlight_1_label = VALUES(highlight_1_label),
                    highlight_1_value = VALUES(highlight_1_value),
                    highlight_2_label = VALUES(highlight_2_label),
                    highlight_2_value = VALUES(highlight_2_value)
            ");
            $stmtPack->execute([
                $fy, $quarter, $periodEnded, $releaseDate, $isoDate, $status,
                trim($_POST['h1_label'] ?? ''), trim($_POST['h1_value'] ?? ''),
                trim($_POST['h2_label'] ?? ''), trim($_POST['h2_value'] ?? '')
            ]);

            $packId = (int)$pdo->lastInsertId();
            if ($packId === 0) {
                $qIdStmt = $pdo->prepare("SELECT id FROM financial_quarterly_packs WHERE fiscal_year = ? AND quarter = ?");
                $qIdStmt->execute([$fy, $quarter]);
                $packId = (int)$qIdStmt->fetchColumn();
            }

            $docTitle = trim($_POST['doc_title'] ?? '');
            $docType = $_POST['doc_type'] ?? 'Financial Statement';
            $docUpload = handleFinancialUpload('doc_file', ['pdf'], trim($_POST['doc_href'] ?? ''));

            if ($docTitle !== '' && $docUpload['href'] !== '') {
                $stmtDoc = $pdo->prepare("INSERT INTO financial_quarterly_documents (quarter_pack_id, doc_type, title, file_size, href, sort_order) VALUES (?, ?, ?, ?, ?, 1)");
                $stmtDoc->execute([
                    $packId,
                    $docType,
                    $docTitle,
                    $docUpload['file_size'] ?? trim($_POST['doc_file_size'] ?? '1.5 MB'),
                    $docUpload['href']
                ]);
            }

            header('Location: index.php?success=' . urlencode("Saved Quarterly Pack {$fy} {$quarter}."));
            exit;
        }

        if ($type === 'subsidiary') {
            $entityName = trim($_POST['entity_name'] ?? '');
            $spvRole = trim($_POST['spv_role'] ?? '');
            $auditStatus = trim($_POST['latest_audit_status'] ?? '');
            $reportTitle = trim($_POST['report_title'] ?? '');
            $subUpload = handleFinancialUpload('sub_file', ['pdf'], trim($_POST['href'] ?? ''));

            if ($entityName === '' || $reportTitle === '' || $subUpload['href'] === '') {
                throw new RuntimeException('Entity name, report title, and PDF/URL are required.');
            }

            $stmtSub = $pdo->prepare("
                INSERT INTO financial_subsidiaries (entity_name, spv_role, latest_audit_status, report_title, file_size, href, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtSub->execute([
                $entityName,
                $spvRole,
                $auditStatus,
                $reportTitle,
                $subUpload['file_size'] ?? trim($_POST['file_size'] ?? 'PDF · 2.5 MB'),
                $subUpload['href'],
                (int)($_POST['sort_order'] ?? 1)
            ]);

            header('Location: index.php?success=' . urlencode("Added Subsidiary Financial record."));
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-4xl pb-20">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Investor Relations CMS</p>
            <h2 class="text-2xl font-bold text-pingaksh-navy">Create Financial Record</h2>
        </div>
        <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">← Back to List</a>
    </div>

    <!-- Type Switcher -->
    <div class="mb-6 flex gap-2">
        <a href="?type=annual" class="rounded-xl px-4 py-2 text-xs font-bold <?= $type === 'annual' ? 'bg-pingaksh-navy text-white' : 'bg-white border border-slate-200 text-slate-700' ?>">Annual Report</a>
        <a href="?type=quarterly" class="rounded-xl px-4 py-2 text-xs font-bold <?= $type === 'quarterly' ? 'bg-pingaksh-navy text-white' : 'bg-white border border-slate-200 text-slate-700' ?>">Quarterly Pack</a>
        <a href="?type=subsidiary" class="rounded-xl px-4 py-2 text-xs font-bold <?= $type === 'subsidiary' ? 'bg-pingaksh-navy text-white' : 'bg-white border border-slate-200 text-slate-700' ?>">Subsidiary Financial</a>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
        <input type="hidden" name="record_type" value="<?= htmlspecialchars($type) ?>">

        <?php if ($type === 'annual'): ?>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Fiscal Year</label>
                    <select name="fiscal_year" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="FY 2026-27">FY 2026-27</option>
                        <option value="FY 2025-26">FY 2025-26</option>
                        <option value="FY 2024-25">FY 2024-25</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Release Date Label</label>
                    <input type="text" name="release_date" placeholder="15 October 2026" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ISO Date</label>
                    <input type="date" name="iso_date" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Theme Title *</label>
                <input type="text" name="theme_title" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Subtitle / Summary</label>
                <textarea name="subtitle" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Page Count</label>
                    <input type="text" name="page_count" value="120 Pages" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Cover Image Upload (JPG/PNG/WEBP)</label>
                    <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="cover_image" placeholder="Or paste Cover Image URL" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3 border-t border-slate-100 pt-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Report PDF *</label>
                    <input type="file" name="full_report_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="full_report_href" placeholder="Or /uploads/investors/..." class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Form MGT-7 PDF</label>
                    <input type="file" name="mgt7_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="mgt7_return_href" placeholder="Or /uploads/investors/..." class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Standalone Accounts PDF</label>
                    <input type="file" name="standalone_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="standalone_accounts_href" placeholder="Or /uploads/investors/..." class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Key Performance Metrics (3 Rows)</h4>
                <?php for ($k = 1; $k <= 3; $k++): ?>
                    <div class="grid gap-2 sm:grid-cols-4">
                        <input type="text" name="kpi_<?= $k ?>_metric" placeholder="Metric Label" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <input type="text" name="kpi_<?= $k ?>_value" placeholder="Value (e.g. 99.2%)" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <input type="text" name="kpi_<?= $k ?>_growth" placeholder="Growth (e.g. +1.4% YoY)" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <input type="number" min="0" max="100" name="kpi_<?= $k ?>_bar" value="90" placeholder="Bar %" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <?php if ($type === 'quarterly'): ?>
            <div class="grid gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Fiscal Year</label>
                    <select name="fiscal_year" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="FY 2026-27">FY 2026-27</option>
                        <option value="FY 2025-26">FY 2025-26</option>
                        <option value="FY 2024-25">FY 2024-25</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Quarter</label>
                    <select name="quarter" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Release Date</label>
                    <input type="text" name="release_date" placeholder="15 Oct, 2026" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="Published">Published</option>
                        <option value="Upcoming">Upcoming</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Period Ended</label>
                    <input type="text" name="period_ended" placeholder="Quarter Ended Sep 30, 2026" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ISO Date</label>
                    <input type="date" name="iso_date" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 border-t border-slate-100 pt-4">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="h1_label" placeholder="Highlight 1 Label" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="h1_value" placeholder="Highlight 1 Value" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="h2_label" placeholder="Highlight 2 Label" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="h2_value" placeholder="Highlight 2 Value" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Attach Initial Document (Optional — more can be added in Edit)</h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Document Type</label>
                        <select name="doc_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="Financial Statement">Financial Statement</option>
                            <option value="Investor Presentation">Investor Presentation</option>
                            <option value="Press Release">Press Release</option>
                            <option value="Auditor Review">Auditor Review</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Document Title</label>
                        <input type="text" name="doc_title" placeholder="Unaudited Consolidated Financial Results – Q2 FY27" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Upload PDF</label>
                        <input type="file" name="doc_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Or Document Link / Path</label>
                        <input type="text" name="doc_href" placeholder="/uploads/investors/..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'subsidiary'): ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Entity Name *</label>
                    <input type="text" name="entity_name" required placeholder="Uttar Bharat Hydro Power Pvt. Ltd. (UBHP)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Audit Status</label>
                    <input type="text" name="latest_audit_status" placeholder="FY 2025–26 Audited Balance Sheet & P&L" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">SPV Role</label>
                <input type="text" name="spv_role" placeholder="Hydroelectric Generation SPV (Sarju I, II & III — 23.100 MW)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Report Title *</label>
                <input type="text" name="report_title" required placeholder="UBHP Standalone Audited Financial Statements" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Upload PDF *</label>
                    <input type="file" name="sub_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Or PDF Path / URL</label>
                    <input type="text" name="href" placeholder="/uploads/investors/..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-4 flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 px-5 py-2.5 text-xs font-bold text-slate-600">Cancel</a>
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-xs font-bold text-white hover:bg-pingaksh-terracottaHover cursor-pointer">Save Record</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
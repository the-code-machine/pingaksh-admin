<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/upload_helper.php';

$pageTitle = 'Edit Financial Record';
$activeMenu = 'financials';

$type = $_GET['type'] ?? ($_POST['record_type'] ?? 'annual');
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

if (!in_array($type, ['annual', 'quarterly', 'subsidiary'], true) || $id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid record ID.'));
    exit;
}

$error = null;
$success = null;

// Delete a single document inside a Quarterly Pack
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_doc') {
    $docId = (int)$_POST['doc_id'];
    $pdo->prepare("DELETE FROM financial_quarterly_documents WHERE id = ? AND quarter_pack_id = ?")->execute([$docId, $id]);
    $success = 'Document removed.';
}

// Add a new document to a Quarterly Pack
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_doc' && $type === 'quarterly') {
    try {
        $docTitle = trim($_POST['new_doc_title'] ?? '');
        $docType = $_POST['new_doc_type'] ?? 'Financial Statement';
        $docUpload = handleFinancialUpload('new_doc_file', ['pdf'], trim($_POST['new_doc_href'] ?? ''));
        if ($docTitle === '' || $docUpload['href'] === '') {
            throw new RuntimeException('Document title and PDF/URL are required.');
        }
        $stmt = $pdo->prepare("INSERT INTO financial_quarterly_documents (quarter_pack_id, doc_type, title, file_size, href, sort_order) VALUES (?, ?, ?, ?, ?, 10)");
        $stmt->execute([$id, $docType, $docTitle, $docUpload['file_size'] ?? '1.5 MB', $docUpload['href']]);
        $success = 'Document added to quarterly pack.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Main Update Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_record') {
    try {
        if ($type === 'annual') {
            $curr = $pdo->prepare("SELECT * FROM financial_annual_reports WHERE id = ?");
            $curr->execute([$id]);
            $existing = $curr->fetch(PDO::FETCH_ASSOC);

            $coverUp = handleFinancialUpload('cover_file', ['jpg', 'jpeg', 'png', 'webp'], trim($_POST['cover_image'] ?? $existing['cover_image']));
            $reportUp = handleFinancialUpload('full_report_file', ['pdf'], trim($_POST['full_report_href'] ?? $existing['full_report_href']));
            $mgt7Up = handleFinancialUpload('mgt7_file', ['pdf'], trim($_POST['mgt7_return_href'] ?? $existing['mgt7_return_href']));
            $standUp = handleFinancialUpload('standalone_file', ['pdf'], trim($_POST['standalone_accounts_href'] ?? $existing['standalone_accounts_href']));

            $stmt = $pdo->prepare("
                UPDATE financial_annual_reports SET
                    theme_title = ?, subtitle = ?, release_date = ?, iso_date = ?,
                    cover_image = ?, page_count = ?, file_size = ?,
                    full_report_href = ?, mgt7_return_href = ?, standalone_accounts_href = ?,
                    kpi_1_metric = ?, kpi_1_value = ?, kpi_1_growth = ?, kpi_1_bar = ?,
                    kpi_2_metric = ?, kpi_2_value = ?, kpi_2_growth = ?, kpi_2_bar = ?,
                    kpi_3_metric = ?, kpi_3_value = ?, kpi_3_growth = ?, kpi_3_bar = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($_POST['theme_title'] ?? ''), trim($_POST['subtitle'] ?? ''),
                trim($_POST['release_date'] ?? ''), trim($_POST['iso_date'] ?? date('Y-m-d')),
                $coverUp['href'], trim($_POST['page_count'] ?? ''), $reportUp['file_size'] ?? trim($_POST['file_size'] ?? $existing['file_size']),
                $reportUp['href'], $mgt7Up['href'], $standUp['href'],
                trim($_POST['kpi_1_metric'] ?? ''), trim($_POST['kpi_1_value'] ?? ''), trim($_POST['kpi_1_growth'] ?? ''), (int)($_POST['kpi_1_bar'] ?? 90),
                trim($_POST['kpi_2_metric'] ?? ''), trim($_POST['kpi_2_value'] ?? ''), trim($_POST['kpi_2_growth'] ?? ''), (int)($_POST['kpi_2_bar'] ?? 90),
                trim($_POST['kpi_3_metric'] ?? ''), trim($_POST['kpi_3_value'] ?? ''), trim($_POST['kpi_3_growth'] ?? ''), (int)($_POST['kpi_3_bar'] ?? 80),
                $id
            ]);
            $success = 'Annual Report updated.';
        }

        if ($type === 'quarterly') {
            $stmt = $pdo->prepare("
                UPDATE financial_quarterly_packs SET
                    period_ended = ?, release_date = ?, iso_date = ?, status = ?,
                    highlight_1_label = ?, highlight_1_value = ?,
                    highlight_2_label = ?, highlight_2_value = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($_POST['period_ended'] ?? ''), trim($_POST['release_date'] ?? ''),
                trim($_POST['iso_date'] ?? date('Y-m-d')), ($_POST['status'] ?? 'Published') === 'Upcoming' ? 'Upcoming' : 'Published',
                trim($_POST['h1_label'] ?? ''), trim($_POST['h1_value'] ?? ''),
                trim($_POST['h2_label'] ?? ''), trim($_POST['h2_value'] ?? ''),
                $id
            ]);
            $success = 'Quarterly Pack updated.';
        }

        if ($type === 'subsidiary') {
            $curr = $pdo->prepare("SELECT * FROM financial_subsidiaries WHERE id = ?");
            $curr->execute([$id]);
            $existing = $curr->fetch(PDO::FETCH_ASSOC);

            $subUpload = handleFinancialUpload('sub_file', ['pdf'], trim($_POST['href'] ?? $existing['href']));
            $stmt = $pdo->prepare("
                UPDATE financial_subsidiaries SET
                    entity_name = ?, spv_role = ?, latest_audit_status = ?,
                    report_title = ?, file_size = ?, href = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($_POST['entity_name'] ?? ''), trim($_POST['spv_role'] ?? ''),
                trim($_POST['latest_audit_status'] ?? ''), trim($_POST['report_title'] ?? ''),
                $subUpload['file_size'] ?? trim($_POST['file_size'] ?? $existing['file_size']), $subUpload['href'],
                $id
            ]);
            $success = 'Subsidiary Financial updated.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Reload current record
$record = null;
$packDocs = [];

if ($type === 'annual') {
    $stmt = $pdo->prepare("SELECT * FROM financial_annual_reports WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($type === 'quarterly') {
    $stmt = $pdo->prepare("SELECT * FROM financial_quarterly_packs WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    $dStmt = $pdo->prepare("SELECT * FROM financial_quarterly_documents WHERE quarter_pack_id = ? ORDER BY sort_order ASC, id ASC");
    $dStmt->execute([$id]);
    $packDocs = $dStmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($type === 'subsidiary') {
    $stmt = $pdo->prepare("SELECT * FROM financial_subsidiaries WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$record) {
    header('Location: index.php?error=' . urlencode('Record not found.'));
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-4xl pb-20 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Investor Relations CMS</p>
            <h2 class="text-2xl font-bold text-pingaksh-navy">Edit <?= htmlspecialchars(ucfirst($type)) ?> Record</h2>
        </div>
        <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">← Back to List</a>
    </div>

    <?php if ($success): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
        <input type="hidden" name="action" value="update_record">
        <input type="hidden" name="record_type" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <?php if ($type === 'annual'): ?>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Fiscal Year</label>
                    <input type="text" disabled value="<?= htmlspecialchars($record['fiscal_year']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Release Date</label>
                    <input type="text" name="release_date" value="<?= htmlspecialchars($record['release_date']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ISO Date</label>
                    <input type="date" name="iso_date" value="<?= htmlspecialchars($record['iso_date']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Theme Title</label>
                <input type="text" name="theme_title" value="<?= htmlspecialchars($record['theme_title']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Subtitle</label>
                <textarea name="subtitle" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"><?= htmlspecialchars($record['subtitle']) ?></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Page Count</label>
                    <input type="text" name="page_count" value="<?= htmlspecialchars($record['page_count']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">File Size</label>
                    <input type="text" name="file_size" value="<?= htmlspecialchars($record['file_size']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Replace Cover Image</label>
                    <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="cover_image" value="<?= htmlspecialchars($record['cover_image']) ?>" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3 border-t border-slate-100 pt-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Report PDF</label>
                    <input type="file" name="full_report_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="full_report_href" value="<?= htmlspecialchars($record['full_report_href']) ?>" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Form MGT-7 PDF</label>
                    <input type="file" name="mgt7_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="mgt7_return_href" value="<?= htmlspecialchars($record['mgt7_return_href']) ?>" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Standalone Accounts PDF</label>
                    <input type="file" name="standalone_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="standalone_accounts_href" value="<?= htmlspecialchars($record['standalone_accounts_href']) ?>" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Key Performance Metrics</h4>
                <?php for ($k = 1; $k <= 3; $k++): ?>
                    <div class="grid gap-2 sm:grid-cols-4">
                        <input type="text" name="kpi_<?= $k ?>_metric" value="<?= htmlspecialchars($record["kpi_{$k}_metric"]) ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <input type="text" name="kpi_<?= $k ?>_value" value="<?= htmlspecialchars($record["kpi_{$k}_value"]) ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <input type="text" name="kpi_<?= $k ?>_growth" value="<?= htmlspecialchars($record["kpi_{$k}_growth"]) ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <input type="number" min="0" max="100" name="kpi_<?= $k ?>_bar" value="<?= (int)$record["kpi_{$k}_bar"] ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <?php if ($type === 'quarterly'): ?>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">FY & Quarter</label>
                    <input type="text" disabled value="<?= htmlspecialchars($record['fiscal_year'] . ' · ' . $record['quarter']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Release Date</label>
                    <input type="text" name="release_date" value="<?= htmlspecialchars($record['release_date']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="Published" <?= $record['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                        <option value="Upcoming" <?= $record['status'] === 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Period Ended</label>
                    <input type="text" name="period_ended" value="<?= htmlspecialchars($record['period_ended']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">ISO Date</label>
                    <input type="date" name="iso_date" value="<?= htmlspecialchars($record['iso_date']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="h1_label" value="<?= htmlspecialchars($record['highlight_1_label']) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="h1_value" value="<?= htmlspecialchars($record['highlight_1_value']) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="h2_label" value="<?= htmlspecialchars($record['highlight_2_label']) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="h2_value" value="<?= htmlspecialchars($record['highlight_2_value']) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'subsidiary'): ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Entity Name</label>
                    <input type="text" name="entity_name" value="<?= htmlspecialchars($record['entity_name']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Audit Status</label>
                    <input type="text" name="latest_audit_status" value="<?= htmlspecialchars($record['latest_audit_status']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">SPV Role</label>
                <input type="text" name="spv_role" value="<?= htmlspecialchars($record['spv_role']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Report Title</label>
                <input type="text" name="report_title" value="<?= htmlspecialchars($record['report_title']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Upload Replacement PDF</label>
                    <input type="file" name="sub_file" accept=".pdf" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">PDF Link / Path</label>
                    <input type="text" name="href" value="<?= htmlspecialchars($record['href']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">File Size Label</label>
                    <input type="text" name="file_size" value="<?= htmlspecialchars($record['file_size']) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-xs font-bold text-white hover:bg-pingaksh-terracottaHover cursor-pointer">Save Changes</button>
        </div>
    </form>

    <?php if ($type === 'quarterly'): ?>
        <!-- Manage Documents inside this Quarterly Pack -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
            <h3 class="text-sm font-bold uppercase tracking-wider text-pingaksh-navy">Quarterly Documents (<?= count($packDocs) ?>)</h3>

            <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl">
                <?php foreach ($packDocs as $doc): ?>
                    <div class="p-3.5 flex items-center justify-between gap-4 text-xs">
                        <div>
                            <span class="rounded bg-slate-100 px-2 py-0.5 font-bold text-pingaksh-navy mr-2"><?= htmlspecialchars($doc['doc_type']) ?></span>
                            <a href="<?= htmlspecialchars($doc['href']) ?>" target="_blank" class="font-semibold text-pingaksh-terracotta underline"><?= htmlspecialchars($doc['title']) ?></a>
                            <span class="font-mono text-slate-500 ml-2">(<?= htmlspecialchars($doc['file_size']) ?>)</span>
                        </div>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="delete_doc">
                            <input type="hidden" name="record_type" value="quarterly">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="doc_id" value="<?= (int)$doc['id'] ?>">
                            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 font-semibold text-red-700 hover:bg-red-100 cursor-pointer">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="border-t border-slate-100 pt-4 space-y-3">
                <input type="hidden" name="action" value="add_doc">
                <input type="hidden" name="record_type" value="quarterly">
                <input type="hidden" name="id" value="<?= $id ?>">
                <h4 class="text-xs font-bold uppercase text-slate-600">Add New Document to <?= htmlspecialchars($record['fiscal_year'] . ' ' . $record['quarter']) ?></h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <select name="new_doc_type" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="Financial Statement">Financial Statement</option>
                        <option value="Investor Presentation">Investor Presentation</option>
                        <option value="Press Release">Press Release</option>
                        <option value="Auditor Review">Auditor Review</option>
                    </select>
                    <input type="text" name="new_doc_title" required placeholder="Document Title" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input type="file" name="new_doc_file" accept=".pdf" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="new_doc_href" placeholder="Or /uploads/investors/..." class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-xl bg-pingaksh-navy px-5 py-2 text-xs font-bold text-pingaksh-sand cursor-pointer">+ Add Document</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Vendor KYC Registrations';
$activeMenu = 'vendors';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(reference_id LIKE ? OR name_of_party LIKE ? OR pan_number LIKE ? OR gst_reg_number LIKE ? OR contact_person LIKE ?)";
    $val = "%{$search}%";
    array_push($params, $val, $val, $val, $val, $val);
}

if ($status !== '') {
    $where[] = "status = ?";
    $params[] = $status;
}

$sql = "SELECT * FROM vendor_registrations";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$success = null;
if (isset($_GET['created'])) $success = 'Vendor KYC record created.';
if (isset($_GET['updated'])) $success = 'Vendor KYC record updated.';
if (isset($_GET['deleted'])) $success = 'Vendor KYC record deleted.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Supply Chain Empanelment</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Vendor KYC Registrations</h2>
            <p class="mt-1 text-sm text-slate-600">Inspect vendor GST/PAN/Bank KYC details and statutory uploaded enclosures.</p>
        </div>
        <a href="create.php" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-pingaksh-terracottaHover">
            <i data-lucide="plus" class="h-4 w-4"></i> Add Vendor KYC
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
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search Ref ID, Party Name, PAN, GSTIN, or Contact Person..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta">
            </div>
            <div class="md:col-span-3">
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="under_review" <?= $status === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-3">
                <button type="submit" class="flex-1 rounded-xl bg-pingaksh-navy px-4 py-2.5 text-sm font-semibold text-pingaksh-sand">Filter</button>
                <?php if ($search || $status): ?>
                    <a href="index.php" class="flex items-center justify-center rounded-xl border border-slate-200 px-3 text-slate-500"><i data-lucide="x" class="h-4 w-4"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center text-slate-500">No vendor registrations found.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">
                            <th class="px-5 py-3.5">Ref ID & Party</th>
                            <th class="px-5 py-3.5">GST / PAN / HSN</th>
                            <th class="px-5 py-3.5">Bank & MSMED</th>
                            <th class="px-5 py-3.5">Enclosures</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs font-bold text-pingaksh-terracotta"><?= htmlspecialchars($item['reference_id']) ?></span>
                                    <p class="font-bold text-pingaksh-navy mt-0.5"><?= htmlspecialchars($item['name_of_party']) ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($item['contact_person']) ?> · <?= htmlspecialchars($item['contact_mobile']) ?></p>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($item['principal_city']) ?>, <?= htmlspecialchars($item['principal_state']) ?></p>
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    <p><strong class="text-pingaksh-navy">PAN:</strong> <span class="font-mono"><?= htmlspecialchars($item['pan_number']) ?></span></p>
                                    <p><strong class="text-pingaksh-navy">GST:</strong> <span class="font-mono"><?= htmlspecialchars($item['gst_reg_number'] ?: $item['gst_status']) ?></span></p>
                                    <p><strong class="text-pingaksh-navy">HSN/SAC:</strong> <span class="font-mono"><?= htmlspecialchars($item['hsn_sac_code']) ?></span></p>
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    <p class="font-semibold text-pingaksh-navy"><?= htmlspecialchars($item['bank_name']) ?></p>
                                    <p class="font-mono text-[11px] text-slate-600">A/c: <?= htmlspecialchars($item['account_number']) ?> (<?= htmlspecialchars($item['ifsc_code']) ?>)</p>
                                    <p class="mt-1 text-[11px] text-pingaksh-terracotta font-semibold"><?= htmlspecialchars($item['mfg_unit_status']) ?> · MSMED: <?= htmlspecialchars($item['msmed_section_2n']) ?></p>
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php if ($item['cancelled_cheque_copy']): ?>
                                            <a href="<?= htmlspecialchars($item['cancelled_cheque_copy']) ?>" target="_blank" class="rounded bg-slate-100 px-2 py-1 font-semibold text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white">Cheque</a>
                                        <?php endif; ?>
                                        <?php if ($item['pan_scan_copy']): ?>
                                            <a href="<?= htmlspecialchars($item['pan_scan_copy']) ?>" target="_blank" class="rounded bg-slate-100 px-2 py-1 font-semibold text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white">PAN</a>
                                        <?php endif; ?>
                                        <?php if ($item['gstin_certificate']): ?>
                                            <a href="<?= htmlspecialchars($item['gstin_certificate']) ?>" target="_blank" class="rounded bg-slate-100 px-2 py-1 font-semibold text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white">GST</a>
                                        <?php endif; ?>
                                        <?php if ($item['ssi_msmed_em_copy']): ?>
                                            <a href="<?= htmlspecialchars($item['ssi_msmed_em_copy']) ?>" target="_blank" class="rounded bg-slate-100 px-2 py-1 font-semibold text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white">MSMED</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-pingaksh-sand px-3 py-1 text-[11px] font-bold uppercase text-pingaksh-navy">
                                        <?= htmlspecialchars(str_replace('_', ' ', $item['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="edit.php?id=<?= (int)$item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                        <a href="delete.php?id=<?= (int)$item['id'] ?>" onclick="return confirm('Delete this vendor registration permanently?')" class="rounded-lg border border-slate-200 p-2 text-red-600 hover:bg-red-600 hover:text-white"><i data-lucide="trash-2" class="h-4 w-4"></i></a>
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
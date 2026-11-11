<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Edit Vendor KYC';
$activeMenu = 'vendors';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM vendor_registrations WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) die('Vendor record not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $chequePath = !empty($_FILES['cancelled_cheque_copy']['name'])
            ? vendorUploadEnclosure($_FILES['cancelled_cheque_copy'], 'cheque')
            : $item['cancelled_cheque_copy'];

        $panPath = !empty($_FILES['pan_scan_copy']['name'])
            ? vendorUploadEnclosure($_FILES['pan_scan_copy'], 'pan')
            : $item['pan_scan_copy'];

        $gstPath = !empty($_FILES['gstin_certificate']['name'])
            ? vendorUploadEnclosure($_FILES['gstin_certificate'], 'gst')
            : $item['gstin_certificate'];

        $msmedPath = !empty($_FILES['ssi_msmed_em_copy']['name'])
            ? vendorUploadEnclosure($_FILES['ssi_msmed_em_copy'], 'msmed')
            : $item['ssi_msmed_em_copy'];

        $upd = $pdo->prepare("
            UPDATE vendor_registrations SET
                name_of_party = ?, contact_person = ?, contact_mobile = ?,
                principal_address = ?, principal_city = ?, principal_pin_code = ?, principal_state = ?, principal_tel_no = ?, principal_fax_no = ?, principal_email_id = ?,
                gst_contact_name = ?, gst_contact_designation = ?, gst_contact_address = ?, gst_contact_city = ?, gst_contact_pin_code = ?, gst_contact_state = ?, gst_contact_tel_no = ?, gst_contact_mobile_no = ?, gst_contact_email_id = ?,
                director_1 = ?, director_2 = ?, director_3 = ?, director_4 = ?,
                gst_status = ?, gst_reg_number = ?, material_services_dealing = ?, hsn_sac_code = ?, pan_number = ?, ssi_msmed_number = ?, iec_number = ?,
                bank_name = ?, account_type = ?, account_number = ?, ifsc_code = ?,
                mfg_unit_status = ?, msmed_section_2n = ?,
                cancelled_cheque_copy = ?, pan_scan_copy = ?, gstin_certificate = ?, ssi_msmed_em_copy = ?,
                status = ?, admin_remarks = ?
            WHERE id = ?
        ");

        $upd->execute([
            trim($_POST['name_of_party'] ?? ''),
            trim($_POST['contact_person'] ?? ''),
            trim($_POST['contact_mobile'] ?? ''),
            trim($_POST['principal_address'] ?? ''),
            trim($_POST['principal_city'] ?? ''),
            trim($_POST['principal_pin_code'] ?? ''),
            trim($_POST['principal_state'] ?? ''),
            trim($_POST['principal_tel_no'] ?? ''),
            trim($_POST['principal_fax_no'] ?? ''),
            trim($_POST['principal_email_id'] ?? ''),
            trim($_POST['gst_contact_name'] ?? ''),
            trim($_POST['gst_contact_designation'] ?? ''),
            trim($_POST['gst_contact_address'] ?? ''),
            trim($_POST['gst_contact_city'] ?? ''),
            trim($_POST['gst_contact_pin_code'] ?? ''),
            trim($_POST['gst_contact_state'] ?? ''),
            trim($_POST['gst_contact_tel_no'] ?? ''),
            trim($_POST['gst_contact_mobile_no'] ?? ''),
            trim($_POST['gst_contact_email_id'] ?? ''),
            trim($_POST['director_1'] ?? ''),
            trim($_POST['director_2'] ?? ''),
            trim($_POST['director_3'] ?? ''),
            trim($_POST['director_4'] ?? ''),
            $_POST['gst_status'] ?? 'Registered',
            trim($_POST['gst_reg_number'] ?? ''),
            trim($_POST['material_services_dealing'] ?? ''),
            trim($_POST['hsn_sac_code'] ?? ''),
            strtoupper(trim($_POST['pan_number'] ?? '')),
            trim($_POST['ssi_msmed_number'] ?? ''),
            trim($_POST['iec_number'] ?? ''),
            trim($_POST['bank_name'] ?? ''),
            trim($_POST['account_type'] ?? ''),
            trim($_POST['account_number'] ?? ''),
            strtoupper(trim($_POST['ifsc_code'] ?? '')),
            trim($_POST['mfg_unit_status'] ?? ''),
            $_POST['msmed_section_2n'] ?? 'No',
            $chequePath,
            $panPath,
            $gstPath,
            $msmedPath,
            $_POST['status'] ?? 'pending',
            trim($_POST['admin_remarks'] ?? ''),
            $id
        ]);

        header('Location: index.php?updated=1');
        exit;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl pb-20">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="index.php" class="mb-2 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Vendors
            </a>
            <h2 class="text-2xl font-bold text-pingaksh-navy">Edit Vendor: <?= htmlspecialchars($item['reference_id']) ?></h2>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-8 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-8 shadow-sm">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">1. Party & Principal Place of Supply</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase">Name of Party *</label>
                <input type="text" name="name_of_party" required value="<?= htmlspecialchars($item['name_of_party']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Contact Person *</label>
                <input type="text" name="contact_person" required value="<?= htmlspecialchars($item['contact_person']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Mobile No. *</label>
                <input type="text" name="contact_mobile" required value="<?= htmlspecialchars($item['contact_mobile']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Principal Email *</label>
                <input type="email" name="principal_email_id" required value="<?= htmlspecialchars($item['principal_email_id']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase">Principal Street Address *</label>
                <input type="text" name="principal_address" required value="<?= htmlspecialchars($item['principal_address']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">City *</label>
                <input type="text" name="principal_city" required value="<?= htmlspecialchars($item['principal_city']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">PIN Code *</label>
                <input type="text" name="principal_pin_code" required value="<?= htmlspecialchars($item['principal_pin_code']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">State *</label>
                <input type="text" name="principal_state" required value="<?= htmlspecialchars($item['principal_state']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Tel No.</label>
                <input type="text" name="principal_tel_no" value="<?= htmlspecialchars($item['principal_tel_no'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Fax No.</label>
                <input type="text" name="principal_fax_no" value="<?= htmlspecialchars($item['principal_fax_no'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">2. GST Contact & Directors (1–4)</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Contact Name *</label>
                <input type="text" name="gst_contact_name" required value="<?= htmlspecialchars($item['gst_contact_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Designation</label>
                <input type="text" name="gst_contact_designation" value="<?= htmlspecialchars($item['gst_contact_designation'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Mobile *</label>
                <input type="text" name="gst_contact_mobile_no" required value="<?= htmlspecialchars($item['gst_contact_mobile_no']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Email *</label>
                <input type="email" name="gst_contact_email_id" required value="<?= htmlspecialchars($item['gst_contact_email_id']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase">GST Address</label>
                <input type="text" name="gst_contact_address" value="<?= htmlspecialchars($item['gst_contact_address'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director 1 *</label>
                <input type="text" name="director_1" required value="<?= htmlspecialchars($item['director_1']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director 2</label>
                <input type="text" name="director_2" value="<?= htmlspecialchars($item['director_2'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director 3</label>
                <input type="text" name="director_3" value="<?= htmlspecialchars($item['director_3'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director 4</label>
                <input type="text" name="director_4" value="<?= htmlspecialchars($item['director_4'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">3. GST, Tax, Banking & MSMED</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Status</label>
                <select name="gst_status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <?php foreach (['Registered', 'Not Registered', 'Compounding Scheme'] as $st): ?>
                        <option value="<?= $st ?>" <?= $item['gst_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GSTIN Number</label>
                <input type="text" name="gst_reg_number" value="<?= htmlspecialchars($item['gst_reg_number'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">PAN Number *</label>
                <input type="text" name="pan_number" required value="<?= htmlspecialchars($item['pan_number']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase">Material / Services Dealing *</label>
                <input type="text" name="material_services_dealing" required value="<?= htmlspecialchars($item['material_services_dealing']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">HSN / SAC Code *</label>
                <input type="text" name="hsn_sac_code" required value="<?= htmlspecialchars($item['hsn_sac_code']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">SSI / MSMED No.</label>
                <input type="text" name="ssi_msmed_number" value="<?= htmlspecialchars($item['ssi_msmed_number'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">IEC Number</label>
                <input type="text" name="iec_number" value="<?= htmlspecialchars($item['iec_number'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Bank Name *</label>
                <input type="text" name="bank_name" required value="<?= htmlspecialchars($item['bank_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Account Type *</label>
                <input type="text" name="account_type" required value="<?= htmlspecialchars($item['account_type']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Account Number *</label>
                <input type="text" name="account_number" required value="<?= htmlspecialchars($item['account_number']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">IFSC Code *</label>
                <input type="text" name="ifsc_code" required value="<?= htmlspecialchars($item['ifsc_code']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Mfg. Unit Status *</label>
                <input type="text" name="mfg_unit_status" required value="<?= htmlspecialchars($item['mfg_unit_status']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">MSMED Section 2(n)</label>
                <select name="msmed_section_2n" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <option value="Yes" <?= $item['msmed_section_2n'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="No" <?= $item['msmed_section_2n'] === 'No' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">4. Enclosures & Verification Status</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <?php
            $docs = [
                'cancelled_cheque_copy' => 'Cancelled Cheque Copy',
                'pan_scan_copy' => 'PAN Scan Copy',
                'gstin_certificate' => 'GST Registration Certificate',
                'ssi_msmed_em_copy' => 'SSI / MSMED & EM Copy'
            ];
            foreach ($docs as $col => $label):
            ?>
                <div class="rounded-xl border border-slate-200 p-4">
                    <label class="mb-1 block text-xs font-bold uppercase text-pingaksh-navy"><?= $label ?></label>
                    <?php if (!empty($item[$col])): ?>
                        <a href="<?= htmlspecialchars($item[$col]) ?>" target="_blank" class="mb-2 inline-block text-xs font-bold text-pingaksh-terracotta underline">View Current File</a>
                    <?php endif; ?>
                    <input type="file" name="<?= $col ?>" class="w-full text-xs">
                </div>
            <?php endforeach; ?>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <?php foreach (['pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $item['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase">Admin Remarks</label>
                <textarea name="admin_remarks" rows="3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm"><?= htmlspecialchars($item['admin_remarks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold">Cancel</a>
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-sm font-semibold text-white">Update Vendor KYC</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
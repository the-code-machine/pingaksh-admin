<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Add Vendor KYC';
$activeMenu = 'vendors';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $refId = 'PRPL-KYC-' . date('Y') . '-' . random_int(100000, 999999);
        $chequePath = !empty($_FILES['cancelled_cheque_copy']) ? vendorUploadEnclosure($_FILES['cancelled_cheque_copy'], 'cheque') : null;
        $panPath    = !empty($_FILES['pan_scan_copy']) ? vendorUploadEnclosure($_FILES['pan_scan_copy'], 'pan') : null;
        $gstPath    = !empty($_FILES['gstin_certificate']) ? vendorUploadEnclosure($_FILES['gstin_certificate'], 'gst') : null;
        $msmedPath  = !empty($_FILES['ssi_msmed_em_copy']) ? vendorUploadEnclosure($_FILES['ssi_msmed_em_copy'], 'msmed') : null;

        $stmt = $pdo->prepare("
            INSERT INTO vendor_registrations (
                reference_id, name_of_party, contact_person, contact_mobile,
                principal_address, principal_city, principal_pin_code, principal_state, principal_tel_no, principal_fax_no, principal_email_id,
                gst_contact_name, gst_contact_designation, gst_contact_address, gst_contact_city, gst_contact_pin_code, gst_contact_state, gst_contact_tel_no, gst_contact_mobile_no, gst_contact_email_id,
                director_1, director_2, director_3, director_4,
                gst_status, gst_reg_number, material_services_dealing, hsn_sac_code, pan_number, ssi_msmed_number, iec_number,
                bank_name, account_type, account_number, ifsc_code,
                mfg_unit_status, msmed_section_2n,
                cancelled_cheque_copy, pan_scan_copy, gstin_certificate, ssi_msmed_em_copy,
                status, admin_remarks
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?
            )
        ");

        $stmt->execute([
            $refId,
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
            trim($_POST['account_type'] ?? 'Current Account'),
            trim($_POST['account_number'] ?? ''),
            strtoupper(trim($_POST['ifsc_code'] ?? '')),
            trim($_POST['mfg_unit_status'] ?? 'Small Scale'),
            $_POST['msmed_section_2n'] ?? 'No',
            $chequePath,
            $panPath,
            $gstPath,
            $msmedPath,
            $_POST['status'] ?? 'pending',
            trim($_POST['admin_remarks'] ?? '')
        ]);

        header('Location: index.php?created=1');
        exit;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Vendors
        </a>
        <h2 class="text-2xl font-bold text-pingaksh-navy">Create Vendor KYC Record</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-8 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-8 shadow-sm">
        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">1. Party & Principal Place of Supply</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase">Name of Party *</label>
                <input type="text" name="name_of_party" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Contact Person *</label>
                <input type="text" name="contact_person" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Mobile No. *</label>
                <input type="text" name="contact_mobile" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Principal Email *</label>
                <input type="email" name="principal_email_id" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase">Principal Street Address *</label>
                <input type="text" name="principal_address" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">City *</label>
                <input type="text" name="principal_city" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">PIN Code *</label>
                <input type="text" name="principal_pin_code" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">State *</label>
                <input type="text" name="principal_state" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">2. GST Contact & Directors</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Contact Name *</label>
                <input type="text" name="gst_contact_name" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Contact Mobile *</label>
                <input type="text" name="gst_contact_mobile_no" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Contact Email *</label>
                <input type="email" name="gst_contact_email_id" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director / Partner 1 *</label>
                <input type="text" name="director_1" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director / Partner 2</label>
                <input type="text" name="director_2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Director / Partner 3</label>
                <input type="text" name="director_3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">3. GST, PAN, Bank & MSMED Details</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Status *</label>
                <select name="gst_status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <option value="Registered">Registered</option>
                    <option value="Not Registered">Not Registered</option>
                    <option value="Compounding Scheme">Compounding Scheme</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GSTIN Number</label>
                <input type="text" name="gst_reg_number" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">PAN Number *</label>
                <input type="text" name="pan_number" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase">Material / Services Dealing *</label>
                <input type="text" name="material_services_dealing" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">HSN / SAC Code *</label>
                <input type="text" name="hsn_sac_code" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Bank Name *</label>
                <input type="text" name="bank_name" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Account Type *</label>
                <input type="text" name="account_type" value="Current Account" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Account Number *</label>
                <input type="text" name="account_number" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">IFSC Code *</label>
                <input type="text" name="ifsc_code" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Mfg. Unit Status *</label>
                <input type="text" name="mfg_unit_status" value="Small Scale" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">MSMED Section 2(n)</label>
                <select name="msmed_section_2n" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                </select>
            </div>
        </div>

        <h3 class="border-b pb-2 font-bold text-pingaksh-navy">4. Enclosures & Admin Status</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Cancelled Cheque Copy</label>
                <input type="file" name="cancelled_cheque_copy" class="w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">PAN Scan Copy</label>
                <input type="file" name="pan_scan_copy" class="w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">GST Certificate Copy</label>
                <input type="file" name="gstin_certificate" class="w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">SSI / MSMED EM Copy</label>
                <input type="file" name="ssi_msmed_em_copy" class="w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Verification Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase">Admin Remarks</label>
                <textarea name="admin_remarks" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold">Cancel</a>
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-sm font-semibold text-white">Save Vendor KYC</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
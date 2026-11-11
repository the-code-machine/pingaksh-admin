<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Add Contact Inquiry';
$activeMenu = 'contacts';
$errors = [];

$item = [
    'full_name' => '',
    'organization' => '',
    'email' => '',
    'phone' => '',
    'subject' => 'Institutional / Investor Relations Inquiry',
    'message' => '',
    'status' => 'new',
    'admin_notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($item as $k => $v) {
        $item[$k] = trim($_POST[$k] ?? '');
    }

    if ($item['full_name'] === '' || $item['organization'] === '' || $item['email'] === '' || $item['phone'] === '' || $item['subject'] === '' || $item['message'] === '') {
        $errors[] = 'All primary inquiry fields are required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO contacts (full_name, organization, email, phone, subject, message, status, admin_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $item['full_name'], $item['organization'], $item['email'], $item['phone'],
            $item['subject'], $item['message'], $item['status'], $item['admin_notes']
        ]);
        header('Location: index.php?created=1');
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Inquiries
        </a>
        <h2 class="text-2xl font-bold text-pingaksh-navy">Log New Contact Inquiry</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-8 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($item['full_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Organization / Entity *</label>
                <input type="text" name="organization" required value="<?= htmlspecialchars($item['organization']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Official Email *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($item['email']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Phone Number *</label>
                <input type="text" name="phone" required value="<?= htmlspecialchars($item['phone']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Inquiry Category / Subject *</label>
                <input type="text" name="subject" required value="<?= htmlspecialchars($item['subject']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Detailed Message *</label>
                <textarea name="message" rows="4" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm"><?= htmlspecialchars($item['message']) ?></textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <option value="new">New</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Internal Admin Notes</label>
                <textarea name="admin_notes" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm"><?= htmlspecialchars($item['admin_notes']) ?></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-sm font-semibold text-white">Save Inquiry</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
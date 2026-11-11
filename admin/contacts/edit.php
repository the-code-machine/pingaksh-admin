<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Contact Inquiry';
$activeMenu = 'contacts';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) die('Inquiry not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['full_name', 'organization', 'email', 'phone', 'subject', 'message', 'status', 'admin_notes'];
    foreach ($fields as $f) {
        $item[$f] = trim($_POST[$f] ?? '');
    }

    if ($item['full_name'] === '' || $item['email'] === '' || $item['message'] === '') {
        $errors[] = 'Name, Email, and Message are required.';
    }

    if (!$errors) {
        $upd = $pdo->prepare("
            UPDATE contacts
            SET full_name = ?, organization = ?, email = ?, phone = ?, subject = ?, message = ?, status = ?, admin_notes = ?
            WHERE id = ?
        ");
        $upd->execute([
            $item['full_name'], $item['organization'], $item['email'], $item['phone'],
            $item['subject'], $item['message'], $item['status'], $item['admin_notes'], $id
        ]);
        header('Location: index.php?updated=1');
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
        <h2 class="text-2xl font-bold text-pingaksh-navy">Edit Inquiry #<?= (int)$item['id'] ?></h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-8 shadow-sm">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($item['full_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Organization *</label>
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
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Subject *</label>
                <input type="text" name="subject" required value="<?= htmlspecialchars($item['subject']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Message *</label>
                <textarea name="message" rows="4" required class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm"><?= htmlspecialchars($item['message']) ?></textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm">
                    <?php foreach (['new' => 'New', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'archived' => 'Archived'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $item['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase text-pingaksh-navy">Internal Admin Notes</label>
                <textarea name="admin_notes" rows="3" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3.5 py-2.5 text-sm"><?= htmlspecialchars($item['admin_notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-sm font-semibold text-white">Update Inquiry</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
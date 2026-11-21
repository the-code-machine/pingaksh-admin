<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Career Application';
$activeMenu = 'careers';

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid application ID.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM career_applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    header('Location: index.php?error=' . urlencode('Application not found.'));
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $resumePath = $app['resume_path'];
        $resumeName = $app['resume_name'];

        if (!empty($_FILES['resume']['name']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
                throw new RuntimeException('Only PDF, DOC, or DOCX files are allowed.');
            }
            $dir = __DIR__ . '/../../uploads/resumes';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fileName = 'resume-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
            move_uploaded_file($_FILES['resume']['tmp_name'], $dir . '/' . $fileName);
            $resumePath = BASE_URL . '/uploads/resumes/' . $fileName;
            $resumeName = $_FILES['resume']['name'];
        }

        $upd = $pdo->prepare("
            UPDATE career_applications SET
                full_name = ?, email = ?, contact_number = ?, position = ?,
                preferred_location = ?, experience_level = ?, resume_path = ?,
                resume_name = ?, portfolio_url = ?, status = ?, hr_notes = ?
            WHERE id = ?
        ");
        $upd->execute([
            trim($_POST['full_name'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['contact_number'] ?? ''),
            trim($_POST['position'] ?? ''),
            trim($_POST['preferred_location'] ?? ''),
            $_POST['experience_level'] ?? 'Fresher',
            $resumePath,
            $resumeName,
            trim($_POST['portfolio_url'] ?? '') ?: null,
            $_POST['status'] ?? 'New',
            trim($_POST['hr_notes'] ?? '') ?: null,
            $id
        ]);

        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        $success = 'Application updated successfully.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl pb-20">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">HR & Recruitment</p>
            <h2 class="text-2xl font-bold text-pingaksh-navy">Edit Application #<?= $id ?></h2>
        </div>
        <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700">← Back</a>
    </div>

    <?php if ($success): ?>
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($app['full_name']) ?>" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($app['email']) ?>" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Contact Number</label>
                <input type="text" name="contact_number" value="<?= htmlspecialchars($app['contact_number']) ?>" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Position Applying For</label>
                <input type="text" name="position" value="<?= htmlspecialchars($app['position']) ?>" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Preferred Location</label>
                <input type="text" name="preferred_location" value="<?= htmlspecialchars($app['preferred_location']) ?>" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Experience Level</label>
                <select name="experience_level" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <?php foreach (['Fresher', '1–3 Years', '3–5 Years', '5+ Years'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $app['experience_level'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <?php foreach (['New', 'Shortlisted', 'Reviewed', 'Rejected'] as $st): ?>
                        <option value="<?= $st ?>" <?= $app['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Replace Resume (Optional)</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                <a href="<?= htmlspecialchars($app['resume_path']) ?>" target="_blank" class="mt-1 inline-block text-xs font-semibold text-pingaksh-terracotta underline">Current: <?= htmlspecialchars($app['resume_name']) ?></a>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Portfolio / LinkedIn URL</label>
                <input type="url" name="portfolio_url" value="<?= htmlspecialchars($app['portfolio_url'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">HR Notes</label>
            <textarea name="hr_notes" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"><?= htmlspecialchars($app['hr_notes'] ?? '') ?></textarea>
        </div>
        <div class="pt-3 flex justify-end">
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-xs font-bold text-white cursor-pointer">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
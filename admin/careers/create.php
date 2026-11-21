<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Add Career Application';
$activeMenu = 'careers';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $location = trim($_POST['preferred_location'] ?? '');
        $exp = $_POST['experience_level'] ?? 'Fresher';
        $portfolio = trim($_POST['portfolio_url'] ?? '');
        $status = $_POST['status'] ?? 'New';
        $hrNotes = trim($_POST['hr_notes'] ?? '');

        if ($fullName === '' || $email === '' || $contact === '' || $position === '' || $location === '') {
            throw new RuntimeException('All required fields must be filled.');
        }

        $resumePath = trim($_POST['resume_url_fallback'] ?? '');
        $resumeName = 'Resume';

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

        if ($resumePath === '') {
            throw new RuntimeException('Please upload a Resume / CV file.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO career_applications (
                full_name, email, contact_number, position, preferred_location,
                experience_level, resume_path, resume_name, portfolio_url, status, hr_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $fullName, $email, $contact, $position, $location,
            $exp, $resumePath, $resumeName, $portfolio !== '' ? $portfolio : null, $status, $hrNotes !== '' ? $hrNotes : null
        ]);

        header('Location: index.php?success=' . urlencode('Career application added.'));
        exit;
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
            <h2 class="text-2xl font-bold text-pingaksh-navy">Add Career Application</h2>
        </div>
        <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700">← Back</a>
    </div>

    <?php if ($error): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Name *</label>
                <input type="text" name="full_name" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email Address *</label>
                <input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Contact Number *</label>
                <input type="text" name="contact_number" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Position Applying For *</label>
                <input type="text" name="position" required placeholder="e.g. Plant Engineer (Hydro)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Preferred Location *</label>
                <input type="text" name="preferred_location" required placeholder="e.g. Bageshwar / Rajasthan" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Experience Level *</label>
                <select name="experience_level" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="Fresher">Fresher</option>
                    <option value="1–3 Years">1–3 Years</option>
                    <option value="3–5 Years">3–5 Years</option>
                    <option value="5+ Years">5+ Years</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="New">New</option>
                    <option value="Shortlisted">Shortlisted</option>
                    <option value="Reviewed">Reviewed</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Upload Resume (PDF/DOC) *</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Portfolio / LinkedIn URL</label>
                <input type="url" name="portfolio_url" placeholder="https://linkedin.com/in/..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">HR Notes</label>
            <textarea name="hr_notes" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
        </div>
        <div class="pt-3 flex justify-end">
            <button type="submit" class="rounded-xl bg-pingaksh-terracotta px-6 py-2.5 text-xs font-bold text-white cursor-pointer">Save Application</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
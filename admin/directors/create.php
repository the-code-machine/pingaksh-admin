<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Add Director Profile';
$activeMenu = 'directors';

$errors = [];

// Pre-fill JSON schemas
$defaultArray = "[\n  \"\"\n]";
$defaultCommittees = "[\n  {\n    \"name\": \"\",\n    \"role\": \"Member\"\n  }\n]";

$form = [
    'name' => '',
    'slug' => '',
    'designation' => '',
    'board_category' => 'Promoter & Executive',
    'is_founder' => '0',
    'din_number' => '',
    'appointed_since' => '',
    'alma_mater' => '',
    'portrait_alt' => '',
    'short_summary' => '',
    'executive_quote' => '',
    'qualifications' => $defaultArray,
    'biography_paragraphs' => $defaultArray,
    'key_responsibilities' => $defaultArray,
    'committees' => $defaultCommittees,
    'directorships_and_affiliations' => $defaultArray,
    'display_order' => '0',
    'is_published' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, $_POST);
    $form['is_founder'] = isset($_POST['is_founder']) ? '1' : '0';
    $form['is_published'] = isset($_POST['is_published']) ? '1' : '0';

    if (empty(trim($form['name']))) {
        $errors[] = 'Name is required.';
    }
    if (empty(trim($form['slug']))) {
        $errors[] = 'URL Slug is required.';
    }

    // Validate JSON
    $validatedJson = [];
    $jsonFields = ['qualifications', 'biography_paragraphs', 'key_responsibilities', 'committees', 'directorships_and_affiliations'];
    foreach ($jsonFields as $field) {
        try {
            $validatedJson[$field] = directorsValidateJson($form[$field]);
        } catch (Exception $e) {
            $errors[] = 'Invalid JSON in field: ' . str_replace('_', ' ', strtoupper($field));
        }
    }

    $imagePath = null;

    if (!$errors) {
        try {
            // Upload Portrait (Optional during creation, but recommended)
            if (isset($_FILES['portrait_image']) && $_FILES['portrait_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $imagePath = directorsUploadFile($_FILES['portrait_image'], false);
            }

            $stmt = $pdo->prepare("
                INSERT INTO directors (
                    slug, name, designation, board_category, is_founder, din_number, appointed_since, alma_mater,
                    portrait_image, portrait_alt, short_summary, executive_quote,
                    qualifications, biography_paragraphs, key_responsibilities, committees, directorships_and_affiliations,
                    display_order, is_published
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim($form['slug']),
                trim($form['name']),
                trim($form['designation']),
                $form['board_category'],
                $form['is_founder'],
                trim($form['din_number']),
                trim($form['appointed_since']),
                trim($form['alma_mater']),
                $imagePath,
                trim($form['portrait_alt']),
                trim($form['short_summary']),
                trim($form['executive_quote']),
                $validatedJson['qualifications'],
                $validatedJson['biography_paragraphs'],
                $validatedJson['key_responsibilities'],
                $validatedJson['committees'],
                $validatedJson['directorships_and_affiliations'],
                (int) $form['display_order'],
                $form['is_published'],
            ]);

            header('Location: index.php?created=1');
            exit;
        } catch (PDOException $e) {
            directorsDeleteFile($imagePath); // cleanup on fail
            if ($e->errorInfo[1] === 1062) {
                $errors[] = 'A director with this slug already exists.';
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Board
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Add Director Profile</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Please fix the following:</p>
            <ul class="list-disc pl-5 text-sm text-red-700">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Core Details -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Identity & Role</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($form['name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($form['slug']) ?>" placeholder="e.g. naresh-goel" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Designation *</label>
                    <input type="text" name="designation" required value="<?= htmlspecialchars($form['designation']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Board Category</label>
                    <select name="board_category" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                        <option value="Promoter & Executive" <?= $form['board_category'] === 'Promoter & Executive' ? 'selected' : '' ?>>Promoter & Executive</option>
                        <option value="Executive Director" <?= $form['board_category'] === 'Executive Director' ? 'selected' : '' ?>>Executive Director</option>
                        <option value="Independent Director" <?= $form['board_category'] === 'Independent Director' ? 'selected' : '' ?>>Independent Director</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">DIN Number</label>
                    <input type="text" name="din_number" value="<?= htmlspecialchars($form['din_number']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Appointed Since</label>
                    <input type="text" name="appointed_since" value="<?= htmlspecialchars($form['appointed_since']) ?>" placeholder="e.g. 2008" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_founder" value="1" <?= $form['is_founder'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Is Founder / Promoter?</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Media & Bio Summary -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Portrait & Summaries</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Portrait Image</label>
                    <input type="file" name="portrait_image" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-pingaksh-sand file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-pingaksh-navy">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Image Alt Text (SEO)</label>
                    <input type="text" name="portrait_alt" value="<?= htmlspecialchars($form['portrait_alt']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Alma Mater</label>
                    <input type="text" name="alma_mater" value="<?= htmlspecialchars($form['alma_mater']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Summary (Card Preview)</label>
                    <textarea name="short_summary" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['short_summary']) ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Executive Quote</label>
                    <textarea name="executive_quote" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['executive_quote']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- Advanced JSON Lists -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-bold text-pingaksh-navy">Detailed Profile (JSON Arrays)</h3>
            <p class="text-xs text-slate-500 mb-6">Format lists as JSON arrays (`["Item 1", "Item 2"]`).</p>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Biography Paragraphs</label>
                    <textarea name="biography_paragraphs" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['biography_paragraphs']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Key Responsibilities</label>
                    <textarea name="key_responsibilities" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['key_responsibilities']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Qualifications</label>
                    <textarea name="qualifications" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['qualifications']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Board Committees</label>
                    <textarea name="committees" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['committees']) ?></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Directorships & Affiliations</label>
                    <textarea name="directorships_and_affiliations" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($form['directorships_and_affiliations']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- Settings -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Order</label>
                    <input type="number" name="display_order" value="<?= htmlspecialchars($form['display_order']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live</span>
                    </label>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover">
                <i data-lucide="save" class="h-4 w-4"></i> Save Profile
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
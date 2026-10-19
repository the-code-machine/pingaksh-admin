<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Edit Director Profile';
$activeMenu = 'directors';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM directors WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die('Director not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $boardCategory = $_POST['board_category'] ?? 'Promoter & Executive';
    $isFounder = isset($_POST['is_founder']) ? 1 : 0;
    $dinNumber = trim($_POST['din_number'] ?? '');
    $appointedSince = trim($_POST['appointed_since'] ?? '');
    $almaMater = trim($_POST['alma_mater'] ?? '');
    $portraitAlt = trim($_POST['portrait_alt'] ?? '');
    $shortSummary = trim($_POST['short_summary'] ?? '');
    $executiveQuote = trim($_POST['executive_quote'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($name === '') $errors[] = 'Name is required.';
    if ($slug === '') $errors[] = 'Slug is required.';

    $validatedJson = [];
    $jsonFields = ['qualifications', 'biography_paragraphs', 'key_responsibilities', 'committees', 'directorships_and_affiliations'];
    
    foreach ($jsonFields as $field) {
        try {
            $validatedJson[$field] = directorsValidateJson($_POST[$field] ?? '');
        } catch (Exception $e) {
            $errors[] = "Invalid JSON in field: " . str_replace('_', ' ', strtoupper($field));
        }
    }

    $newImagePath = null;

    if (!$errors) {
        try {
            if (isset($_FILES['portrait_image']) && $_FILES['portrait_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $newImagePath = directorsUploadFile($_FILES['portrait_image'], false);
            }

            $imagePath = $newImagePath ?: $item['portrait_image'];

            if (isset($_POST['remove_image'])) {
                if ($item['portrait_image']) directorsDeleteFile($item['portrait_image']);
                $imagePath = null;
                $newImagePath = null;
            }

            $updateStmt = $pdo->prepare("
                UPDATE directors SET
                    name = ?, slug = ?, designation = ?, board_category = ?, is_founder = ?,
                    din_number = ?, appointed_since = ?, alma_mater = ?, portrait_image = ?, portrait_alt = ?,
                    short_summary = ?, executive_quote = ?,
                    qualifications = ?, biography_paragraphs = ?, key_responsibilities = ?, committees = ?, directorships_and_affiliations = ?,
                    display_order = ?, is_published = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $name, $slug, $designation, $boardCategory, $isFounder,
                $dinNumber, $appointedSince, $almaMater, $imagePath, $portraitAlt,
                $shortSummary, $executiveQuote,
                $validatedJson['qualifications'], $validatedJson['biography_paragraphs'], $validatedJson['key_responsibilities'],
                $validatedJson['committees'], $validatedJson['directorships_and_affiliations'],
                $displayOrder, $isPublished, $id
            ]);

            if ($newImagePath && $item['portrait_image'] && $item['portrait_image'] !== $newImagePath) {
                directorsDeleteFile($item['portrait_image']);
            }

            header('Location: index.php?updated=1');
            exit;
        } catch (PDOException $e) {
            directorsDeleteFile($newImagePath);
            if ($e->errorInfo[1] === 1062) $errors[] = 'Slug must be unique.';
            else $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    $item = array_merge($item, $_POST);
}

$previewUrl = directorsResolveUrl($item['portrait_image'] ?? '');
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Board
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Edit Director: <?= htmlspecialchars($item['name']) ?></h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Please fix the following:</p>
            <ul class="list-disc pl-5 text-sm text-red-700">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- Current Portrait Preview -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Portrait Image</h3>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="relative h-28 w-28 overflow-hidden rounded-full border border-slate-200 bg-slate-100 shrink-0">
                    <?php if ($previewUrl): ?>
                        <img src="<?= htmlspecialchars($previewUrl) ?>" alt="Portrait" class="h-full w-full object-cover">
                    <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center text-slate-400"><i data-lucide="user" class="h-8 w-8"></i></div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 space-y-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Replace Portrait</label>
                        <input type="file" name="portrait_image" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-pingaksh-sand file:px-4 file:py-2 file:font-semibold file:text-pingaksh-navy">
                    </div>
                    <?php if ($item['portrait_image']): ?>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded border-slate-300 text-red-600">
                        <span class="text-sm text-slate-700">Remove portrait</span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Core Details -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Identity & Role</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($item['name']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug *</label>
                    <input type="text" name="slug" required value="<?= htmlspecialchars($item['slug']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Designation *</label>
                    <input type="text" name="designation" required value="<?= htmlspecialchars($item['designation']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Board Category</label>
                    <select name="board_category" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                        <option value="Promoter & Executive" <?= $item['board_category'] === 'Promoter & Executive' ? 'selected' : '' ?>>Promoter & Executive</option>
                        <option value="Executive Director" <?= $item['board_category'] === 'Executive Director' ? 'selected' : '' ?>>Executive Director</option>
                        <option value="Independent Director" <?= $item['board_category'] === 'Independent Director' ? 'selected' : '' ?>>Independent Director</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">DIN Number</label>
                    <input type="text" name="din_number" value="<?= htmlspecialchars($item['din_number'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Appointed Since</label>
                    <input type="text" name="appointed_since" value="<?= htmlspecialchars($item['appointed_since'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_founder" value="1" <?= $item['is_founder'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Is Founder / Promoter?</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Media & Bio Summary -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Summaries</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Image Alt Text (SEO)</label>
                    <input type="text" name="portrait_alt" value="<?= htmlspecialchars($item['portrait_alt'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Alma Mater</label>
                    <input type="text" name="alma_mater" value="<?= htmlspecialchars($item['alma_mater'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Summary (Card Preview)</label>
                    <textarea name="short_summary" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($item['short_summary'] ?? '') ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Executive Quote</label>
                    <textarea name="executive_quote" rows="2" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($item['executive_quote'] ?? '') ?></textarea>
                </div>
            </div>
        </section>

        <!-- Advanced JSON Lists -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-bold text-pingaksh-navy">Detailed Profile (JSON Arrays)</h3>
            <div class="grid gap-6 md:grid-cols-2">
                <?php 
                $jsonFields = ['biography_paragraphs', 'key_responsibilities', 'qualifications', 'committees', 'directorships_and_affiliations'];
                foreach ($jsonFields as $field): 
                    $prettyJson = directorsPrettyJson($item[$field] ?? '');
                ?>
                <div class="<?= $field === 'biography_paragraphs' ? 'md:col-span-2' : '' ?>">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy uppercase tracking-wider"><?= str_replace('_', ' ', $field) ?></label>
                    <textarea name="<?= $field ?>" rows="5" class="w-full font-mono text-xs rounded-xl border border-slate-200 bg-slate-900 text-green-400 p-4 outline-none"><?= htmlspecialchars($prettyJson) ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Settings -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Order</label>
                    <input type="number" name="display_order" value="<?= (int)$item['display_order'] ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= $item['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live</span>
                    </label>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover">
                <i data-lucide="save" class="h-4 w-4"></i> Save Updates
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
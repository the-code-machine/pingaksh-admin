<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Add Director Profile';
$activeMenu = 'directors';

$errors = [];

// Pre-fill default empty arrays
$form = [
    'name' => '', 'slug' => '', 'designation' => '', 'board_category' => 'Promoter & Executive',
    'is_founder' => '0', 'din_number' => '', 'appointed_since' => '', 'alma_mater' => '',
    'portrait_alt' => '', 'short_summary' => '', 'executive_quote' => '',
    'qualifications' => '[]', 
    'biography_paragraphs' => '[]',
    'key_responsibilities' => '[]', 
    'committees' => '[]',
    'directorships_and_affiliations' => '[]', 
    'display_order' => '0', 'is_published' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = array_merge($form, $_POST);
    $form['is_founder'] = isset($_POST['is_founder']) ? '1' : '0';
    $form['is_published'] = isset($_POST['is_published']) ? '1' : '0';

    if (empty(trim($form['name']))) $errors[] = 'Name is required.';
    if (empty(trim($form['slug']))) $errors[] = 'URL Slug is required.';

    // Validate JSON hidden fields populated by JS
    $validatedJson = [];
    $jsonFields = ['qualifications', 'biography_paragraphs', 'key_responsibilities', 'committees', 'directorships_and_affiliations'];
    foreach ($jsonFields as $field) {
        try {
            $validatedJson[$field] = directorsValidateJson($form[$field]);
        } catch (Exception $e) {
            $errors[] = "System error parsing " . str_replace('_', ' ', strtoupper($field));
        }
    }

    $imagePath = null;

    if (!$errors) {
        try {
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
                trim($form['slug']), trim($form['name']), trim($form['designation']), $form['board_category'],
                $form['is_founder'], trim($form['din_number']), trim($form['appointed_since']), trim($form['alma_mater']),
                $imagePath, trim($form['portrait_alt']), trim($form['short_summary']), trim($form['executive_quote']),
                $validatedJson['qualifications'], $validatedJson['biography_paragraphs'], $validatedJson['key_responsibilities'],
                $validatedJson['committees'], $validatedJson['directorships_and_affiliations'],
                (int)$form['display_order'], $form['is_published']
            ]);

            header('Location: index.php?created=1');
            exit;
        } catch (PDOException $e) {
            directorsDeleteFile($imagePath); // cleanup on fail
            if ($e->errorInfo[1] === 1062) $errors[] = 'A director with this auto-generated slug already exists.';
            else $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-5xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Board
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Add Director Profile</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <ul class="list-disc pl-5 text-sm text-red-700 font-medium">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="directorForm">
        <!-- Core Details -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold text-pingaksh-navy">Identity & Role</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Full Name *</label>
                    <input type="text" id="nameInput" name="name" required value="<?= htmlspecialchars($form['name']) ?>" placeholder="e.g. Mr. Naresh Goel" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">URL Slug (Auto-Generated) *</label>
                    <input type="text" id="slugInput" name="slug" required readonly value="<?= htmlspecialchars($form['slug']) ?>" placeholder="mr-naresh-goel" class="w-full rounded-xl border border-slate-200 bg-slate-100 text-slate-500 px-3 py-2.5 text-sm cursor-not-allowed outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Designation *</label>
                    <input type="text" name="designation" required value="<?= htmlspecialchars($form['designation']) ?>" placeholder="e.g. Founder & Managing Director" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
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
                    <input type="text" name="din_number" value="<?= htmlspecialchars($form['din_number']) ?>" placeholder="e.g. 01234567" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm font-mono focus:border-pingaksh-terracotta outline-none">
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
                    <input type="file" name="portrait_image" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-pingaksh-sand file:px-4 file:py-2.5 file:font-semibold file:text-pingaksh-navy cursor-pointer">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Image Alt Text (SEO)</label>
                    <input type="text" name="portrait_alt" value="<?= htmlspecialchars($form['portrait_alt']) ?>" placeholder="e.g. Portrait of Mr. Naresh Goel" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Alma Mater</label>
                    <input type="text" name="alma_mater" value="<?= htmlspecialchars($form['alma_mater']) ?>" placeholder="e.g. B.Tech (Civil), IIT Delhi" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Short Summary (Card Preview)</label>
                    <textarea name="short_summary" rows="2" placeholder="Brief 1-2 sentence overview for the directory card..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['short_summary']) ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Executive Quote</label>
                    <textarea name="executive_quote" rows="2" placeholder="e.g. We envision ourselves as global leaders in clean energy..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none"><?= htmlspecialchars($form['executive_quote']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- INTERACTIVE ARRAYS: BIOGRAPHY & QUALIFICATIONS -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-bold text-pingaksh-navy">Detailed Biography & Qualifications</h3>
            <p class="text-xs text-slate-500 mb-6">Build the detailed profile. Each input represents a distinct bullet point or paragraph.</p>
            
            <div class="grid gap-8 md:grid-cols-2">
                <!-- Biography Paragraphs -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-pingaksh-navy">Biography Paragraphs</label>
                        <button type="button" onclick="addStringItem('biography')" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Paragraph</button>
                    </div>
                    <div id="biography_container" class="space-y-3"></div>
                    <input type="hidden" name="biography_paragraphs" id="biography_hidden" value="<?= htmlspecialchars($form['biography_paragraphs']) ?>">
                </div>

                <!-- Qualifications -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-pingaksh-navy">Qualifications</label>
                        <button type="button" onclick="addStringItem('qualifications')" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Degree</button>
                    </div>
                    <div id="qualifications_container" class="space-y-3"></div>
                    <input type="hidden" name="qualifications" id="qualifications_hidden" value="<?= htmlspecialchars($form['qualifications']) ?>">
                </div>

                <!-- Key Responsibilities -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-pingaksh-navy">Key Responsibilities</label>
                        <button type="button" onclick="addStringItem('responsibilities')" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Responsibility</button>
                    </div>
                    <div id="responsibilities_container" class="space-y-3"></div>
                    <input type="hidden" name="key_responsibilities" id="responsibilities_hidden" value="<?= htmlspecialchars($form['key_responsibilities']) ?>">
                </div>

                <!-- Directorships & Affiliations -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-pingaksh-navy">Directorships & Affiliations</label>
                        <button type="button" onclick="addStringItem('affiliations')" class="text-xs font-bold text-pingaksh-terracotta hover:underline inline-flex items-center gap-1"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Affiliation</button>
                    </div>
                    <div id="affiliations_container" class="space-y-3"></div>
                    <input type="hidden" name="directorships_and_affiliations" id="affiliations_hidden" value="<?= htmlspecialchars($form['directorships_and_affiliations']) ?>">
                </div>
            </div>
        </section>

        <!-- INTERACTIVE ARRAYS: COMMITTEES -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-pingaksh-navy">Board Committees</h3>
                <button type="button" onclick="addCommittee()" class="text-xs px-3 py-1.5 rounded-lg bg-pingaksh-navy text-pingaksh-sand hover:bg-pingaksh-navyDark transition font-semibold inline-flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Committee Role
                </button>
            </div>
            
            <div id="committees_container" class="space-y-3"></div>
            <input type="hidden" name="committees" id="committees_hidden" value="<?= htmlspecialchars($form['committees']) ?>">
        </section>

        <!-- Settings -->
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Display Order (Sorting)</label>
                    <input type="number" name="display_order" value="<?= htmlspecialchars($form['display_order']) ?>" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none">
                </div>
                <div class="flex items-center mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_published" value="1" <?= $form['is_published'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-300 accent-pingaksh-terracotta">
                        <span class="text-sm font-bold text-pingaksh-navy">Publish Live on Website</span>
                    </label>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3 sticky bottom-6 z-40 bg-white/80 backdrop-blur-md p-4 rounded-2xl border border-sand-200 shadow-xl">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="button" onclick="submitForm()" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                <i data-lucide="save" class="h-4 w-4"></i> Save Profile
            </button>
        </div>
    </form>
</div>

<script>
// --- Auto-Generate Slug ---
document.getElementById('nameInput').addEventListener('input', (e) => {
    const slug = e.target.value.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-') 
        .replace(/(^-|-$)+/g, '');   
    document.getElementById('slugInput').value = slug;
});

// --- Dynamic Form State Management ---
const state = {
    biography: JSON.parse(document.getElementById('biography_hidden').value || '[]'),
    qualifications: JSON.parse(document.getElementById('qualifications_hidden').value || '[]'),
    responsibilities: JSON.parse(document.getElementById('responsibilities_hidden').value || '[]'),
    affiliations: JSON.parse(document.getElementById('affiliations_hidden').value || '[]'),
    committees: JSON.parse(document.getElementById('committees_hidden').value || '[]')
};

// Config for string arrays
const stringConfigs = {
    biography: { placeholder: "e.g. Mr. Goel brings 40 years of experience...", isTextarea: true },
    qualifications: { placeholder: "e.g. B.Tech in Civil Engineering, IIT", isTextarea: false },
    responsibilities: { placeholder: "e.g. Strategic execution of Hydro Projects", isTextarea: false },
    affiliations: { placeholder: "e.g. Managing Director at PBPL", isTextarea: false }
};

// Render String Arrays
function renderStringList(key) {
    const container = document.getElementById(`${key}_container`);
    const conf = stringConfigs[key];
    container.innerHTML = '';

    if(state[key].length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No items added. Click + to add.</p>`;
    }

    state[key].forEach((val, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2';
        
        const input = document.createElement(conf.isTextarea ? 'textarea' : 'input');
        input.className = 'flex-1 rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm focus:border-pingaksh-terracotta outline-none';
        input.placeholder = conf.placeholder;
        input.value = val;
        if(conf.isTextarea) input.rows = 2;
        
        input.addEventListener('input', (e) => {
            state[key][index] = e.target.value;
            updateHidden(key);
        });

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'p-2.5 mt-0.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition cursor-pointer shrink-0';
        btn.innerHTML = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>`;
        btn.onclick = () => {
            state[key].splice(index, 1);
            renderStringList(key);
            updateHidden(key);
        };

        div.appendChild(input);
        div.appendChild(btn);
        container.appendChild(div);
    });
}

window.addStringItem = function(key) {
    state[key].push('');
    renderStringList(key);
    updateHidden(key);
}

// Render Committees (Object Array)
function renderCommittees() {
    const container = document.getElementById('committees_container');
    container.innerHTML = '';

    if(state.committees.length === 0) {
        container.innerHTML = `<p class="text-xs text-slate-400 italic">No committees added. Click + to add.</p>`;
    }

    state.committees.forEach((comm, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 p-3 rounded-xl border border-sand-200 bg-sand-50';

        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.className = 'flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta';
        nameInput.placeholder = 'Committee Name (e.g. Audit Committee)';
        nameInput.value = comm.name || '';
        nameInput.addEventListener('input', (e) => {
            state.committees[index].name = e.target.value;
            updateHidden('committees');
        });

        const roleSelect = document.createElement('select');
        roleSelect.className = 'w-40 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-pingaksh-terracotta bg-white';
        roleSelect.innerHTML = `
            <option value="Chairperson" ${comm.role === 'Chairperson' ? 'selected' : ''}>Chairperson</option>
            <option value="Member" ${comm.role === 'Member' ? 'selected' : ''}>Member</option>
        `;
        roleSelect.addEventListener('change', (e) => {
            state.committees[index].role = e.target.value;
            updateHidden('committees');
        });

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'p-2 rounded-md bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition cursor-pointer';
        btn.innerHTML = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>`;
        btn.onclick = () => {
            state.committees.splice(index, 1);
            renderCommittees();
            updateHidden('committees');
        };

        div.appendChild(nameInput);
        div.appendChild(roleSelect);
        div.appendChild(btn);
        container.appendChild(div);
    });
}

window.addCommittee = function() {
    state.committees.push({ name: '', role: 'Member' });
    renderCommittees();
    updateHidden('committees');
}

function updateHidden(key) {
    document.getElementById(`${key}_hidden`).value = JSON.stringify(state[key]);
}

window.submitForm = function() {
    // Ensure all state is synced to hidden inputs before native submit
    Object.keys(state).forEach(updateHidden);
    document.getElementById('directorForm').submit();
}

// Initial Render
document.addEventListener('DOMContentLoaded', () => {
    Object.keys(stringConfigs).forEach(renderStringList);
    renderCommittees();
    if(typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
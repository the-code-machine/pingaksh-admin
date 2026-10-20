<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Upload Media';$activeMenu = 'media';

$errors = [];$successCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type =$_POST['type'] ?? 'image';

    if (!in_array($type, ['image', 'video'])) {$errors[] = 'Invalid media type selected.';
    }

    if (empty($_FILES['media_files']['name'][0])) {$errors[] = 'Please select at least one file to upload.';
    }

    if (!$errors) {
        $fileCount = count($_FILES['media_files']['name']);
        
        for ($i = 0; $i <$fileCount; $i++) {$fileArray = [
                'name'     => $_FILES['media_files']['name'][$i],
                'type'     => $_FILES['media_files']['type'][$i],
                'tmp_name' => $_FILES['media_files']['tmp_name'][$i],
                'error'    => $_FILES['media_files']['error'][$i],
                'size'     => $_FILES['media_files']['size'][$i],
            ];

            // Skip empty file slots
            if ($fileArray['error'] === UPLOAD_ERR_NO_FILE) continue;

            try {
                $processed = mediaProcessSingleFile($fileArray,$type);

                $stmt =$pdo->prepare("
                    INSERT INTO media (type, file_name, file_path, mime_type, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([$type,
                    $processed['file_name'],$processed['file_path'],
                    $processed['mime_type'],$processed['file_size']
                ]);

                $successCount++;
            } catch (Exception $e) {$errors[] = "Failed to upload {$fileArray['name']}: " . $e->getMessage();
            }
        }

        if ($successCount > 0 && empty($errors)) {
            header('Location: index.php?created=1');
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Media
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Upload Multiple Files</h2>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="font-bold text-red-800 mb-2">Upload Errors:</p>
            <ul class="list-disc pl-5 text-sm text-red-700">
                <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successCount > 0): ?>
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 text-sm font-bold">
            Successfully uploaded <?= $successCount ?> file(s).
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <section class="rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-8 shadow-sm">
            
            <div class="mb-6">
                <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Upload Type</label>
                <select name="type" id="uploadType" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-3 text-sm focus:border-pingaksh-terracotta outline-none cursor-pointer">
                    <option value="image">Images (JPG, PNG, WebP, SVG)</option>
                    <option value="video">Videos (MP4, WebM, MOV)</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-pingaksh-navy">Select Files (Multiple Allowed) *</label>
                <div class="relative flex w-full flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 py-12 hover:bg-slate-100 transition">
                    <i data-lucide="upload-cloud" class="mb-3 h-10 w-10 text-slate-400"></i>
                    <p class="mb-1 text-sm font-semibold text-pingaksh-navy">Drag & drop files or click to browse</p>
                    <p id="helpText" class="text-xs text-slate-500">Max size: 15MB per image.</p>
                    <input 
                        type="file" 
                        name="media_files[]" 
                        multiple 
                        required 
                        id="fileInput"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                    >
                </div>
                <div id="fileList" class="mt-4 space-y-2"></div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="index.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                <i data-lucide="upload" class="h-4 w-4"></i> Upload All
            </button>
        </div>
    </form>
</div>

<script>
    const typeSelect = document.getElementById('uploadType');
    const helpText = document.getElementById('helpText');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');

// Find this block at the bottom of create.php:
    typeSelect.addEventListener('change', (e) => {
        if (e.target.value === 'video') {
            // Update the text from 150MB to 1GB:
            helpText.textContent = "Max size: 1GB per video. MP4, WebM, MOV.";
            fileInput.accept = "video/mp4,video/webm,video/quicktime";
        } else {
            helpText.textContent = "Max size: 15MB per image. JPG, PNG, WebP, SVG.";
            fileInput.accept = "image/jpeg,image/png,image/webp,image/gif,image/svg+xml";
        }
    });

    // Show selected files preview list
    fileInput.addEventListener('change', () => {
        fileList.innerHTML = '';
        Array.from(fileInput.files).forEach(file => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-3 rounded-lg bg-pingaksh-sandLight border border-pingaksh-sandBorder text-xs font-semibold text-pingaksh-navy';
            div.innerHTML = `<span>${file.name}</span> <span class="text-slate-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>`;
            fileList.appendChild(div);
        });
    });

    // Trigger initial state
    typeSelect.dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
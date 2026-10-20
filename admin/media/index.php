<?php

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Media Library';
$activeMenu = 'media';

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(file_name LIKE ? OR file_path LIKE ? OR mime_type LIKE ?)";
    $searchValue = "%{$search}%";
    array_push($params, $searchValue, $searchValue, $searchValue);
}

if ($type !== '') {
    $where[] = "type = ?";
    $params[] = $type;
}

$sql = "SELECT * FROM media";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$success = null;
if (isset($_GET['created'])) $success = 'Media uploaded successfully.';
if (isset($_GET['updated'])) $success = 'Media details updated.';
if (isset($_GET['deleted'])) $success = 'Media file deleted.';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Global Assets</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Media Manager</h2>
            <p class="mt-1 text-sm text-slate-600">Upload bulk files and copy database paths for use in JSON editors.</p>
        </div>
        <a href="create.php" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-pingaksh-terracottaHover">
            <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Files
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i data-lucide="circle-check" class="h-5 w-5 text-emerald-600"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="mb-6 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 md:grid-cols-12">
            <div class="md:col-span-6">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search file name or path..." class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight py-2.5 pl-10 pr-4 text-sm outline-none focus:border-pingaksh-terracotta">
                </div>
            </div>
            <div class="md:col-span-3">
                <select name="type" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-3 py-2.5 text-sm outline-none focus:border-pingaksh-terracotta">
                    <option value="">All Types</option>
                    <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Images</option>
                    <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Videos</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-3">
                <button type="submit" class="flex-1 rounded-xl bg-pingaksh-navy px-4 py-2.5 text-sm font-semibold text-pingaksh-sand hover:bg-pingaksh-navyDark">Filter</button>
                <?php if ($search || $type): ?>
                    <a href="index.php" class="flex items-center justify-center rounded-xl border border-slate-200 px-3 text-slate-500 hover:bg-slate-50"><i data-lucide="x" class="h-4 w-4"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-pingaksh-sandBorder/60 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 bg-pingaksh-sand/30">
            <h3 class="font-bold text-pingaksh-navy">Uploaded Files</h3>
        </div>

        <?php if (!$items): ?>
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pingaksh-sand/50 text-pingaksh-navy">
                    <i data-lucide="folder-open" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 font-bold text-pingaksh-navy">No media found</h3>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-pingaksh-navy">
                            <th class="px-5 py-3.5">Preview</th>
                            <th class="px-5 py-3.5">File Details</th>
                            <th class="px-5 py-3.5">DB Path (For JSON)</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <?php $resolvedUrl = mediaResolveUrl($item['file_path']); ?>
                            <tr class="transition hover:bg-pingaksh-sandLight">
                                <td class="px-5 py-4">
                                    <a href="<?= htmlspecialchars($resolvedUrl) ?>" target="_blank" class="block relative h-20 w-32 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 group">
                                        <?php if ($item['type'] === 'image'): ?>
                                            <img src="<?= htmlspecialchars($resolvedUrl) ?>" class="h-full w-full object-cover group-hover:scale-105 transition">
                                        <?php else: ?>
                                            <video src="<?= htmlspecialchars($resolvedUrl) ?>" muted class="h-full w-full object-cover"></video>
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/20"><i data-lucide="play-circle" class="h-6 w-6 text-white"></i></div>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-pingaksh-navy truncate max-w-xs" title="<?= htmlspecialchars($item['file_name']) ?>">
                                        <?= htmlspecialchars($item['file_name']) ?>
                                    </p>
                                    <div class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                                        <span class="uppercase font-semibold text-pingaksh-terracotta"><?= htmlspecialchars($item['type']) ?></span>
                                        <span><?= htmlspecialchars($item['mime_type']) ?></span>
                                        <span><?= formatBytes($item['file_size']) ?></span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-slate-400">Added: <?= date('M d, Y H:i', strtotime($item['created_at'])) ?></p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <input type="text" readonly value="<?= htmlspecialchars($item['file_path']) ?>" class="w-full max-w-xs rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 font-mono text-[10px] text-slate-600 outline-none">
                                        <button onclick="copyToClipboard('<?= htmlspecialchars(addslashes($item['file_path'])) ?>')" class="rounded-lg bg-pingaksh-sand px-2.5 py-1.5 text-pingaksh-navy hover:bg-pingaksh-terracotta hover:text-white transition shadow-sm" title="Copy Database Path">
                                            <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="edit.php?id=<?= (int)$item['id'] ?>" class="rounded-lg border border-slate-200 p-2 text-pingaksh-navy hover:bg-pingaksh-navy hover:text-white"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                        <a href="delete.php?id=<?= (int)$item['id'] ?>" onclick="return confirm('Delete this file permanently?')" class="rounded-lg border border-slate-200 p-2 text-red-600 hover:bg-red-600 hover:text-white"><i data-lucide="trash-2" class="h-4 w-4"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Toast Notification for Copy -->
<div id="toast" class="fixed bottom-5 right-5 z-50 translate-y-20 opacity-0 transition-all duration-300 rounded-xl bg-pingaksh-navy px-4 py-3 text-sm font-semibold text-pingaksh-sand shadow-xl flex items-center gap-2">
    <i data-lucide="check-circle-2" class="h-4 w-4 text-emerald-400"></i> Path Copied to Clipboard!
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('toast');
        toast.classList.remove('translate-y-20', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
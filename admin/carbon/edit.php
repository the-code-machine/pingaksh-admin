<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin', 'carbon_editor']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Edit Carbon Metric';
$activeMenu = 'carbon';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM carbon_metrics WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metric_name = trim($_POST['metric_name']);
    $asset_tag = trim($_POST['asset_tag']);
    $base_value = (float)$_POST['base_value'];
    $rate_per_second = (float)$_POST['rate_per_second'];
    $base_timestamp = trim($_POST['base_timestamp']);
    $unit = trim($_POST['unit']);
    $status = $_POST['status'];

    if (!$metric_name || !$base_timestamp) $errors[] = "Metric Name and Base Timestamp are required.";

    if (!$errors) {
        $upd = $pdo->prepare("UPDATE carbon_metrics SET metric_name=?, asset_tag=?, base_value=?, rate_per_second=?, base_timestamp=?, unit=?, status=? WHERE id=?");
        $upd->execute([$metric_name, $asset_tag, $base_value, $rate_per_second, $base_timestamp, $unit, $status, $id]);
        header('Location: index.php?success=Metric+updated');
        exit;
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl pb-20">
    <div class="mb-6"><a href="index.php" class="text-sm font-bold text-pingaksh-terracotta">← Back to Matrix</a></div>
    
    <form method="POST" class="space-y-6 rounded-2xl border border-sand-300 bg-white p-8 shadow-sm">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-xs font-bold uppercase mb-1">Metric Title (e.g. Total CO₂ Displaced)</label>
                <input type="text" name="metric_name" required value="<?= htmlspecialchars($item['metric_name']) ?>" class="w-full rounded-xl border border-slate-200 bg-sand-50 p-3 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase mb-1">Asset Tag (e.g. UBHP_HYDRO)</label>
                <input type="text" name="asset_tag" required value="<?= htmlspecialchars($item['asset_tag']) ?>" class="w-full rounded-xl border border-slate-200 bg-sand-50 p-3 text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase mb-1">Unit (e.g. MT CO₂)</label>
                <input type="text" name="unit" required value="<?= htmlspecialchars($item['unit']) ?>" class="w-full rounded-xl border border-slate-200 bg-sand-50 p-3 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase mb-1">Base Value (Starting Point)</label>
                <input type="number" step="0.0001" name="base_value" required value="<?= htmlspecialchars($item['base_value']) ?>" class="w-full rounded-xl border border-slate-200 bg-sand-50 p-3 text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase mb-1">Base Timestamp (Format: YYYY-MM-DD HH:MM:SS)</label>
                <input type="text" name="base_timestamp" required value="<?= htmlspecialchars($item['base_timestamp']) ?>" class="w-full rounded-xl border border-slate-200 bg-sand-50 p-3 text-sm font-mono">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold uppercase mb-1 text-emerald-600">Growth Rate Per Second (Formula Engine)</label>
                <input type="number" step="0.00000001" name="rate_per_second" required value="<?= htmlspecialchars($item['rate_per_second']) ?>" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-mono">
                <p class="text-[10px] text-slate-500 mt-1">Example: 0.006944 MT/sec = 600 MT per day.</p>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold uppercase mb-1">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-sand-50 p-3 text-sm">
                    <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active (API Calculates)</option>
                    <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                </select>
            </div>
        </div>
        <button type="submit" class="w-full rounded-xl bg-pingaksh-navy p-3 text-sm font-bold text-white mt-4">Save Engine Parameters</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
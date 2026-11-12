<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin', 'carbon_editor']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Add Carbon Metric Tracker';
$activeMenu = 'carbon';

$errors = [];
$item = [
    'metric_name'     => '',
    'asset_tag'       => 'UBHP_HYDRO',
    'base_value'      => '217800.0000',
    'rate_per_second' => '0.00694444',
    'base_timestamp'  => date('Y-m-d H:i:s'),
    'unit'            => 'MT CO2',
    'status'          => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['metric_name']     = trim($_POST['metric_name'] ?? '');
    $item['asset_tag']       = strtoupper(trim($_POST['asset_tag'] ?? ''));
    $item['base_value']      = trim($_POST['base_value'] ?? '0');
    $item['rate_per_second'] = trim($_POST['rate_per_second'] ?? '0');
    $item['base_timestamp']  = trim($_POST['base_timestamp'] ?? date('Y-m-d H:i:s'));
    $item['unit']            = trim($_POST['unit'] ?? 'MT CO2');
    $item['status']          = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    // Convert HTML5 datetime-local format (YYYY-MM-DDTHH:MM) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
    $item['base_timestamp'] = str_replace('T', ' ', $item['base_timestamp']);
    if (strlen($item['base_timestamp']) === 16) {
        $item['base_timestamp'] .= ':00';
    }

    if ($item['metric_name'] === '') {
        $errors[] = 'Metric Title is required.';
    }
    if ($item['asset_tag'] === '') {
        $errors[] = 'Asset Tag is required.';
    }
    if (!is_numeric($item['base_value'])) {
        $errors[] = 'Base Value must be a valid number.';
    }
    if (!is_numeric($item['rate_per_second'])) {
        $errors[] = 'Growth Rate Per Second must be a valid number.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO carbon_metrics (
                    metric_name, asset_tag, base_value, rate_per_second, base_timestamp, unit, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $item['metric_name'],
                $item['asset_tag'],
                (float)$item['base_value'],
                (float)$item['rate_per_second'],
                $item['base_timestamp'],
                $item['unit'],
                $item['status']
            ]);

            header('Location: index.php?success=' . urlencode('Carbon metric tracker created successfully.'));
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-3xl pb-20">
    <div class="mb-6">
        <a href="index.php" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-pingaksh-terracotta hover:text-pingaksh-navy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Carbon Telemetry Matrix
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Add Carbon Metric Tracker</h2>
        <p class="mt-1 text-sm text-slate-600">Configure a real-time mathematical carbon offset counter for the frontend ESG ticker.</p>
    </div>

    <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
            <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6 rounded-2xl border border-pingaksh-sandBorder/60 bg-white p-8 shadow-sm">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Metric Title *</label>
                <input
                    type="text"
                    name="metric_name"
                    required
                    placeholder="e.g., Cumulative CO₂ Displaced (Sarju Hydro Cascade)"
                    value="<?= htmlspecialchars($item['metric_name']) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-3 text-sm outline-none focus:border-pingaksh-terracotta"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Asset Tag *</label>
                <input
                    type="text"
                    name="asset_tag"
                    required
                    placeholder="e.g., UBHP_HYDRO or PBPL_ETHANOL"
                    value="<?= htmlspecialchars($item['asset_tag']) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-3 font-mono text-sm uppercase outline-none focus:border-pingaksh-terracotta"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Measurement Unit *</label>
                <input
                    type="text"
                    name="unit"
                    required
                    placeholder="e.g., MT CO₂ or kWh"
                    value="<?= htmlspecialchars($item['unit']) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-3 text-sm outline-none focus:border-pingaksh-terracotta"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Base Value (Starting Counter) *</label>
                <input
                    type="number"
                    step="0.0001"
                    name="base_value"
                    required
                    value="<?= htmlspecialchars($item['base_value']) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-3 font-mono text-sm outline-none focus:border-pingaksh-terracotta"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Base Timestamp (YYYY-MM-DD HH:MM:SS) *</label>
                <input
                    type="text"
                    name="base_timestamp"
                    required
                    value="<?= htmlspecialchars($item['base_timestamp']) ?>"
                    class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-3 font-mono text-sm outline-none focus:border-pingaksh-terracotta"
                >
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-emerald-700">Increment Rate Per Second (Formula Engine) *</label>
                <input
                    type="number"
                    step="0.00000001"
                    name="rate_per_second"
                    required
                    value="<?= htmlspecialchars($item['rate_per_second']) ?>"
                    class="w-full rounded-xl border border-emerald-300 bg-emerald-50/60 px-4 py-3 font-mono text-sm text-emerald-900 outline-none focus:border-emerald-600"
                >
                <p class="mt-1.5 text-xs text-slate-500">
                    Formula: <code class="font-mono text-pingaksh-navy">Live Value = Base Value + ((Current Time - Base Timestamp) × Rate/Sec)</code>.
                    (e.g., <code class="font-mono">0.00694444</code>/sec = ~600 MT/day).
                </p>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-pingaksh-navy">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 bg-pingaksh-sandLight px-4 py-3 text-sm outline-none focus:border-pingaksh-terracotta">
                    <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active (Live in API)</option>
                    <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Paused / Hidden)</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="index.php" class="rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                <i data-lucide="save" class="h-4 w-4"></i> Create Metric Tracker
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
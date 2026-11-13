<?php

require_once __DIR__ . '/../../includes/auth.php';
requireRole(['super_admin', 'carbon_editor']);
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Carbon Savings Workbook';
$activeMenu = 'carbon';

$activeTab = $_GET['tab'] ?? 'carbon_saved';
$success = null;
$error = null;

// Handle Form Submissions for the 3 Editable Sheets
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // 1. Update Ethanol Sales Sheet
        if ($action === 'save_ethanol') {
            if (!empty($_POST['litres']) && is_array($_POST['litres'])) {
                $upd = $pdo->prepare("UPDATE ethanol_sales_log SET litres_sold = ? WHERE id = ?");
                foreach ($_POST['litres'] as $id => $val) {
                    $cleanVal = (float)str_replace(',', '', trim($val));
                    $upd->execute([$cleanVal, (int)$id]);
                }
            }
            if (!empty($_POST['new_date']) && isset($_POST['new_litres']) && $_POST['new_litres'] !== '') {
                $newDate = trim($_POST['new_date']);
                $newLitres = (float)str_replace(',', '', trim($_POST['new_litres']));
                $ins = $pdo->prepare("INSERT INTO ethanol_sales_log (entry_label, sale_date, litres_sold, is_baseline) VALUES (?, ?, ?, 0)");
                $ins->execute([$newDate, $newDate, $newLitres]);
            }
            $activeTab = 'ethanol_sales';
            $success = 'Ethanol Sales sheet updated. Carbon Saved recalculated automatically.';
        }

        // 2 & 3. Update Sarju-III or Sarju-II Monthly Units
        if ($action === 'save_hydro') {
            $plant = ($_POST['plant_code'] ?? '') === 'SARJU_2' ? 'SARJU_2' : 'SARJU_3';
            if (!empty($_POST['units']) && is_array($_POST['units'])) {
                $upsert = $pdo->prepare("
                    INSERT INTO hydro_generation_units (plant_code, year, month, units_kwh)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE units_kwh = VALUES(units_kwh)
                ");
                foreach ($_POST['units'] as $yr => $months) {
                    foreach ($months as $m => $val) {
                        $cleanVal = $val === '' ? 0.00 : (float)str_replace(',', '', trim($val));
                        $upsert->execute([$plant, (int)$yr, (int)$m, $cleanVal]);
                    }
                }
            }
            if (!empty($_POST['add_year'])) {
                $newYr = (int)$_POST['add_year'];
                if ($newYr >= 2010 && $newYr <= 2050) {
                    $insYr = $pdo->prepare("INSERT IGNORE INTO hydro_generation_units (plant_code, year, month, units_kwh) VALUES (?, ?, ?, 0)");
                    for ($m = 1; $m <= 12; $m++) {
                        $insYr->execute([$plant, $newYr, $m]);
                    }
                }
            }
            $activeTab = $plant === 'SARJU_3' ? 'sarju_3' : 'sarju_2';
            $success = ($plant === 'SARJU_3' ? 'Sarju-III' : 'Sarju-II') . ' units updated. Carbon & GWh recalculated automatically.';
        }
    } catch (Exception $e) {
        $error = 'Error saving data: ' . $e->getMessage();
    }
}

// Fetch Sheet 1: Ethanol Sales
$ethanolRows = $pdo->query("SELECT * FROM ethanol_sales_log ORDER BY is_baseline DESC, sale_date ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalEthanolLitres = 0.0;
foreach ($ethanolRows as $r) {
    $totalEthanolLitres += (float)$r['litres_sold'];
}

// Fetch Sheet 2 & 3 Helper
function fetchHydroGrid(PDO $pdo, string $plantCode): array {
    $stmt = $pdo->prepare("SELECT year, month, units_kwh FROM hydro_generation_units WHERE plant_code = ? ORDER BY year ASC, month ASC");
    $stmt->execute([$plantCode]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $grid = [];
    $total = 0.0;
    foreach ($rows as $r) {
        $y = (int)$r['year'];
        $m = (int)$r['month'];
        $v = (float)$r['units_kwh'];
        $grid[$y][$m] = $v;
        $total += $v;
    }
    return ['grid' => $grid, 'total' => $total];
}

$sarju3 = fetchHydroGrid($pdo, 'SARJU_3');
$sarju2 = fetchHydroGrid($pdo, 'SARJU_2');

// Sheet 4: Locked Auto-Calculations (Exact Excel Formulas)
$e0EmissionsGrams = 2348;
$e20EmissionsGrams = 2182;
$savedPerLitreE20Grams = $e0EmissionsGrams - $e20EmissionsGrams; // 166 g
$carbonSavedPerLitreEthanolKg = ($savedPerLitreE20Grams * 5) / 1000; // 0.83 kg/L
$ethanolCarbonSavedTonnes = ($totalEthanolLitres * $carbonSavedPerLitreEthanolKg) / 1000;

$totalHydroKwh = $sarju3['total'] + $sarju2['total'];
$totalHydroGwh = $totalHydroKwh / 1000000; // Yellow Showcase Field 1
$coalEmissionFactor = 0.969; // kg CO2 / kWh
$hydroCarbonSavedTonnes = ($totalHydroKwh * $coalEmissionFactor) / 1000;

$totalPingakshCarbonSavedTonnes = $ethanolCarbonSavedTonnes + $hydroCarbonSavedTonnes; // Yellow Showcase Field 2

$monthNames = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mx-auto max-w-7xl pb-20">
    <!-- Top Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-pingaksh-terracotta">Automated ESG Calculation Workbook</p>
            <h2 class="text-2xl font-bold tracking-tight text-pingaksh-navy">Carbon Reduction & Generation Sheets</h2>
            <p class="mt-1 text-sm text-slate-600">Feed operational data into the first 3 sheets. Sheet 4 is locked and auto-calculates live website metrics.</p>
        </div>
        <a href="<?= BASE_URL ?>/api/carbon.php" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-pingaksh-navy shadow-2xs hover:bg-slate-50">
            <i data-lucide="external-link" class="h-4 w-4 text-pingaksh-terracotta"></i> View Live JSON API
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-600"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- 4-Sheet Workbook Tabs -->
    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        <a href="?tab=ethanol_sales" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition <?= $activeTab === 'ethanol_sales' ? 'bg-pingaksh-navy text-pingaksh-sand shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' ?>">
            <i data-lucide="droplets" class="h-4 w-4"></i> 1. Ethanol Sales (Editable)
        </a>
        <a href="?tab=sarju_3" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition <?= $activeTab === 'sarju_3' ? 'bg-pingaksh-navy text-pingaksh-sand shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' ?>">
            <i data-lucide="zap" class="h-4 w-4"></i> 2. Sarju 3 Units Generated (Editable)
        </a>
        <a href="?tab=sarju_2" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition <?= $activeTab === 'sarju_2' ? 'bg-pingaksh-navy text-pingaksh-sand shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' ?>">
            <i data-lucide="zap" class="h-4 w-4"></i> 3. Sarju 2 Units Generated (Editable)
        </a>
        <a href="?tab=carbon_saved" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition <?= $activeTab === 'carbon_saved' ? 'bg-amber-400 text-pingaksh-navy shadow-sm ring-2 ring-amber-500/40' : 'bg-amber-50 text-amber-900 border border-amber-300 hover:bg-amber-100' ?>">
            <i data-lucide="lock" class="h-4 w-4"></i> 4. Carbon Saved (LOCKED · Auto-Calculated)
        </a>
    </div>

    <!-- ===================================================================
         SHEET 4: CARBON SAVED (LOCKED / READ-ONLY SUMMARY SHEET)
    =================================================================== -->
    <?php if ($activeTab === 'carbon_saved'): ?>
        <div class="space-y-6">
            <!-- Yellow Highlighted Website Showcase Banner -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border-2 border-amber-400 bg-amber-50/90 p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-amber-300/80 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider text-amber-950">Yellow Showcase Field 1 · Hydro Page</span>
                        <i data-lucide="lock" class="h-4 w-4 text-amber-800"></i>
                    </div>
                    <p class="mt-4 text-xs font-bold uppercase tracking-wider text-amber-900">Total Energy Generated (Hydro Operations)</p>
                    <p class="mt-1 font-mono text-4xl font-extrabold text-pingaksh-navy">
                        <?= number_format($totalHydroGwh, 2) ?> <span class="text-xl font-bold text-amber-900">GWh</span>
                    </p>
                    <p class="mt-2 font-mono text-xs text-amber-900/80">
                        Exact Value: <?= number_format($totalHydroGwh, 6) ?> GWh (<?= number_format($totalHydroKwh, 2) ?> kWh)
                    </p>
                </div>

                <div class="rounded-2xl border-2 border-amber-400 bg-amber-50/90 p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-amber-300/80 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider text-amber-950">Yellow Showcase Field 2 · Group Total</span>
                        <i data-lucide="lock" class="h-4 w-4 text-amber-800"></i>
                    </div>
                    <p class="mt-4 text-xs font-bold uppercase tracking-wider text-amber-900">Total Carbon Saved via Pingaksh Renewables</p>
                    <p class="mt-1 font-mono text-4xl font-extrabold text-pingaksh-navy">
                        <?= number_format($totalPingakshCarbonSavedTonnes, 2) ?> <span class="text-xl font-bold text-amber-900">Tonnes</span>
                    </p>
                    <p class="mt-2 font-mono text-xs text-amber-900/80">
                        Exact Value: <?= number_format($totalPingakshCarbonSavedTonnes, 5) ?> Tonnes CO₂
                    </p>
                </div>
            </div>

            <!-- Read-Only Spreadsheet Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="lock" class="h-4 w-4 text-pingaksh-terracotta"></i>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-pingaksh-navy">Sheet 4: Carbon Saved (Protected Formula Sheet)</h3>
                    </div>
                    <span class="text-xs text-slate-500">Read-Only — Derived automatically from Sheets 1, 2 & 3</span>
                </div>

                <div class="p-6">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-200 text-xs font-bold uppercase text-slate-500">
                                <th class="py-3 px-4">Parameter / Section</th>
                                <th class="py-3 px-4">Calculated / Constant Value</th>
                                <th class="py-3 px-4">Unit / Formula Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr class="bg-slate-50/70 font-bold text-pingaksh-navy">
                                <td class="py-3 px-4" colspan="3">1. ETHANOL DIVISION (PBPL)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">E0 : Normal Fuel Emission</td>
                                <td class="py-3 px-4 font-mono">2,348</td>
                                <td class="py-3 px-4 text-xs text-slate-500">Grams CO₂ per Litre</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">E20 Blended Fuel Emission</td>
                                <td class="py-3 px-4 font-mono">2,182</td>
                                <td class="py-3 px-4 text-xs text-slate-500">166g saved per Litre of E20 (1L Ethanol makes 5L E20 = 5 × 166g = 830g)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-semibold">Sales to Date (From Sheet 1)</td>
                                <td class="py-3 px-4 font-mono font-bold text-pingaksh-navy"><?= number_format($totalEthanolLitres, 0) ?></td>
                                <td class="py-3 px-4 text-xs font-semibold">Litres</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">Carbon Saved per Litre of Ethanol</td>
                                <td class="py-3 px-4 font-mono">0.83</td>
                                <td class="py-3 px-4 text-xs text-slate-500">KGs / Litre</td>
                            </tr>
                            <tr class="bg-emerald-50/60">
                                <td class="py-3 px-4 font-bold text-emerald-950">Tailpipe Emissions Saved to Date (Ethanol)</td>
                                <td class="py-3 px-4 font-mono font-bold text-emerald-700"><?= number_format($ethanolCarbonSavedTonnes, 2) ?></td>
                                <td class="py-3 px-4 text-xs font-bold text-emerald-800">Tonnes</td>
                            </tr>

                            <tr class="bg-slate-50/70 font-bold text-pingaksh-navy">
                                <td class="py-3 px-4" colspan="3">2. HYDRO DIVISION (UBHP — SARJU II & SARJU III)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">Sarju-III Total Units (From Sheet 2)</td>
                                <td class="py-3 px-4 font-mono"><?= number_format($sarju3['total'], 2) ?></td>
                                <td class="py-3 px-4 text-xs text-slate-500">kWh</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">Sarju-II Total Units (From Sheet 3)</td>
                                <td class="py-3 px-4 font-mono"><?= number_format($sarju2['total'], 2) ?></td>
                                <td class="py-3 px-4 text-xs text-slate-500">kWh</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-semibold">Total Hydro Units Combined</td>
                                <td class="py-3 px-4 font-mono font-bold"><?= number_format($totalHydroKwh, 2) ?></td>
                                <td class="py-3 px-4 text-xs font-semibold">KWH</td>
                            </tr>
                            <tr class="bg-amber-100/80 border-y-2 border-amber-300">
                                <td class="py-3.5 px-4 font-extrabold text-pingaksh-navy">★ Energy Generated (Showcase on Hydro Page)</td>
                                <td class="py-3.5 px-4 font-mono text-lg font-extrabold text-pingaksh-navy"><?= number_format($totalHydroGwh, 6) ?></td>
                                <td class="py-3.5 px-4 text-xs font-extrabold text-amber-950">GWH</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">Coal Energy Emission Factor</td>
                                <td class="py-3 px-4 font-mono">0.969</td>
                                <td class="py-3 px-4 text-xs text-slate-500">kg CO₂ / kWh</td>
                            </tr>
                            <tr class="bg-emerald-50/60">
                                <td class="py-3 px-4 font-bold text-emerald-950">Carbon Saved via Hydro Operations</td>
                                <td class="py-3 px-4 font-mono font-bold text-emerald-700"><?= number_format($hydroCarbonSavedTonnes, 5) ?></td>
                                <td class="py-3 px-4 text-xs font-bold text-emerald-800">Tonnes</td>
                            </tr>

                            <tr class="bg-amber-200/90 border-t-2 border-amber-400">
                                <td class="py-4 px-4 text-base font-extrabold text-pingaksh-navy">★ Total Carbon Saved via Pingaksh Renewables</td>
                                <td class="py-4 px-4 font-mono text-xl font-extrabold text-pingaksh-navy"><?= number_format($totalPingakshCarbonSavedTonnes, 5) ?></td>
                                <td class="py-4 px-4 text-sm font-extrabold text-pingaksh-navy">Tonnes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         SHEET 1: ETHANOL SALES (EDITABLE)
    =================================================================== -->
    <?php if ($activeTab === 'ethanol_sales'): ?>
        <form method="POST" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <input type="hidden" name="action" value="save_ethanol">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-pingaksh-navy">Sheet 1: Ethanol Sales (Litres)</h3>
                    <p class="text-xs text-slate-500">Total Sales Auto-Sum: <strong class="font-mono text-pingaksh-terracotta"><?= number_format($totalEthanolLitres, 0) ?> Litres</strong></p>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                    <i data-lucide="save" class="h-4 w-4"></i> Save Ethanol Sales
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full max-w-3xl text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                            <th class="py-2.5 px-3">Date / Period</th>
                            <th class="py-2.5 px-3">Ethanol Sold (Litres)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($ethanolRows as $row): ?>
                            <tr class="<?= $row['is_baseline'] ? 'bg-sand-50 font-bold' : '' ?>">
                                <td class="py-2.5 px-3 font-mono text-xs text-pingaksh-navy">
                                    <?= htmlspecialchars($row['entry_label']) ?>
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="litres[<?= (int)$row['id'] ?>]"
                                        value="<?= htmlspecialchars((string)$row['litres_sold']) ?>"
                                        class="w-full max-w-xs rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-mono text-sm outline-none focus:border-pingaksh-terracotta"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Add New Date Entry -->
                        <tr class="bg-emerald-50/40">
                            <td class="py-3 px-3">
                                <input type="date" name="new_date" placeholder="Add new date" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-mono">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="new_litres" placeholder="Enter Litres for new date..." class="w-full max-w-xs rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-mono text-sm">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>

    <!-- ===================================================================
         SHEET 2 & 3: SARJU-III & SARJU-II MONTHLY UNITS (EDITABLE)
    =================================================================== -->
    <?php if ($activeTab === 'sarju_3' || $activeTab === 'sarju_2'):
        $isSarju3 = ($activeTab === 'sarju_3');
        $plantCode = $isSarju3 ? 'SARJU_3' : 'SARJU_2';
        $plantTitle = $isSarju3 ? 'Sheet 2: SARJU-III Monthly Units Generated (kWh)' : 'Sheet 3: SARJU-II Monthly Units Generated (kWh)';
        $plantData = $isSarju3 ? $sarju3 : $sarju2;
        $years = array_keys($plantData['grid']);
    ?>
        <form method="POST" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <input type="hidden" name="action" value="save_hydro">
            <input type="hidden" name="plant_code" value="<?= $plantCode ?>">

            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-pingaksh-navy"><?= htmlspecialchars($plantTitle) ?></h3>
                    <p class="text-xs text-slate-500">Total Units to Date: <strong class="font-mono text-pingaksh-terracotta"><?= number_format($plantData['total'], 2) ?> kWh</strong></p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="number" name="add_year" placeholder="Add Year (e.g. 2027)" class="w-40 rounded-xl border border-slate-200 px-3 py-2 text-xs font-mono">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-pingaksh-terracotta px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-pingaksh-terracottaHover cursor-pointer">
                        <i data-lucide="save" class="h-4 w-4"></i> Save Monthly Units
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-slate-200 bg-slate-50 font-bold uppercase text-pingaksh-navy">
                            <th class="py-3 px-2">Month</th>
                            <?php foreach ($years as $yr): ?>
                                <th class="py-3 px-2 font-mono text-right"><?= $yr ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($monthNames as $mNum => $mLabel): ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-2 px-2 font-bold text-pingaksh-navy"><?= $mLabel ?></td>
                                <?php foreach ($years as $yr):
                                    $val = $plantData['grid'][$yr][$mNum] ?? 0.00;
                                ?>
                                    <td class="p-1">
                                        <input
                                            type="number"
                                            step="0.01"
                                            name="units[<?= $yr ?>][<?= $mNum ?>]"
                                            value="<?= $val > 0 ? htmlspecialchars((string)$val) : '' ?>"
                                            placeholder="-"
                                            class="w-full rounded border border-slate-200 px-2 py-1.5 text-right font-mono text-[11px] focus:border-pingaksh-terracotta focus:bg-amber-50/30 outline-none"
                                        >
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-300 bg-slate-100 font-bold text-pingaksh-navy">
                            <td class="py-3 px-2">Yearly Total</td>
                            <?php foreach ($years as $yr):
                                $yrSum = array_sum($plantData['grid'][$yr] ?? []);
                            ?>
                                <td class="py-3 px-2 text-right font-mono text-[11px]"><?= number_format($yrSum, 2) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
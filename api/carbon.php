<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // 1. Sum Ethanol Sales (Sheet 1)
    $ethanolLitres = (float)$pdo->query("SELECT COALESCE(SUM(litres_sold), 0) FROM ethanol_sales_log")->fetchColumn();

    // 2. Sum Sarju-III & Sarju-II Units (Sheets 2 & 3)
    $stmtHydro = $pdo->query("
        SELECT plant_code, COALESCE(SUM(units_kwh), 0) AS total_kwh
        FROM hydro_generation_units
        GROUP BY plant_code
    ");
    $hydroTotals = ['SARJU_3' => 0.0, 'SARJU_2' => 0.0];
    foreach ($stmtHydro->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $hydroTotals[$row['plant_code']] = (float)$row['total_kwh'];
    }

    // 3. Sheet 4 Locked Formulas
    $carbonSavedPerLitreKg = 0.83; // (2348g - 2182g) * 5 / 1000
    $ethanolCarbonSavedTonnes = ($ethanolLitres * $carbonSavedPerLitreKg) / 1000.0;

    $totalHydroKwh = $hydroTotals['SARJU_3'] + $hydroTotals['SARJU_2'];
    $hydroEnergyGeneratedGwh = $totalHydroKwh / 1000000.0; // Yellow Showcase Field 1
    $coalEmissionFactor = 0.969; // kg CO2 / kWh
    $hydroCarbonSavedTonnes = ($totalHydroKwh * $coalEmissionFactor) / 1000.0;

    $totalCarbonSavedTonnes = $ethanolCarbonSavedTonnes + $hydroCarbonSavedTonnes; // Yellow Showcase Field 2

    echo json_encode([
        'success' => true,
        'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'showcase_yellow_fields' => [
            'hydro_energy_generated_gwh' => round($hydroEnergyGeneratedGwh, 2),
            'hydro_energy_generated_gwh_exact' => round($hydroEnergyGeneratedGwh, 6),
            'total_carbon_saved_tonnes' => round($totalCarbonSavedTonnes, 2),
            'total_carbon_saved_tonnes_exact' => round($totalCarbonSavedTonnes, 5),
        ],
        'breakdown' => [
            'ethanol' => [
                'sales_to_date_litres' => $ethanolLitres,
                'factor_kg_per_litre' => $carbonSavedPerLitreKg,
                'tailpipe_carbon_saved_tonnes' => round($ethanolCarbonSavedTonnes, 2),
            ],
            'hydro' => [
                'sarju_3_kwh' => $hydroTotals['SARJU_3'],
                'sarju_2_kwh' => $hydroTotals['SARJU_2'],
                'total_units_kwh' => $totalHydroKwh,
                'energy_generated_gwh' => round($hydroEnergyGeneratedGwh, 6),
                'coal_emission_factor' => $coalEmissionFactor,
                'hydro_carbon_saved_tonnes' => round($hydroCarbonSavedTonnes, 5),
            ],
            'total_carbon_saved_tonnes' => round($totalCarbonSavedTonnes, 5),
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to compute carbon savings workbook.']);
}
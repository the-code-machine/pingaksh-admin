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
    $fiscalYears = ['FY 2026-27', 'FY 2025-26', 'FY 2024-25'];

    // 1. Annual Reports
    $annualRows = $pdo->query("SELECT * FROM financial_annual_reports ORDER BY iso_date DESC")->fetchAll(PDO::FETCH_ASSOC);
    $annualReports = [];
    foreach ($annualRows as $r) {
        $fy = $r['fiscal_year'];
        $annualReports[$fy] = [
            'fiscalYear' => $fy,
            'themeTitle' => $r['theme_title'],
            'subtitle' => $r['subtitle'],
            'releaseDate' => $r['release_date'],
            'isoDate' => $r['iso_date'],
            'coverImage' => $r['cover_image'],
            'coverAlt' => $r['cover_alt'],
            'pageCount' => $r['page_count'],
            'fileSize' => $r['file_size'],
            'fullReportHref' => $r['full_report_href'],
            'mgt7ReturnHref' => $r['mgt7_return_href'],
            'standaloneAccountsHref' => $r['standalone_accounts_href'],
            'keyPerformanceMetrics' => [
                [
                    'metric' => $r['kpi_1_metric'],
                    'value' => $r['kpi_1_value'],
                    'growth' => $r['kpi_1_growth'],
                    'barPercentage' => (int)$r['kpi_1_bar'],
                ],
                [
                    'metric' => $r['kpi_2_metric'],
                    'value' => $r['kpi_2_value'],
                    'growth' => $r['kpi_2_growth'],
                    'barPercentage' => (int)$r['kpi_2_bar'],
                ],
                [
                    'metric' => $r['kpi_3_metric'],
                    'value' => $r['kpi_3_value'],
                    'growth' => $r['kpi_3_growth'],
                    'barPercentage' => (int)$r['kpi_3_bar'],
                ],
            ],
        ];
    }

    // 2. Quarterly Matrix
    $packRows = $pdo->query("SELECT * FROM financial_quarterly_packs ORDER BY iso_date DESC, FIELD(quarter, 'Q4','Q3','Q2','Q1')")->fetchAll(PDO::FETCH_ASSOC);
    $docRows = $pdo->query("SELECT * FROM financial_quarterly_documents ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $docsByPack = [];
    foreach ($docRows as $d) {
        $docsByPack[(int)$d['quarter_pack_id']][] = [
            'id' => 'doc-' . (int)$d['id'],
            'type' => $d['doc_type'],
            'title' => $d['title'],
            'fileSize' => $d['file_size'],
            'href' => $d['href'],
        ];
    }

    $quarterlyMatrix = [
        'FY 2026-27' => [],
        'FY 2025-26' => [],
        'FY 2024-25' => [],
    ];

    foreach ($packRows as $p) {
        $fy = $p['fiscal_year'];
        $highlights = [];
        if (!empty($p['highlight_1_label']) || !empty($p['highlight_1_value'])) {
            $highlights[] = ['label' => $p['highlight_1_label'], 'value' => $p['highlight_1_value']];
        }
        if (!empty($p['highlight_2_label']) || !empty($p['highlight_2_value'])) {
            $highlights[] = ['label' => $p['highlight_2_label'], 'value' => $p['highlight_2_value']];
        }

        $quarterlyMatrix[$fy][] = [
            'quarter' => $p['quarter'],
            'periodEnded' => $p['period_ended'],
            'releaseDate' => $p['release_date'],
            'isoDate' => $p['iso_date'],
            'status' => $p['status'],
            'highlights' => $highlights,
            'documents' => $docsByPack[(int)$p['id']] ?? [],
        ];
    }

    // 3. Subsidiary Financials
    $subRows = $pdo->query("SELECT * FROM financial_subsidiaries ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $subsidiaryFinancials = [];
    foreach ($subRows as $s) {
        $subsidiaryFinancials[] = [
            'entityName' => $s['entity_name'],
            'spvRole' => $s['spv_role'],
            'latestAuditStatus' => $s['latest_audit_status'],
            'reportTitle' => $s['report_title'],
            'fileSize' => $s['file_size'],
            'href' => $s['href'],
        ];
    }

    echo json_encode([
        'success' => true,
        'fiscalYears' => $fiscalYears,
        'annualReports' => $annualReports,
        'quarterlyMatrix' => $quarterlyMatrix,
        'subsidiaryFinancials' => $subsidiaryFinancials,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load financial reports.']);
}
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
    $stmt = $pdo->query("SELECT * FROM carbon_metrics WHERE status = 'active' ORDER BY created_at ASC");
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nowTimestamp = time();
    $serverTimeIso = gmdate('Y-m-d\TH:i:s\Z', $nowTimestamp); // UTC
    
    $responseData = [];

    foreach ($metrics as $m) {
        $baseTime = strtotime($m['base_timestamp']);
        $diffSeconds = max(0, $nowTimestamp - $baseTime); // Prevent negative time if clock skew
        
        // Exact real-time calculation: Base + (Seconds Elapsed * Rate)
        $realTimeValue = (float)$m['base_value'] + ($diffSeconds * (float)$m['rate_per_second']);

        $responseData[] = [
            'id' => (int)$m['id'],
            'asset_tag' => $m['asset_tag'],
            'metric_name' => $m['metric_name'],
            'unit' => $m['unit'],
            
            // The exact calculated value at the time the API was hit
            'current_calculated_value' => round($realTimeValue, 6),
            
            // Provide parameters for frontend smooth animation (setInterval)
            'animation_params' => [
                'rate_per_second' => (float)$m['rate_per_second'],
                'base_timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z', $baseTime),
                'base_value' => (float)$m['base_value']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'server_time' => $serverTimeIso,
        'data' => $responseData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Telemetry calculation failed.']);
}
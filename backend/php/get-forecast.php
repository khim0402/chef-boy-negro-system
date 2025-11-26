<?php
// backend/php/get-forecast.php
require_once(__DIR__ . '/db.php');
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'view';

// Optional: run Python pipeline if requested
$pipelineInfo = ["ran" => false];
if ($action === 'run') {
    $pythonPathCandidates = ['python3', 'python'];
    $scriptPath = '/opt/app/python/forecast_sales.py';

if (file_exists($scriptPath)) {
    $pythonPathCandidates = ['python3', 'python'];
    foreach ($pythonPathCandidates as $bin) {
        $cmd = $bin . ' ' . escapeshellarg($scriptPath) . ' 2>&1';
        $output = shell_exec($cmd);
        if ($output !== null) {
            $pipelineInfo = [
                "ran" => true,
                "cmd" => $cmd,
                "stdout" => $output
            ];
            break;
        }
    }
} else {
    $pipelineInfo = ["ran" => false, "message" => "forecast_sales.py not found", "path" => $scriptPath];
}

}

try {
    // Latest metrics
    $stmtM = $pdo->query("
        SELECT model_used, mape, rmse, mae, trained_on, forecast_horizon
        FROM forecast_metrics
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $metricsRow = $stmtM->fetch();
    if (!$metricsRow) {
        $metricsRow = [
            "model_used" => "LightGBM",
            "mape" => 0.0,
            "rmse" => 0.0,
            "mae" => 0.0,
            "trained_on" => date('Y-m-d H:i:s'),
            "forecast_horizon" => 7
        ];
    }

    // Forecasts
    $forecasts = $pdo->query("
        SELECT date, forecast_amount
        FROM sales_forecast
        ORDER BY date ASC
    ")->fetchAll();
    foreach ($forecasts as &$f) {
        $f['date'] = date("Y-m-d", strtotime($f['date']));
        $f['forecast_amount'] = (float)$f['forecast_amount'];
    }

    // Actuals
    $actuals = $pdo->query("
        SELECT DATE(created_at) AS date, SUM(total_amount) AS actual_sales
        FROM sales
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll();
    foreach ($actuals as &$a) {
        $a['date'] = date("Y-m-d", strtotime($a['date']));
        $a['actual_sales'] = (float)$a['actual_sales'];
    }

    // Inventory summary
    $avgRows = $pdo->query("
        SELECT product_id, AVG(forecast_qty) AS avg_qty
        FROM product_forecast
        GROUP BY product_id
    ")->fetchAll();
    $avgMap = [];
    foreach ($avgRows as $r) {
        $avgMap[(int)$r['product_id']] = (float)$r['avg_qty'];
    }

    $invRows = $pdo->query("
        SELECT product_id, name, qty AS current_qty, threshold
        FROM inventory
        ORDER BY product_id ASC
    ")->fetchAll();

    $inventorySummary = [];
    foreach ($invRows as $row) {
        $pid = (int)$row['product_id'];
        $avg_fc = isset($avgMap[$pid]) ? (int)round($avgMap[$pid]) : 0;
        $status = "OK";
        if ($avg_fc > (int)$row['current_qty']) {
            $status = "Restock needed";
        } elseif ((int)$row['current_qty'] <= (int)$row['threshold']) {
            $status = "Stock low";
        }
        $inventorySummary[] = [
            "product_id" => $pid,
            "name" => $row['name'],
            "avg_forecast_qty" => $avg_fc,
            "current_qty" => (int)$row['current_qty'],
            "threshold" => (int)$row['threshold'],
            "status" => $status
        ];
    }

    echo json_encode([
        "status" => "success",
        "pipeline" => $pipelineInfo,
        "model" => $metricsRow['model_used'],
        "horizon" => (int)$metricsRow['forecast_horizon'],
        "trained_on" => $metricsRow['trained_on'],
        "forecasts" => $forecasts,
        "actuals" => $actuals,
        "metrics" => [
            "rmse" => (float)$metricsRow['rmse'],
            "mae" => (float)$metricsRow['mae'],
            "mape" => (float)$metricsRow['mape']
        ],
        "inventory_summary" => $inventorySummary
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "pipeline" => $pipelineInfo
    ]);
}

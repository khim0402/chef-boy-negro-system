<?php
header('Content-Type: application/json');

$DB_HOST = "dpg-d4i43m75r7bs73c7gvl0-a";  // Render/Postgres host
$DB_NAME = "chefboynegro";
$DB_USER = "chefboyuser";                // Postgres user
$DB_PASS = "jA9GdmJDas6lbzWYzIybF50GqoHvOqwF"; // Postgres password
$DB_PORT = "5432";

try {
  $pdo = new PDO("pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);

  // Latest metrics
  $stmtM = $pdo->query("SELECT model_used, mape, rmse, mae, trained_on, forecast_horizon 
                        FROM forecast_metrics ORDER BY created_at DESC LIMIT 1");
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

  // Daily aggregated forecast (₱)
  $forecasts = $pdo->query("SELECT date, forecast_amount, model_used 
                            FROM sales_forecast ORDER BY date ASC")->fetchAll();

  // Actual daily sales (₱)
  $actuals = $pdo->query("SELECT DATE(created_at) AS date, SUM(total_amount) AS actual_sales 
                          FROM sales GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC")->fetchAll();

  // Inventory summary with avg forecast qty per product
  $avgRows = $pdo->query("SELECT product_id, AVG(forecast_qty) AS avg_qty 
                          FROM product_forecast GROUP BY product_id")->fetchAll();
  $avgMap = [];
  foreach ($avgRows as $r) {
    $avgMap[(int)$r['product_id']] = (float)$r['avg_qty'];
  }

  $invRows = $pdo->query("SELECT product_id, name, qty AS current_qty, threshold 
                          FROM inventory ORDER BY product_id ASC")->fetchAll();
  $inventorySummary = [];
  foreach ($invRows as $row) {
    $pid = (int)$row['product_id'];
    $avg_fc = isset($avgMap[$pid]) ? (int)round($avgMap[$pid]) : 0;
    $status = "OK";
    if ($avg_fc > (int)$row['current_qty']) {
      $status = "⚠️ Restock needed (forecast $avg_fc > stock {$row['current_qty']})";
    } else if ((int)$row['current_qty'] <= (int)$row['threshold']) {
      $status = "⚠️ Stock low (qty {$row['current_qty']} ≤ threshold {$row['threshold']})";
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
    "model" => $metricsRow['model_used'],
    "horizon" => (int)$metricsRow['forecast_horizon'],
    "trained_on" => $metricsRow['trained_on'],
    "forecasts" => array_map(function($f) {
      return [
        "date" => date('Y-m-d', strtotime($f['date'])),
        "forecast_amount" => (float)$f['forecast_amount']
      ];
    }, $forecasts),
    "actuals" => array_map(function($a) {
      return [
        "date" => date('Y-m-d', strtotime($a['date'])),
        "actual_sales" => (float)$a['actual_sales']
      ];
    }, $actuals),
    "metrics" => [
      "rmse" => (float)$metricsRow['rmse'],
      "mae" => (float)$metricsRow['mae'],
      "mape" => (float)$metricsRow['mape']
    ],
    "inventory_summary" => $inventorySummary
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Server error", "detail" => $e->getMessage()]);
}
?>

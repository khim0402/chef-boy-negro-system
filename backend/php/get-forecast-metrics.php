<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/db.php');

try {
    $res = $conn->query("SELECT model_used, mape, rmse, mae, trained_on, forecast_horizon 
                         FROM forecast_metrics ORDER BY created_at DESC LIMIT 1");

    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode([
            "status" => "success",
            "metrics" => [
                "model" => $row['model_used'],
                "mape" => (float)$row['mape'],
                "rmse" => (float)$row['rmse'],
                "mae" => (float)$row['mae'],
                "trained_on" => $row['trained_on'],
                "horizon" => (int)$row['forecast_horizon']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "metrics" => [
                "model" => null,
                "mape" => 0,
                "rmse" => 0,
                "mae" => 0,
                "trained_on" => null,
                "horizon" => 0
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../db.php');

// Use relative path to Python script
$output = shell_exec("python3 " . escapeshellarg(__DIR__ . '/../../python/forecast_sales.py') . " 2>&1");

if ($output === null) {
    echo json_encode(["status" => "error", "message" => "Forecast script failed"]);
} else {
    $data = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["status" => "error", "message" => "Invalid forecast output"]);
    } else {
        echo $output; // Already JSON from forecast_sales.py
    }
}
?>

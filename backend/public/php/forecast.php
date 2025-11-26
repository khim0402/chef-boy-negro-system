<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../php/db.php'); // ✅ go up two levels, then into php/db.php

$output = shell_exec("python3 " . escapeshellarg(__DIR__ . '/../../python/forecast_sales.py') . " 2>&1");

if ($output === null) {
    echo json_encode(["status" => "error", "message" => "Forecast script failed"]);
} else {
    $data = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid forecast output",
            "raw_output" => $output // ✅ show Python’s actual output for debugging
        ]);
    } else {
        echo $output; // ✅ already JSON from Python
    }
}
?>

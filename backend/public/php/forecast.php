<?php
// /var/www/html/php/forecast.php
header('Content-Type: application/json');

// Config
$BASE_URL = "https://chefboynegro-forecast.onrender.com";

// Helper to call an endpoint safely
function call_endpoint($url, $method = "GET", $timeout = 30) {
    $options = [
        "http" => [
            "method" => $method,
            "timeout" => $timeout,
            "ignore_errors" => true
        ]
    ];
    $context = stream_context_create($options);
    $resp = @file_get_contents($url, false, $context);

    if ($resp === false) {
        echo json_encode(["status" => "error", "message" => "Unable to reach forecast service"]);
        exit;
    }
    // Pass through the response from the Python service
    echo $resp;
    exit;
}

// Route based on action
$action = $_GET["action"] ?? "view";

if ($action === "run") {
    // Manual trigger with optional batch number: /php/forecast.php?action=run&batch=2
    $batch = intval($_GET["batch"] ?? "1");
    if ($batch < 1) $batch = 1;

    $url = $BASE_URL . "/run-forecast?batch=" . urlencode((string)$batch);
    call_endpoint($url, "POST", 60); // longer timeout for training
} else {
    // Default dashboard: cached forecast
    $url = $BASE_URL . "/forecast";
    call_endpoint($url, "GET", 20);
}

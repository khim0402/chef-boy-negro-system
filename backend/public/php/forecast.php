<?php
header('Content-Type: application/json');

// Call the deployed Python service via HTTP
$apiUrl = "https://chefboynegro-forecast.onrender.com/forecast";

$response = file_get_contents($apiUrl);

if ($response === false) {
    echo json_encode(["status" => "error", "message" => "Unable to reach forecast service"]);
} else {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid forecast response",
            "raw_output" => $response
        ]);
    } else {
        echo $response; // ✅ Already JSON from Flask
    }
}
?>

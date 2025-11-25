<?php
header('Content-Type: application/json');

// Call Python forecast script inside container
$output = shell_exec("python /var/www/html/php/forecast_sales.py 2>&1");

if ($output === null) {
    echo json_encode(["status" => "error", "message" => "Forecast script failed"]);
} else {
    echo $output; // forecast_sales.py should print JSON
}
?>

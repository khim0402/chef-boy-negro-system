<?php
$output = shell_exec('"C:\xampp\htdocs\Chef Boy Negro\venv\Scripts\python.exe" "C:\xampp\htdocs\Chef Boy Negro\backend\forecast_sales.py" 2>&1');

if ($output === null) {
    header("Location: ../html/forecast.html?status=error");
    exit;
} else {
    header("Location: ../html/forecast.html?status=success");
    exit;
}
?>

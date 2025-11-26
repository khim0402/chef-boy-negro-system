<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../php/db.php');

try {
    echo json_encode([
        "status" => "ok",
        "message" => "DB connected",
        "whoami" => getenv("PGUSER"),
        "dbname" => getenv("PGDATABASE")
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>

<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

try {
    $stmt = $conn->query('SELECT 1');
    echo json_encode(["status" => "ok", "message" => "DB Connected"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

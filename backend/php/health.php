<?php
require 'db.php';
try {
    $stmt = $conn->query("SELECT 1");
    echo json_encode(["status" => "ok", "db" => "connected"]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "detail" => $e->getMessage()]);
}
?>

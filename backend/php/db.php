<?php
$dsn = getenv('DB_URL'); // full connection string from Render

try {
    $conn = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}
?>

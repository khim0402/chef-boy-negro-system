<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

try {
    $conn = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: quick test query
    $stmt = $conn->query("SELECT 1");
    echo json_encode(["db" => "ok"]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed", "detail" => $e->getMessage()]);
}
?>

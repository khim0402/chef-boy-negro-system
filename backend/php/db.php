<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;sslrootcert=/etc/ssl/certs/ca-certificates.crt",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}
?>

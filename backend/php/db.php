<?php
session_start();

// First check if Render provides DB_URL
$dbUrl = getenv('DB_URL');

if ($dbUrl) {
    // Render style connection string
    $parts = parse_url($dbUrl);

    $host = $parts['host'];
    $port = $parts['port'] ?? 5432;
    $user = $parts['user'];
    $pass = $parts['pass'];
    $dbname = ltrim($parts['path'], '/');

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
} else {
    // Local fallback (XAMPP/Docker)
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'chefboynegro';
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASSWORD') ?: 'yourpassword';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
}

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Optional: remove this echo in production
    // echo json_encode(["status" => "ok", "message" => "DB Connected"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}
?>

<?php
// Detect environment: if DB_HOST is set, assume Render; else default to local
$host = getenv('DB_HOST') ?: "127.0.0.1";
$port = getenv('DB_PORT') ?: "5432";
$dbname = getenv('DB_NAME') ?: "chefboynegro";

// Local defaults
$user = getenv('DB_USER') ?: "postgres";
$pass = getenv('DB_PASS') ?: "123456";

// If running on Render, override with cloud credentials
if ($host === "dpg-d4i43m75r7bs73c7gvl0-a") {
    $user = "chefboyuser";
    $pass = "jA9GdmJDas6lbzWYzIybF50GqoHvOqwF";
}

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Silent connector: no echo
} catch(PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>

<?php
$host = getenv('DB_HOST') ?: "dpg-d4i43m75r7bs73c7gvl0-a";
$user = getenv('DB_USER') ?: "chefboyuser";
$pass = getenv('DB_PASS') ?: "jA9GdmJDas6lbzWYzIybF50GqoHvOqwF";
$dbname = getenv('DB_NAME') ?: "chefboynegro";
$port = getenv('DB_PORT') ?: "5432";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Silent connector: no echo here
} catch(PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>

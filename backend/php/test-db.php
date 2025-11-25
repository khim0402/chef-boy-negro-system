<?php
$host = getenv('DB_HOST') ?: "dpg-d4i43m75r7bs73c7gvl0-a";
$port = getenv('DB_PORT') ?: "5432";
$dbname = getenv('DB_NAME') ?: "chefboynegro";
$user = getenv('DB_USER') ?: "chefboyuser";
$pass = getenv('DB_PASS') ?: "jA9GdmJDas6lbzWYzIybF50GqoHvOqwF";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to Postgres!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>

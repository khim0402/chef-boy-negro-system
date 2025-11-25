<?php
$host = "dpg-d4imdsngi27c739mueug-a.oregon-postgres.render.com";
$port = "5432";
$dbname = "chefboynegro_db";
$user = "chefboynegro_db_user";
$password = "cvAt4jXC9okoDhKe6NK5wD742R3UqHo6"; // set this correctly

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password;sslmode=require";
    $pdo = new PDO($dsn);
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

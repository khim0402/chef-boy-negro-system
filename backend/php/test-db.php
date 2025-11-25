<?php
require 'db.php'; // include your hybrid db.php

try {
    $stmt = $conn->query("SELECT NOW()");
    $row = $stmt->fetch();
    echo "✅ Database connection works. Current time: " . $row['now'];
} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage();
}
?>

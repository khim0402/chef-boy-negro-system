<?php
require_once(__DIR__ . '/db.php');
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT product_id, category, name, price, qty, threshold FROM inventory");
    $items = $stmt->fetchAll();
    echo json_encode($items);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../php/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$orderItems = $data['orderItems'] ?? [];

$outOfStock = [];

try {
    // Force associative fetch so we can access by column name
    $stmt = $pdo->prepare("SELECT qty, name FROM inventory WHERE product_id = :product_id");

    foreach ($orderItems as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $qty        = (int)($item['qty'] ?? 0);
        $fallbackName = $item['name'] ?? "Unknown";

        if ($product_id <= 0 || $qty <= 0) {
            $outOfStock[] = $fallbackName;
            continue;
        }

        $stmt->execute([':product_id' => $product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug log (check your PHP error log)
        error_log("Stock check for product_id=$product_id → " . json_encode($row));

        if (!$row || (int)$row['qty'] < $qty) {
            $outOfStock[] = $row['name'] ?? $fallbackName;
        }
    }

    if (!empty($outOfStock)) {
        echo json_encode(['status' => 'out_of_stock', 'items' => $outOfStock]);
    } else {
        echo json_encode(['status' => 'ok']);
    }
} catch (Exception $e) {
    error_log("Stock check error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
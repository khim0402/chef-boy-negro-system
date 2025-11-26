<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../php/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$orderItems = $data['orderItems'] ?? [];

$outOfStock = [];

try {
    // Select both possible name columns defensively
    $stmt = $pdo->prepare("SELECT qty, name, product_name FROM inventory WHERE product_id = :product_id");

    foreach ($orderItems as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);
        $fallbackName = $item['name'] ?? "Unknown";

        if ($product_id <= 0 || $qty <= 0) {
            $outOfStock[] = $fallbackName;
            continue;
        }

        $stmt->execute([':product_id' => $product_id]);
        $row = $stmt->fetch();

        // Determine display name safely
        $rowName = null;
        if ($row) {
            if (isset($row['name']) && $row['name'] !== null) $rowName = $row['name'];
            if (isset($row['product_name']) && $row['product_name'] !== null) $rowName = $row['product_name'];
        }

        if (!$row || (int)$row['qty'] < $qty) {
            $outOfStock[] = $rowName ?: $fallbackName;
        }
    }

    if (!empty($outOfStock)) {
        echo json_encode(['status' => 'out_of_stock', 'items' => $outOfStock]);
    } else {
        echo json_encode(['status' => 'ok']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

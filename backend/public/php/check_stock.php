<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../php/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$orderItems = $data['orderItems'] ?? [];

$outOfStock = [];

try {
    $stmt = $pdo->prepare("SELECT qty, name FROM inventory WHERE product_id = :product_id");
    foreach ($orderItems as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);

        if ($product_id <= 0 || $qty <= 0) {
            $outOfStock[] = $item['name'] ?? "Unknown";
            continue;
        }

        $stmt->execute([':product_id' => $product_id]);
        $row = $stmt->fetch();
        if (!$row || $row['qty'] < $qty) {
            $outOfStock[] = $row['name'] ?? $item['name'] ?? "Unknown";
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
?>

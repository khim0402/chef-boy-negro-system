<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../../php/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$orderItems = $data['orderItems'] ?? [];

$outOfStock = [];

try {
    $stmt = $pdo->prepare("SELECT qty FROM inventory WHERE name = :name");
    foreach ($orderItems as $item) {
        $stmt->execute([':name' => $item['name']]);
        $row = $stmt->fetch();
        if (!$row || $row['qty'] < (int)$item['qty']) {
            $outOfStock[] = $item['name'];
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

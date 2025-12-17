<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$orderItems = $data['orderItems'] ?? [];

$outOfStock = [];
$insufficient = [];

try {
    $stmt = $pdo->prepare("SELECT qty, name, oil_usage, category, archived FROM inventory WHERE product_id = :product_id");
    $stmtOil = $pdo->query("SELECT qty FROM oil_stock ORDER BY id DESC LIMIT 1");
    $oilStock = (int)$stmtOil->fetchColumn();

    foreach ($orderItems as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $qty        = (int)($item['qty'] ?? 0);
        $fallbackName = $item['name'] ?? "Unknown";

        $stmt->execute([':product_id' => $product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $outOfStock[] = $fallbackName;
            continue;
        }

        // Block archived/Bilao
        if (!empty($row['archived']) || in_array($row['category'], ['Bilao', 'Bilao/Trays'], true)) {
            $outOfStock[] = $row['name'];
            continue;
        }

        $stock = (int)$row['qty'];

        if ($stock <= 0) {
            $outOfStock[] = $row['name'];
        } elseif ($stock < $qty) {
            $insufficient[] = $row['name'];
        }

        // Oil check
        $oilUsage = (int)($row['oil_usage'] ?? 0);
        if ($oilUsage > 0 && $oilStock < ($oilUsage * $qty)) {
            if ($oilStock <= 0) {
                $outOfStock[] = "Oil (needed for {$row['name']})";
            } else {
                $insufficient[] = "Oil (needed for {$row['name']})";
            }
        }
    }

    if (!empty($outOfStock) || !empty($insufficient)) {
        echo json_encode([
            'status' => 'stock_issue',
            'out_of_stock' => $outOfStock,
            'insufficient' => $insufficient
        ]);
    } else {
        echo json_encode(['status' => 'ok']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

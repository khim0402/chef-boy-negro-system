<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: frame-ancestors 'self'");

require_once(__DIR__ . '/../../php/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['name'];
    $qty = (int)$data['qty'];
    $action = $data['action'];

    if ($action === 'add') {
        $stmt = $conn->prepare("UPDATE inventory SET qty = qty + ? WHERE name = ?");
        $stmt->bind_param("is", $qty, $name);
        $stmt->execute();
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("UPDATE inventory SET qty = GREATEST(qty - ?, 0) WHERE name = ?");
        $stmt->bind_param("is", $qty, $name);
        $stmt->execute();
    }

    echo json_encode(["status" => "success"]);
    exit;
}

try {
    $items = [];
    $res = $conn->query("SELECT product_id, name, category, price, qty, threshold 
                         FROM inventory ORDER BY product_id ASC");
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            "product_id" => $row['product_id'],
            "name" => $row['name'],
            "category" => $row['category'],
            "price" => (float)$row['price'],
            "qty" => (int)$row['qty'],
            "threshold" => (int)$row['threshold']
        ];
    }
    echo json_encode($items);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

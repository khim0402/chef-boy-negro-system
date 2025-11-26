<?php
require_once(__DIR__ . '/db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

try {
    $product_id = (int)($data['product_id'] ?? 0);
    $qty = (int)($data['qty'] ?? 0);

    if ($product_id <= 0 || $qty < 0) {
        throw new Exception("Invalid product or quantity.");
    }

    $stmt = $pdo->prepare("UPDATE inventory SET qty = :qty WHERE product_id = :id");
    $stmt->execute([
        ':qty' => $qty,
        ':id'  => $product_id
    ]);

    $stmt = $pdo->prepare("SELECT product_id, qty FROM inventory WHERE product_id = :id");
    $stmt->execute([':id' => $product_id]);
    $updated = $stmt->fetch();

    echo json_encode(['status' => 'updated', 'product' => $updated]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

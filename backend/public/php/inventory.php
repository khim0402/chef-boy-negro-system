<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $product_id = (int)($data['product_id'] ?? 0);
    $qty        = (int)($data['qty'] ?? 0);
    $action     = $data['action'] ?? '';

    try {
        if ($product_id <= 0 || $qty <= 0) {
            throw new Exception("Invalid product or quantity.");
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare("UPDATE inventory SET qty = qty + :qty WHERE product_id = :product_id");
        } elseif ($action === 'remove') {
            // Prevent overselling
            $stmtCheck = $pdo->prepare("SELECT qty FROM inventory WHERE product_id = :product_id");
            $stmtCheck->execute([':product_id' => $product_id]);
            $stock = (int)$stmtCheck->fetchColumn();
            if ($stock < $qty) {
                throw new Exception("Insufficient stock for product ID $product_id");
            }
            $stmt = $pdo->prepare("UPDATE inventory SET qty = qty - :qty WHERE product_id = :product_id");
        } else {
            throw new Exception("Invalid action");
        }

        $stmt->execute([':qty' => $qty, ':product_id' => $product_id]);
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

try {
    $stmt = $pdo->query("SELECT product_id, name, category, price, qty, threshold 
                         FROM inventory ORDER BY product_id ASC");
    $items = $stmt->fetchAll();
    echo json_encode($items);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

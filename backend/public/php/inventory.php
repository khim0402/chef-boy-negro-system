<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../php/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name   = $data['name'] ?? '';
    $qty    = (int)($data['qty'] ?? 0);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("UPDATE inventory SET qty = qty + :qty WHERE name = :name");
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("UPDATE inventory SET qty = GREATEST(qty - :qty, 0) WHERE name = :name");
        } else {
            throw new Exception("Invalid action");
        }

        $stmt->execute([':qty' => $qty, ':name' => $name]);
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

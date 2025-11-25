<?php
require_once(__DIR__ . '/db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $pdo->prepare("UPDATE inventory SET qty = :qty WHERE product_id = :id");
    $stmt->execute([
        ':qty' => (int)$data['qty'],
        ':id'  => (int)$data['product_id']
    ]);
    echo json_encode(['status' => 'updated']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

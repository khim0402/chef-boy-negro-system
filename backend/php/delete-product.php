<?php
require_once(__DIR__ . '/db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE product_id = :id");
    $stmt->execute([':id' => $data['product_id']]);
    echo json_encode(['status' => 'deleted']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

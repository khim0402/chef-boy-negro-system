<?php
require_once(__DIR__ . '/db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $pdo->prepare("INSERT INTO inventory (category, name, price, qty, threshold) VALUES (:category, :name, :price, :qty, :threshold)");
    $stmt->execute([
        ':category' => $data['category'],
        ':name' => $data['name'],
        ':price' => $data['price'],
        ':qty' => $data['qty'],
        ':threshold' => $data['threshold']
    ]);
    echo json_encode(['status' => 'added']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

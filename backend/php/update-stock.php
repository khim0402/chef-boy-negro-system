<?php
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'];
$new_qty = $data['qty'];

$stmt = $conn->prepare("UPDATE inventory SET qty = ? WHERE product_id = ?");
$stmt->bind_param("ii", $new_qty, $product_id);
$stmt->execute();

echo json_encode(['status' => 'updated']);
?>

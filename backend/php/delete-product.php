<?php
require_once(__DIR__ . '/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'];

$stmt = $conn->prepare("DELETE FROM inventory WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();

echo json_encode(['status' => 'deleted']);
?>

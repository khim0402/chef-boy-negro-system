<?php
require_once(__DIR__ . '/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'];
$category = $data['category'];
$price = $data['price'];
$qty = $data['qty'];
$threshold = $data['threshold'];

$stmt = $conn->prepare("INSERT INTO inventory (category, name, price, qty, threshold) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdii", $category, $name, $price, $qty, $threshold);
$stmt->execute();

echo json_encode(['status' => 'added']);
?>

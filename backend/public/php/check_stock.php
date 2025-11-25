<?php
// check_stock proxy: capture backend output and validate JSON
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(0);
require_once(__DIR__ . '/../../php/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$orderItems = $data['orderItems'] ?? [];

$outOfStock = [];

foreach ($orderItems as $item) {
  $name = $item['name'];
  $qty = $item['qty'];

  // Match against inventory name
  $stmt = $conn->prepare("SELECT qty FROM inventory WHERE name = ?");
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();

  if (!$row || $row['qty'] < $qty) {
    $outOfStock[] = $name;
  }
}

if (!empty($outOfStock)) {
  echo json_encode(['status' => 'out_of_stock', 'items' => $outOfStock]);
} else {
  echo json_encode(['status' => 'ok']);
}
?>

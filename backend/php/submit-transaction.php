<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$items = $data['items'] ?? [];
$method = $conn->real_escape_string($data['payment_method'] ?? '');

if (empty($items) || !$method) {
  echo json_encode(['status' => 'error', 'message' => 'Missing items or payment method']);
  exit;
}

$conn->begin_transaction();

try {
  foreach ($items as $item) {
    $product_id = (int)$item['product_id'];
    $name = $conn->real_escape_string($item['name']);
    $qty = (int)$item['qty'];
    $amount = (float)$item['amount'];

    // 🔹 Insert into sales table
    $stmt = $conn->prepare("INSERT INTO sales (sale_date, product_name, payment_method, quantity, amount, product_id) VALUES (CURDATE(), ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidi", $name, $method, $qty, $amount, $product_id);
    $stmt->execute();

    // 🔹 Deduct from inventory
    $conn->query("UPDATE inventory SET qty = GREATEST(0, qty - $qty) WHERE product_id = $product_id");
  }

  $conn->commit();
  echo json_encode(['status' => 'success']);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['status' => 'error', 'message' => 'Transaction failed']);
}
?>

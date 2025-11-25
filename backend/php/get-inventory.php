<?php
require_once(__DIR__ . '/db.php');

$sql = "SELECT product_id, category, name, price, qty, threshold FROM inventory";
$result = $conn->query($sql);

$items = [];
while ($row = $result->fetch_assoc()) {
  $items[] = $row;
}

echo json_encode($items);
?>

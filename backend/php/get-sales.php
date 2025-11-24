<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$where = '';
if ($start && $end) {
  $where = "WHERE sale_date BETWEEN '$start' AND '$end'";
}

$result = $conn->query("SELECT * FROM sales $where ORDER BY sale_date DESC");

$sales = [];
$daily = $weekly = $monthly = 0;

while ($row = $result->fetch_assoc()) {
  $sales[] = $row;
  $amount = (float)$row['amount'];
  $date = strtotime($row['sale_date']);

  if (date('Y-m-d', $date) === date('Y-m-d')) $daily += $amount;
  if (date('W', $date) === date('W')) $weekly += $amount;
  if (date('Y-m', $date) === date('Y-m')) $monthly += $amount;
}

echo json_encode([
  'status' => 'success',
  'sales' => $sales,
  'daily' => $daily,
  'weekly' => $weekly,
  'monthly' => $monthly
]);
?>

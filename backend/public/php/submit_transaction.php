<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../php/db.php');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['items']) || !is_array($input['items'])) {
        throw new Exception("Invalid items array.");
    }

    $payment_method = $input['payment_method'] ?? 'Unknown';
    $items = $input['items'];
    $total_amount = 0;

    $conn->begin_transaction();

    foreach ($items as $item) {
        if ($item['voided']) continue;
        $total_amount += $item['amount'];
    }

    $stmt = $conn->prepare("INSERT INTO sales (payment_method, total_amount, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("sd", $payment_method, $total_amount);
    $stmt->execute();
    $sales_id = $stmt->insert_id;
    $stmt->close();

    $itemStmt = $conn->prepare("INSERT INTO sales_items (sales_id, product_id, qty, price, amount) VALUES (?, ?, ?, ?, ?)");
    $invStmt = $conn->prepare("UPDATE inventory SET qty = qty - ? WHERE product_id = ?");

    foreach ($items as $item) {
        if ($item['voided']) continue;

        $itemStmt->bind_param("iiidd", $sales_id, $item['product_id'], $item['qty'], $item['price'], $item['amount']);
        $itemStmt->execute();

        $invStmt->bind_param("ii", $item['qty'], $item['product_id']);
        $invStmt->execute();
    }

    $itemStmt->close();
    $invStmt->close();
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Transaction saved",
        "transaction_id" => $sales_id
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>

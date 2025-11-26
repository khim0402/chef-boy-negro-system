<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/db.php');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['items']) || !is_array($input['items'])) {
        throw new Exception("Invalid items array.");
    }

    $payment_method = trim($input['payment_method'] ?? '');
    if ($payment_method === '') {
        throw new Exception("Missing payment method.");
    }

    $items = $input['items'];
    $total_amount = 0.0;

    foreach ($items as $item) {
        if (!empty($item['voided'])) continue;
        $total_amount += (float)($item['amount'] ?? 0);
    }

    $pdo->beginTransaction();

    $stmtSale = $pdo->prepare("
        INSERT INTO sales (payment_method, total_amount, created_at)
        VALUES (:payment_method, :total_amount, NOW())
        RETURNING id
    ");
    $stmtSale->execute([
        ':payment_method' => $payment_method,
        ':total_amount'   => $total_amount
    ]);
    $sales_id = (int)$stmtSale->fetchColumn();

    $stmtItem = $pdo->prepare("
        INSERT INTO sales_items (sales_id, product_id, qty, price, amount)
        VALUES (:sales_id, :product_id, :qty, :price, :amount)
    ");
    $stmtInv = $pdo->prepare("
        UPDATE inventory SET qty = qty - :qty WHERE product_id = :product_id
    ");
    $stmtCheck = $pdo->prepare("SELECT qty FROM inventory WHERE product_id = :product_id");

    foreach ($items as $item) {
        if (!empty($item['voided'])) continue;

        $product_id = (int)$item['product_id'];
        $qty        = (int)$item['qty'];
        $price      = (float)$item['price'];
        $amount     = (float)$item['amount'];

        if ($product_id <= 0 || $qty <= 0) {
            throw new Exception("Invalid product or quantity in items.");
        }

        $stmtCheck->execute([':product_id' => $product_id]);
        $stock = (int)$stmtCheck->fetchColumn();
        if ($stock < $qty) {
            throw new Exception("Insufficient stock for product ID $product_id");
        }

        $stmtItem->execute([
            ':sales_id'   => $sales_id,
            ':product_id' => $product_id,
            ':qty'        => $qty,
            ':price'      => $price,
            ':amount'     => $amount
        ]);

        $stmtInv->execute([
            ':qty'        => $qty,
            ':product_id' => $product_id
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Transaction saved",
        "transaction_id" => $sales_id,
        "total_amount" => $total_amount
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
